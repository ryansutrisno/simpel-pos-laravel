@php
    $demoLandingUrl = config('services.demo.landing_url');
@endphp

@if (filled($demoLandingUrl))
    <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
        Ingin melihat demo?
        <a
            href="{{ $demoLandingUrl }}"
            target="_blank"
            rel="noopener noreferrer"
            class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
        >
            Hubungi kami
        </a>
    </p>
@endif
