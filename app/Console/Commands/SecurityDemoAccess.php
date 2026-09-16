<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SecurityDemoAccess extends Command
{
    protected $signature = 'security:demo-access
        {--enable= : Enable access for a duration such as 30m, 2h, or 1d}
        {--disable : Disable demo access immediately}
        {--promote : Return a demo account to normal staff access}
        {--email=* : Email address to target; repeat for multiple accounts}
        {--status : Show current demo access without changing anything}
        {--force : Allow demo access changes in production}';

    protected $description = 'Enable, disable, or inspect POS demo account access';

    public function handle(): int
    {
        $enableDuration = $this->option('enable');
        $isDisable = (bool) $this->option('disable');
        $isPromote = (bool) $this->option('promote');
        $isStatus = (bool) $this->option('status');
        $actionCount = (int) ($enableDuration !== null) + (int) $isDisable + (int) $isPromote + (int) $isStatus;

        if ($actionCount !== 1) {
            $this->error('Choose exactly one action: --enable=DURATION, --disable, --promote, or --status.');

            return self::FAILURE;
        }

        $requestedEmails = $this->requestedEmails();

        if ($isPromote && $requestedEmails === []) {
            $this->error('The --promote action requires at least one explicit --email=.');
            $this->error('Promoting every @pos.test account at once would silently unlock the entire demo set.');

            return self::FAILURE;
        }

        if (! $isStatus && app()->isProduction() && ! (bool) $this->option('force')) {
            $this->error('Demo access changes are blocked in production unless you pass --force.');

            return self::FAILURE;
        }

        $users = $this->targetUsers($requestedEmails);
        $unknownEmails = $this->reportUnknownEmails($requestedEmails, $users);

        if ($isStatus) {
            $this->printStatus($users);

            return $unknownEmails === [] ? self::SUCCESS : self::FAILURE;
        }

        if ($users->isEmpty()) {
            $this->error('No demo accounts matched the target.');

            return self::FAILURE;
        }

        $expiresAt = $isDisable || $isPromote ? null : $this->expiryFromDuration($enableDuration);

        if (! $isDisable && ! $isPromote && $expiresAt === null) {
            $this->error('Invalid duration. Use a positive value such as 30m, 2h, or 1d.');

            return self::FAILURE;
        }

        $results = $users->map(function (User $user) use ($expiresAt, $isPromote): array {
            $user->is_demo_account = ! $isPromote;
            $user->demo_access_expires_at = $expiresAt;
            $user->save();
            $user = $user->refresh();

            if ($isPromote && $user->hasAnyRole(['super_admin', 'admin'])) {
                $this->warn("WARNING: Promoting {$user->email} permanently re-arms full access.");
                $this->warn('This includes PaymentGatewayConfig (Mayar API key) and database backup downloads.');
                $this->warn("Rotate the account's password with: php artisan security:rotate-demo-passwords --email={$user->email}");
            }

            return $this->accountRow($user);
        })->all();

        $this->table(['Email', 'State', 'Expires At', 'Remaining'], $results);
        $action = $isPromote ? 'Promoted' : ($isDisable ? 'Disabled' : 'Enabled');
        $this->info("{$action} demo access for {$users->count()} account(s).");

        return $unknownEmails === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return list<string>
     */
    private function requestedEmails(): array
    {
        return array_values(array_unique(array_filter(
            $this->option('email') ?? [],
            fn (mixed $email): bool => is_string($email) && $email !== '',
        )));
    }

    /**
     * @param  list<string>  $requestedEmails
     * @return Collection<int, User>
     */
    private function targetUsers(array $requestedEmails): Collection
    {
        $query = User::query()->orderBy('email');

        if ($requestedEmails === []) {
            return $query->where('email', 'like', '%@pos.test')->get();
        }

        return $query->whereIn('email', $requestedEmails)->get();
    }

    /**
     * @param  list<string>  $requestedEmails
     * @param  Collection<int, User>  $users
     * @return list<string>
     */
    private function reportUnknownEmails(array $requestedEmails, Collection $users): array
    {
        $unknownEmails = array_values(array_diff($requestedEmails, $users->pluck('email')->all()));

        foreach ($unknownEmails as $email) {
            $this->error("No user found for email: {$email}");
        }

        return $unknownEmails;
    }

    private function expiryFromDuration(mixed $duration): ?Carbon
    {
        if (! is_string($duration) || preg_match('/^(\d+)([mhd])$/i', trim($duration), $matches) !== 1) {
            return null;
        }

        $amount = (int) $matches[1];

        if ($amount < 1) {
            return null;
        }

        return match (strtolower($matches[2])) {
            'm' => now()->addMinutes($amount),
            'h' => now()->addHours($amount),
            'd' => now()->addDays($amount),
        };
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function printStatus(Collection $users): void
    {
        $this->table(
            ['Email', 'State', 'Expires At', 'Remaining'],
            $users->map(fn (User $user): array => $this->accountRow($user))->all(),
        );
        $this->info("Displayed {$users->count()} account(s). No changes made.");
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function accountRow(User $user): array
    {
        $expiresAt = $user->demo_access_expires_at;
        $state = ! $user->is_demo_account
            ? 'Not a demo account'
            : ($user->hasActiveDemoAccess() ? 'Active' : 'Expired/disabled');
        $remaining = $user->hasActiveDemoAccess()
            ? now()->diffForHumans($expiresAt, ['parts' => 2, 'short' => true])
            : '—';

        return [
            $user->email,
            $state,
            $expiresAt?->toDateTimeString() ?? '—',
            $remaining,
        ];
    }
}
