<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number', 50)->unique();

            $table->foreignId('customer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('restaurant_id')
                ->constrained('restaurants')
                ->cascadeOnDelete();

            $table->foreignId('customer_address_id')
                ->nullable()
                ->constrained('customer_addresses')
                ->nullOnDelete();

            $table->foreignId('service_area_id')
                ->nullable()
                ->constrained('service_areas')
                ->nullOnDelete();

            $table->text('delivery_address');
            // $table->string('delivery_city', 150);
            // $table->string('delivery_postal_code', 20);
            // $table->string('delivery_country', 100)->default('Germany');
            $table->decimal('delivery_latitude', 10, 8);
            $table->decimal('delivery_longitude', 11, 8);

            $table->text('customer_note')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'auto_rejected',
                'requested',
                'paid',
                'not_paid','cancelled',
            ])->default('pending');

            $table->enum('payment_status', [
                'unpaid',
                'paid',
                'not_paid',
            ])->default('unpaid');

            $table->enum('loss_status', [
                'none',
                'loss',
            ])->default('none');

            $table->decimal('items_total', 10, 2)->default(0);
            $table->decimal('delivery_cost', 10, 2)->default(0);
            $table->decimal('order_total', 10, 2)->default(0);

            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('loss_amount', 10, 2)->default(0);

            $table->timestamp('pending_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('auto_rejected_at')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('not_paid_at')->nullable();

            $table->timestamp('expires_at')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('payment_updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('admin_rejection_reason')->nullable();
            $table->text('admin_payment_note')->nullable();
            $table->text('loss_reason')->nullable();

            $table->timestamp('estimated_delivery_time')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('payment_status');
            $table->index('loss_status');
            $table->index('created_at');
            $table->index(['created_at', 'status']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['restaurant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};