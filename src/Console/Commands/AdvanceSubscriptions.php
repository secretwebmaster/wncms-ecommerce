<?php

namespace Secretwebmaster\WncmsEcommerce\Console\Commands;

use Illuminate\Console\Command;
use Secretwebmaster\WncmsEcommerce\Facades\PlanManager;

class AdvanceSubscriptions extends Command
{
    protected $signature = 'wncms-ecommerce:advance-subscriptions';

    protected $description = 'Advance subscription lifecycle states (grace/suspended) for unpaid renewals';

    public function handle(): int
    {
        $result = PlanManager::advanceLifecycleStates();

        $this->info('Subscription lifecycle advance completed.');
        $this->line('to_grace=' . (int) ($result['to_grace'] ?? 0));
        $this->line('to_suspended=' . (int) ($result['to_suspended'] ?? 0));
        $this->line('failed_orders=' . (int) ($result['failed_orders'] ?? 0));

        return self::SUCCESS;
    }
}
