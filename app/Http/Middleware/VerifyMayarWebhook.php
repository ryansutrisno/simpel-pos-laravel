<?php

namespace App\Http\Middleware;

use App\Models\PaymentGatewayConfig;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates the unguessable Mayar webhook path and request size.
 */
class VerifyMayarWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $event = $request->input('event');
        $deliveryId = $request->input('data.id');
        $token = (string) $request->route('token');
        $config = PaymentGatewayConfig::query()
            ->where('provider', PaymentGatewayConfig::PROVIDER_MAYAR)
            ->where('is_active', true)
            ->whereNotNull('webhook_path_token')
            ->where('webhook_path_token', $token)
            ->first();

        if (! $config || ! hash_equals((string) $config->webhook_path_token, $token)) {
            Log::warning('Mayar webhook rejected', [
                'event' => $event,
                'delivery_id' => $deliveryId,
                'outcome' => 'unknown_token',
            ]);

            return response()->json(['message' => 'Not found'], 404);
        }

        if ((int) $request->header('Content-Length', 0) > 16384 || strlen($request->getContent()) > 16384) {
            Log::warning('Mayar webhook rejected', [
                'event' => $event,
                'delivery_id' => $deliveryId,
                'outcome' => 'payload_too_large',
            ]);

            return response()->json(['message' => 'Payload too large'], 413);
        }

        $response = $next($request);

        Log::info('Mayar webhook processed', [
            'event' => $event,
            'delivery_id' => $deliveryId,
            'outcome' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
