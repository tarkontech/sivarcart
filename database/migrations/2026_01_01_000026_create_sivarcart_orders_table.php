<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('order_number', 32)->unique();
            $table->string('reference', 128)->nullable()->unique();
            $table->foreignId('customer_id')->nullable()->constrained('sivarcart_customers')->nullOnDelete()->cascadeOnUpdate();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 32)->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('payment_status', 32)->default('unpaid');
            $table->string('fulfillment_status', 32)->default('unfulfilled');
            $table->char('currency', 3)->default('USD');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->boolean('tax_inclusive')->default(true);
            $table->string('coupon_code', 64)->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->string('shipping_method_code', 64)->nullable();
            $table->string('shipping_method_name')->nullable();
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('payment_status');
            $table->index('placed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_orders');
    }
};
