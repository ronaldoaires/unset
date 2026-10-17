<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AccountClosingService;
use Illuminate\Console\Command;

class PurgeClosedAccounts extends Command
{
    protected $signature = 'accounts:purge';

    protected $description = 'Closes the accounts whose deletion grace period has ended';

    public function handle(AccountClosingService $closing): int
    {
        $deleted    = 0;
        $anonymized = 0;

        User::dueForDeletion()
            ->where('role', User::ROLE_USER)
            ->lazyById(100)
            ->each(function (User $user) use ($closing, &$deleted, &$anonymized) {
                // A subscription started during the grace period: leave the account alone.
                if ($closing->hasLiveSubscription($user)) {
                    $this->warn("Skipped {$user->id}: live subscription.");

                    return;
                }

                $closing->close($user) === AccountClosingService::DELETED
                    ? $deleted++
                    : $anonymized++;
            });

        $this->info("Deleted: {$deleted}. Anonymized: {$anonymized}.");

        return self::SUCCESS;
    }
}