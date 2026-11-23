<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The notifications the store sends
|--------------------------------------------------------------------------
|
| The package tells the buyer when the order moves. Which of those emails
| leave the store is a decision of the merchant, so it lives in the database
| and not in the code: this table holds one row per notification the admin
| has changed.
|
| A notification without a row is not disabled, it simply follows the default
| of the configuration. The table only holds what the admin touched.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_notification_settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('notification', 64)->unique();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_notification_settings');
    }
};
