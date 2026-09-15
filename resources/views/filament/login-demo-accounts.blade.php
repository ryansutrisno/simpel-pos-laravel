@php
    // Daftar akun mengikuti DatabaseSeeder (semua memakai password: "password").
    $demoAccounts = [
        ['role' => 'Super Admin', 'email' => 'superadmin@pos.test', 'initial' => 'S'],
        ['role' => 'Admin', 'email' => 'admin@pos.test', 'initial' => 'A'],
        ['role' => 'Manager', 'email' => 'manager@pos.test', 'initial' => 'M'],
        ['role' => 'Kasir', 'email' => 'kasir@pos.test', 'initial' => 'K'],
    ];
@endphp

<x-filament::section
    class="mt-6"
    compact
    icon="heroicon-o-key"
    icon-color="warning"
    heading="Akun Demo"
    description="Klik akun untuk mengisi form. Password: password"
>
    <ul class="flex flex-col gap-2">
        @foreach ($demoAccounts as $account)
            <li wire:key="demo-account-{{ $account['email'] }}">
                <button
                    type="button"
                    x-data="{ filled: false, hovering: false }"
                    x-on:mouseenter="hovering = true"
                    x-on:mouseleave="hovering = false"
                    x-on:click="$wire.set('data.email', '{{ $account['email'] }}'); $wire.set('data.password', 'password'); $wire.set('data.remember', false); filled = true"
                    aria-label="Isi form dengan akun {{ $account['role'] }}"
                    class="group flex w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-left transition-colors hover:bg-gray-50 focus-visible:ring-2 focus-visible:ring-primary-500 dark:hover:bg-white/5"
                    :class="filled ? 'bg-primary-50 dark:bg-white/5' : ''"
                >
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-sm font-semibold text-primary-600 dark:bg-white/5 dark:text-primary-400">
                        {{ $account['initial'] }}
                    </span>

                    <span class="flex min-w-0 flex-col gap-1">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            {{ $account['role'] }}
                        </span>
                        <span class="truncate font-mono text-sm font-medium text-gray-950 dark:text-white">
                            {{ $account['email'] }}
                        </span>
                    </span>

                    <span
                        x-show="filled"
                        x-transition.opacity.duration.150ms
                        class="ml-auto flex shrink-0 items-center gap-1 text-xs font-medium text-primary-600 dark:text-primary-400"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                        </svg>
                        Terisi
                    </span>

                    <svg
                        x-show="!filled"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                        class="h-4 w-4 shrink-0 text-gray-400 transition-opacity dark:text-gray-500"
                        :class="hovering ? 'opacity-100' : 'opacity-0'"
                    >
                        <path fill-rule="evenodd" d="M8.22 4.47a.75.75 0 0 1 1.06 0l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 0 1-1.06-1.06L12.19 10 8.22 6.03a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </button>
            </li>
        @endforeach
    </ul>
</x-filament::section>
