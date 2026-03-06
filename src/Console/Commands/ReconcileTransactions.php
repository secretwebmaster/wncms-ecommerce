<?php

namespace Secretwebmaster\WncmsEcommerce\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Secretwebmaster\WncmsEcommerce\Models\Order;

class ReconcileTransactions extends Command
{
    protected $signature = 'wncms-ecommerce:reconcile-transactions
        {--gateway= : Filter by payment gateway slug}
        {--date-from= : Start date (Y-m-d)}
        {--date-to= : End date (Y-m-d)}
        {--json : Output machine-readable JSON summary}';

    protected $description = 'Reconcile order and transaction states to detect payment drift';

    public function handle(): int
    {
        $query = Order::query()->with('transactions', 'payment_gateway');

        $gateway = trim((string) $this->option('gateway'));
        if ($gateway !== '') {
            $query->whereHas('payment_gateway', function ($q) use ($gateway) {
                $q->where('slug', $gateway);
            });
        }

        $dateFrom = $this->parseDateOption('date-from');
        $dateTo = $this->parseDateOption('date-to');
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom->startOfDay());
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo->endOfDay());
        }

        $orders = $query->get();

        $summary = [
            'total_orders' => $orders->count(),
            'mismatch_paid_without_succeeded_txn' => 0,
            'mismatch_pending_with_succeeded_txn' => 0,
            'mismatch_failed_without_failed_txn' => 0,
            'checked_at' => now()->toDateTimeString(),
            'gateway' => $gateway !== '' ? $gateway : null,
            'date_from' => $dateFrom?->toDateString(),
            'date_to' => $dateTo?->toDateString(),
        ];

        $mismatches = [];

        foreach ($orders as $order) {
            $hasSucceeded = $order->transactions->contains(fn ($txn) => (string) $txn->status === 'succeeded');
            $hasFailed = $order->transactions->contains(fn ($txn) => (string) $txn->status === 'failed');

            if (in_array((string) $order->status, ['paid', 'completed'], true) && !$hasSucceeded) {
                $summary['mismatch_paid_without_succeeded_txn']++;
                $mismatches[] = $this->buildMismatchRow($order, 'paid/completed without succeeded transaction');
            }

            if ((string) $order->status === 'pending_payment' && $hasSucceeded) {
                $summary['mismatch_pending_with_succeeded_txn']++;
                $mismatches[] = $this->buildMismatchRow($order, 'pending_payment with succeeded transaction');
            }

            if ((string) $order->status === 'failed' && !$hasFailed && !$hasSucceeded) {
                $summary['mismatch_failed_without_failed_txn']++;
                $mismatches[] = $this->buildMismatchRow($order, 'failed without failed/succeeded transaction');
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'summary' => $summary,
                'mismatches' => $mismatches,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        $this->info('Reconciliation summary');
        $this->table(
            ['metric', 'value'],
            collect($summary)->map(fn ($value, $metric) => [$metric, (string) ($value ?? '')])->values()->all()
        );

        if (!empty($mismatches)) {
            $this->warn('Detected mismatches');
            $this->table(['order_id', 'order_slug', 'order_status', 'gateway', 'issue'], array_slice($mismatches, 0, 50));
            if (count($mismatches) > 50) {
                $this->line('... truncated, use --json for full output');
            }
        } else {
            $this->info('No mismatches found.');
        }

        return self::SUCCESS;
    }

    protected function parseDateOption(string $option): ?Carbon
    {
        $value = trim((string) $this->option($option));
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            $this->warn("Invalid {$option}: {$value}, ignoring.");
            return null;
        }
    }

    protected function buildMismatchRow(Order $order, string $issue): array
    {
        return [
            'order_id' => (string) $order->id,
            'order_slug' => (string) $order->slug,
            'order_status' => (string) $order->status,
            'gateway' => (string) ($order->payment_gateway?->slug ?: $order->payment_method),
            'issue' => $issue,
        ];
    }
}
