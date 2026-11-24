<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The carts of a session are gone
|--------------------------------------------------------------------------
|
| The buyer signs in before buying, so a cart always belongs to a buyer and
| the column that held the session of a guest has no consumer anymore.
|
| The carts that a guest left behind stay in the table: they are empty of
| buyers and nothing finds them anymore, but they are data of the merchant
| and the package does not throw it away on its own. The command that prunes
| the expired carts removes them.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sivarcart_carts', function (Blueprint $table): void {
            // The index has to go before the column: SQLite keeps the index
            // when the column drops and the index points at a column that is
            // not there anymore.
            $table->dropIndex(['session_id']);
            $table->dropColumn('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('sivarcart_carts', function (Blueprint $table): void {
            $table->string('session_id', 128)->nullable();
            $table->index('session_id');
        });
    }
};
