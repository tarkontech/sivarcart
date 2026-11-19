<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_product_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('sivarcart_products')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_variant_id')->nullable()->constrained('sivarcart_product_variants')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('path');
            $table->string('disk')->default('public');
            $table->string('alt')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_product_images');
    }
};
