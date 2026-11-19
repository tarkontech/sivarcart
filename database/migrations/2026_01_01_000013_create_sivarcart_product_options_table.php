<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_product_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('sivarcart_products')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('name');
            $table->string('type', 32)->default('text');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_product_options');
    }
};
