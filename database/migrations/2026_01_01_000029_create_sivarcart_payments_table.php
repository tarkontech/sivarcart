<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained('sivarcart_orders')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('payment_method_id')->nullable()->constrained('sivarcart_payment_methods')->nullOnDelete()->cascadeOnUpdate();
            $table->string('method_code', 64);
            $table->string('gateway', 64)->nullable();
            $table->string('reference', 32)->unique();
            $table->string('status', 32)->default('pending');
            $table->decimal('amount', 12, 2)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->string('gateway_reference', 128)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->unsignedInteger('attempt_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_payments');
    }
};
