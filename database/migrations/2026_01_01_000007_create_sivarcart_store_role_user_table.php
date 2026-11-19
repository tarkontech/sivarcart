<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_store_role_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_user_id')->constrained('sivarcart_store_users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('store_role_id')->constrained('sivarcart_store_roles')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['store_user_id', 'store_role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_store_role_user');
    }
};
