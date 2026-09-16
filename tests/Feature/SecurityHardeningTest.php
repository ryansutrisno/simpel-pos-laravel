<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

it('adds security headers to secure responses', function () {
    $response = securityHeadersResponse('false');

    expect($response->headers->get('Strict-Transport-Security'))
        ->toBe('max-age=31536000; includeSubDomains')
        ->and($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
        ->and($response->headers->get('Permissions-Policy'))
        ->toBe('camera=(), microphone=(), geolocation=(), payment=(self)');
});

it('adds the content security policy when enabled', function () {
    $response = securityHeadersResponse('true');

    expect($response->headers->get('Content-Security-Policy'))
        ->toBe("default-src 'self'; script-src 'self' 'unsafe-eval' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self' ws: wss:; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'");
});

it('does not add the content security policy when disabled', function () {
    $response = securityHeadersResponse('false');

    expect($response->headers->has('Content-Security-Policy'))->toBeFalse();
});

it('registers the named security rate limiters', function () {
    expect(RateLimiter::limiter('mayar-webhook'))->toBeInstanceOf(Closure::class)
        ->and(RateLimiter::limiter('login'))->toBeInstanceOf(Closure::class);
});

it('adds security headers to a real HTTP response', function () {
    $this->get('/up')
        ->assertSuccessful()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('exempts the Mayar webhook from CSRF validation', function () {
    expect($this->post('/webhook/mayar/whatever-token')->status())
        ->not->toBe(419);
});

function securityHeadersResponse(string $cspEnabled): Response
{
    putenv("SECURITY_CSP_ENABLED={$cspEnabled}");

    try {
        return (new SecurityHeaders)->handle(
            Request::create('https://example.test/'),
            fn (): Response => new Response('ok'),
        );
    } finally {
        putenv('SECURITY_CSP_ENABLED');
    }
}
