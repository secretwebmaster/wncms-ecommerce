<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->updatePaymentGateways();
        $this->updatePlans();
        $this->updateProducts();
        $this->updateOrders();
        $this->updateOrderItems();
        $this->updateTransactions();
        $this->updateSubscriptions();
    }

    public function down(): void
    {
        // Forward-only compatibility migration.
    }

    protected function updatePaymentGateways(): void
    {
        if (!Schema::hasTable('payment_gateways')) {
            return;
        }

        Schema::table('payment_gateways', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_gateways', 'driver')) {
                $table->string('driver')->nullable();
            }
            if (!Schema::hasColumn('payment_gateways', 'webhook_secret')) {
                $table->string('webhook_secret')->nullable();
            }
            if (!Schema::hasColumn('payment_gateways', 'return_url')) {
                $table->string('return_url')->nullable();
            }
            if (!Schema::hasColumn('payment_gateways', 'currency')) {
                $table->string('currency', 10)->default('USD');
            }
            if (!Schema::hasColumn('payment_gateways', 'is_sandbox')) {
                $table->boolean('is_sandbox')->default(true);
            }
        });
    }

    protected function updatePlans(): void
    {
        if (!Schema::hasTable('plans')) {
            return;
        }

        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'is_recurring')) {
                $table->boolean('is_recurring')->default(true);
            }
            if (!Schema::hasColumn('plans', 'billing_interval_count')) {
                $table->unsignedInteger('billing_interval_count')->default(1);
            }
            if (!Schema::hasColumn('plans', 'billing_interval')) {
                $table->string('billing_interval')->default('month');
            }
            if (!Schema::hasColumn('plans', 'grace_days')) {
                $table->unsignedInteger('grace_days')->default(3);
            }
            if (!Schema::hasColumn('plans', 'price_amount')) {
                $table->decimal('price_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('plans', 'setup_fee_amount')) {
                $table->decimal('setup_fee_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('plans', 'currency')) {
                $table->string('currency', 10)->default('USD');
            }
            if (!Schema::hasColumn('plans', 'attributes')) {
                $table->json('attributes')->nullable();
            }
        });
    }

    protected function updateProducts(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'sale_type')) {
                $table->string('sale_type')->default('one_time');
            }
            if (!Schema::hasColumn('products', 'currency')) {
                $table->string('currency', 10)->default('USD');
            }
            if (!Schema::hasColumn('products', 'billing_interval_count')) {
                $table->unsignedInteger('billing_interval_count')->nullable();
            }
            if (!Schema::hasColumn('products', 'billing_interval')) {
                $table->string('billing_interval')->nullable();
            }
            if (!Schema::hasColumn('products', 'grace_days')) {
                $table->unsignedInteger('grace_days')->nullable();
            }
            if (!Schema::hasColumn('products', 'attributes')) {
                $table->json('attributes')->nullable();
            }
        });

        $this->safeAddUnique('products', 'slug', 'products_slug_unique');
    }

    protected function updateOrders(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_type')) {
                $table->string('order_type')->default('one_time');
            }
            if (!Schema::hasColumn('orders', 'billing_reason')) {
                $table->string('billing_reason')->nullable();
            }
            if (!Schema::hasColumn('orders', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'currency')) {
                $table->string('currency', 10)->default('USD');
            }
            if (!Schema::hasColumn('orders', 'gateway_reference')) {
                $table->string('gateway_reference')->nullable();
            }
            if (!Schema::hasColumn('orders', 'subscription_id')) {
                $table->unsignedBigInteger('subscription_id')->nullable();
            }
            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'failed_at')) {
                $table->timestamp('failed_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'payload')) {
                $table->json('payload')->nullable();
            }
        });

        $this->safeAddIndex('orders', 'subscription_id', 'orders_subscription_id_index');
    }

    protected function updateOrderItems(): void
    {
        if (!Schema::hasTable('order_items')) {
            return;
        }

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'name')) {
                $table->string('name')->nullable();
            }
            if (!Schema::hasColumn('order_items', 'sku')) {
                $table->string('sku')->nullable();
            }
            if (!Schema::hasColumn('order_items', 'currency')) {
                $table->string('currency', 10)->default('USD');
            }
            if (!Schema::hasColumn('order_items', 'unit_amount')) {
                $table->decimal('unit_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('order_items', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('order_items', 'billing_interval_count')) {
                $table->unsignedInteger('billing_interval_count')->nullable();
            }
            if (!Schema::hasColumn('order_items', 'billing_interval')) {
                $table->string('billing_interval')->nullable();
            }
            if (!Schema::hasColumn('order_items', 'attributes')) {
                $table->json('attributes')->nullable();
            }
        });
    }

    protected function updateTransactions(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'subscription_id')) {
                $table->unsignedBigInteger('subscription_id')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'payment_gateway_id')) {
                $table->unsignedBigInteger('payment_gateway_id')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'type')) {
                $table->string('type')->default('charge');
            }
            if (!Schema::hasColumn('transactions', 'direction')) {
                $table->string('direction')->default('debit');
            }
            if (!Schema::hasColumn('transactions', 'status')) {
                $table->string('status')->default('pending');
            }
            if (!Schema::hasColumn('transactions', 'currency')) {
                $table->string('currency', 10)->default('USD');
            }
            if (!Schema::hasColumn('transactions', 'external_id')) {
                $table->string('external_id')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'processed_at')) {
                $table->timestamp('processed_at')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'payload')) {
                $table->json('payload')->nullable();
            }
        });

        $this->safeAddIndex('transactions', 'subscription_id', 'transactions_subscription_id_index');
        $this->safeAddIndex('transactions', 'payment_gateway_id', 'transactions_payment_gateway_id_index');
        $this->safeAddUnique('transactions', 'external_id', 'transactions_external_id_unique');
    }

    protected function updateSubscriptions(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'payment_gateway_id')) {
                $table->unsignedBigInteger('payment_gateway_id')->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'last_transaction_id')) {
                $table->unsignedBigInteger('last_transaction_id')->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'currency')) {
                $table->string('currency', 10)->default('USD');
            }
            if (!Schema::hasColumn('subscriptions', 'amount')) {
                $table->decimal('amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('subscriptions', 'billing_interval_count')) {
                $table->unsignedInteger('billing_interval_count')->default(1);
            }
            if (!Schema::hasColumn('subscriptions', 'billing_interval')) {
                $table->string('billing_interval')->default('month');
            }
            if (!Schema::hasColumn('subscriptions', 'grace_days')) {
                $table->unsignedInteger('grace_days')->default(3);
            }
            if (!Schema::hasColumn('subscriptions', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'started_at')) {
                $table->timestamp('started_at')->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'current_period_start')) {
                $table->timestamp('current_period_start')->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'current_period_end')) {
                $table->timestamp('current_period_end')->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'next_billing_at')) {
                $table->timestamp('next_billing_at')->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'cancel_at_period_end')) {
                $table->boolean('cancel_at_period_end')->default(false);
            }
            if (!Schema::hasColumn('subscriptions', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'attributes')) {
                $table->json('attributes')->nullable();
            }
        });

        $this->safeAddIndex('subscriptions', 'payment_gateway_id', 'subscriptions_payment_gateway_id_index');
        $this->safeAddIndex('subscriptions', 'last_transaction_id', 'subscriptions_last_transaction_id_index');
    }

    protected function safeAddIndex(string $table, string|array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                $blueprint->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // Index already exists or cannot be created on current platform.
        }
    }

    protected function safeAddUnique(string $table, string|array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                $blueprint->unique($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // Unique index already exists or cannot be created on current platform.
        }
    }
};
