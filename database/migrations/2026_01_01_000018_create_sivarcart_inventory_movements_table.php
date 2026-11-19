<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('sivarcart_product_variants')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('type', 32);
            $table->integer('quantity');
            $table->integer('stock_after')->default(0);
            $table->string('reason')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('sivarcart_store_users')->nullOnDelete()->cascadeOnUpdate();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['product_variant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_inventory_movements');
    }
};
