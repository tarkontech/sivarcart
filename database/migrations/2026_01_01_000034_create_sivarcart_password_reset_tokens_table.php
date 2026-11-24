<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The token of the buyers
|--------------------------------------------------------------------------
|
| The buyer signs in with the guard of the package, so the package keeps the
| token that proves a password reset comes from the buyer.
|
| The table carries the prefix of the package, so it does not collide with the
| table of the staff of the store nor with the one Laravel creates for the
| users of the application.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_password_reset_tokens');
    }
};
