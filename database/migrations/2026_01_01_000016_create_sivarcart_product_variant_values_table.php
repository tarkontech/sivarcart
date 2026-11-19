<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_product_variant_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('sivarcart_product_variants')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_option_value_id')->constrained('sivarcart_product_option_values')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['product_variant_id', 'product_option_value_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_product_variant_values');
    }
};
