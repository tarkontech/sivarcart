<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_order_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('sivarcart_orders')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('status', 32);
            $table->string('previous_status', 32)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('sivarcart_store_users')->nullOnDelete()->cascadeOnUpdate();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_order_status_histories');
    }
};
