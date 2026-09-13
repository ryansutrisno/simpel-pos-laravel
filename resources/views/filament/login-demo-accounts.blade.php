@php
    // Daftar akun mengikuti DatabaseSeeder (semua memakai password: "password").
    $demoAccounts = [
        ['role' => 'Super Admin', 'email' => 'superadmin@pos.test'],
        ['role' => 'Admin', 'email' => 'admin@pos.test'],
        ['role' => 'Manager', 'email' => 'manager@pos.test'],
        ['role' => 'Kasir', 'email' => 'kasir@pos.test'],
    ];
@endphp

<x-filament::section
    class="mt-6"
    compact
    icon="heroicon-o-key"
    icon-color="warning"
    heading="Akun Demo"
    description="Gunakan salah satu akun berikut untuk masuk dan mencoba aplikasi."
>
    <ul class="space-y-2">
        @foreach ($demoAccounts as $account)
            <li class="space-y-1 text-sm" wire:key="demo-account-{{ $account['email'] }}">
                <p class="font-medium text-gray-950 dark:text-white">
                    {{ $account['role'] }}
                </p>
                <code class="block font-mono text-xs text-gray-600 dark:text-gray-400">
                    {{ $account['email'] }} / password
                </code>
            </li>
        @endforeach
    </ul>
</x-filament::section>
