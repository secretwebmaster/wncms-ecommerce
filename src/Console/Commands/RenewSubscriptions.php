<?php

namespace Secretwebmaster\WncmsEcommerce\Console\Commands;

use Illuminate\Console\Command;
use Secretwebmaster\WncmsEcommerce\Facades\PlanManager;

class RenewSubscriptions extends Command
{
    protected $signature = 'wncms-ecommerce:renew-subscriptions';

    protected $description = 'Create renewal orders for subscriptions that reached next_billing_at';

    public function handle(): int
    {
        $count = PlanManager::createRenewalOrders();

        $this->info("Created {$count} renewal order(s).");

        return self::SUCCESS;
    }
}
