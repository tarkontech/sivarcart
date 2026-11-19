<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_id')->constrained('sivarcart_payments')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('gateway', 64)->default('wompi');
            $table->string('gateway_transaction_id', 128)->nullable();
            $table->string('gateway_payment_attempt_id', 128)->nullable();
            $table->string('idempotency_key', 191)->unique();
            $table->decimal('amount', 12, 2)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->string('status', 32)->default('pending');
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_production')->default(true);
            $table->integer('result_code')->nullable();
            $table->string('payment_method', 64)->nullable();
            $table->string('authorization_code', 64)->nullable();
            $table->string('merchant_reference', 128)->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index('gateway_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_payment_transactions');
    }
};
