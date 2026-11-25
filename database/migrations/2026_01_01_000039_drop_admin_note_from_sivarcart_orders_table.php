<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The note of the staff is gone
|--------------------------------------------------------------------------
|
| The order carried a note for the staff of the store, but nobody ever wrote
| it: the notes of an order live in the history of its changes, which is
| where the staff of the store read them.
|
| The migration that creates the table still declares the column, and this one
| takes it away, the same way the column of the session of the cart was taken
| away. An installation that already has the package does not lose any row:
| the column was empty.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sivarcart_orders', function (Blueprint $table): void {
            $table->dropColumn('admin_note');
        });
    }

    public function down(): void
    {
        Schema::table('sivarcart_orders', function (Blueprint $table): void {
            $table->text('admin_note')->nullable();
        });
    }
};
