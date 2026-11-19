<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_customer_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('sivarcart_customers')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('label', 64)->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('sivarcart_departments')->nullOnDelete()->cascadeOnUpdate();
            $table->string('municipality')->nullable();
            $table->string('city')->nullable();
            $table->text('reference')->nullable();
            $table->string('postal_code', 16)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_billing')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_customer_addresses');
    }
};
