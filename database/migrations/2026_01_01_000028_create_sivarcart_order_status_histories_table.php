<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use SivarcartCore\SivarcartCore\Support\Auth;

return new class extends Migration
{
    public function up(): void
    {
        Auth::assertUserTableExists();

        $user = Auth::newModel();

        Schema::create('sivarcart_order_status_histories', function (Blueprint $table) use ($user): void {
            $table->id();
            $table->foreignId('order_id')->constrained('sivarcart_orders')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('status', 32);
            $table->string('previous_status', 32)->nullable();
            $table->text('note')->nullable();

            $column = $table->foreignIdFor($user, 'user_id');
            $column->nullable();
            $column->constrained()->nullOnDelete()->cascadeOnUpdate();

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
