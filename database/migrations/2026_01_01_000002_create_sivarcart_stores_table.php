<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_stores', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('whatsapp', 32)->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('sivarcart_departments')->nullOnDelete()->cascadeOnUpdate();
            $table->string('municipality')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 16)->nullable();
            $table->char('country_code', 2)->default('SV');
            $table->char('phone_country_code', 4)->default('503');
            $table->char('currency', 3)->default('USD');
            $table->string('timezone')->default('America/El_Salvador');
            $table->string('locale', 10)->default('es_SV');
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->boolean('prices_include_tax')->default(true);
            $table->decimal('default_tax_rate', 5, 4)->default(0.13);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->string('order_number_prefix', 16)->default('SIV');
            $table->string('payment_number_prefix', 16)->default('PAY');
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_stores');
    }
};
