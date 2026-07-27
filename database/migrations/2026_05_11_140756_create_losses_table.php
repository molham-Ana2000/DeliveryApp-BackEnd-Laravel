<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('losses', function (Blueprint $table) {
            $table->id();

           $table->foreignId('order_id')
            ->constrained('orders')
            ->restrictOnDelete();

            $table->foreignId('recorded_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

            $table->decimal('amount', 10, 2);
            $table->text('reason');

            $table->timestamps();

            $table->index('order_id');
            $table->index('recorded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('losses');
    }
};
