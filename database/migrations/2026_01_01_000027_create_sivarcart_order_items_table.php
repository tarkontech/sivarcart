<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('sivarcart_orders')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->nullable()->constrained('sivarcart_products')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_variant_id')->nullable()->constrained('sivarcart_product_variants')->nullOnDelete()->cascadeOnUpdate();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->json('variant_options')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('line_subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 4)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_order_items');
    }
};
