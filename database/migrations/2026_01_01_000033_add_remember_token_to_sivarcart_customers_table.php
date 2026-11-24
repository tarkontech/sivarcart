<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The token of the session of a buyer
|--------------------------------------------------------------------------
|
| The buyer signs in with the guard of the package, so the package needs the
| column that holds the token of the "remember me" cookie, the same way the
| table of the users of an application holds it.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sivarcart_customers', function (Blueprint $table): void {
            $table->string('remember_token', 100)->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('sivarcart_customers', function (Blueprint $table): void {
            $table->dropColumn('remember_token');
        });
    }
};
