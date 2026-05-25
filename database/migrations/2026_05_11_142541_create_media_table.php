<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')
            ->nullable()
            ->constrained('restaurants')
            ->cascadeOnDelete();


            $table->foreignId('menu_item_id')->nullable()
                ->constrained('menu_items')
                ->cascadeOnDelete();

            $table->string('file_name');
            $table->string('file_path', 500);
            $table->string('file_url', 500)->nullable();

            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->enum('type', ['image'])->default('image');

            $table->timestamps();
            $table->softDeletes();

            $table->index('menu_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};