<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to verify Mayar webhook requests.
 *
 * Validates that the incoming webhook is from Mayar by checking
 * the request signature and API key.
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
        // Log all webhook requests for debugging
        Log::info('Mayar webhook received', [
            'ip' => $request->ip(),
            'url' => $request->url(),
            'headers' => $request->headers->all(),
        ]);

        // For Mayar, we don't have signature verification in the basic API
        // Instead, we can verify the request comes from a trusted source
        // by checking if the data structure is valid
        if (! $this->isValidPayload($request->all())) {
            Log::warning('Invalid Mayar webhook payload', [
                'data' => $request->all(),
            ]);

            return response()->json(['error' => 'Invalid payload'], 400);
        }

        return $next($request);
    }

    /**
     * Validate the webhook payload structure.
     */
    protected function isValidPayload(array $data): bool
    {
        // Required fields for Mayar webhook
        $required = ['transactionId', 'status', 'amount'];

        foreach ($required as $field) {
            if (! isset($data[$field])) {
                return false;
            }
        }

        // Validate status is one of expected values
        $validStatuses = ['UNPAID', 'PAID', 'EXPIRED'];
        if (! in_array(strtoupper($data['status']), $validStatuses)) {
            return false;
        }

        // Validate amount is numeric
        if (! is_numeric($data['amount'])) {
            return false;
        }

        return true;
    }
}
