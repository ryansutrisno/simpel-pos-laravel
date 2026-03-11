<?php

namespace App\Http\Middleware;

use App\Models\AppSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class LoadAppSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        $appSettings = Cache::remember('app.settings', 3600, function () {
            return AppSettings::getInstance();
        });

        if ($appSettings) {
            config(['app.name' => $appSettings->app_name ?? 'Simpel POS']);
            config(['app.timezone' => $appSettings->timezone ?? 'Asia/Jakarta']);
        }

        return $next($request);
    }
}
