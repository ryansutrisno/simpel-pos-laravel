<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RotateDemoPasswords extends Command
{
    protected $signature = 'security:rotate-demo-passwords
        {--email=* : Email address to target; repeat for multiple accounts}
        {--dry-run : List targeted accounts without changing passwords}
        {--force : Allow password rotation in production}';

    protected $description = 'Rotate passwords for POS demo accounts';

    public function handle(): int
    {
        $requestedEmails = $this->requestedEmails();
        $isDryRun = (bool) $this->option('dry-run');

        if (app()->isProduction() && ! $isDryRun && ! (bool) $this->option('force')) {
            $this->error('Password rotation is blocked in production unless you pass --force.');

            return self::FAILURE;
        }

        $users = $this->targetUsers($requestedEmails);
        $unknownEmails = $this->reportUnknownEmails($requestedEmails, $users);

        if ($isDryRun) {
            $this->table(
                ['Email'],
                $users->map(fn (User $user): array => [$user->email])->all(),
            );
            $this->info("Dry run complete. {$users->count()} account(s) would be updated.");

            return $unknownEmails === [] ? self::SUCCESS : self::FAILURE;
        }

        $results = $users->map(function (User $user): array {
            $password = Str::password(32);

            $user->password = $password;
            $user->save();

            return [$user->email, $password];
        })->all();

        $this->table(['Email', 'New Password'], $results);
        $this->info("Updated {$users->count()} account(s).");

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
}
