<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tarkontech\SivarCart\Support\Auth;

return new class extends Migration
{
    public function up(): void
    {
        Auth::assertUserTableExists();

        $user = Auth::newModel();

        Schema::create('sivarcart_inventory_movements', function (Blueprint $table) use ($user): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('sivarcart_product_variants')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('type', 32);
            $table->integer('quantity');
            $table->integer('stock_after')->default(0);
            $table->string('reason')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $column = $table->foreignIdFor($user, 'user_id');
            $column->nullable();
            $column->constrained()->nullOnDelete()->cascadeOnUpdate();

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
