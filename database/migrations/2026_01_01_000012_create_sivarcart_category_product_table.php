<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_category_product', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('sivarcart_categories')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->constrained('sivarcart_products')->cascadeOnDelete()->cascadeOnUpdate();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['category_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_category_product');
    }
};
