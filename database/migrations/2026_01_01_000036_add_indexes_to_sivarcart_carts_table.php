<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The carts are looked up by buyer and pruned by expiration
|--------------------------------------------------------------------------
|
| The cart of a buyer is found by the buyer that owns it, and the expired
| carts are removed by the date they expire. Both columns are read on every
| visit of the buyer and on every run of the pruning, so both of them get an
| index.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sivarcart_carts', function (Blueprint $table): void {
            $table->index('expires_at');
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('sivarcart_carts', function (Blueprint $table): void {
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['customer_id']);
        });
    }
};
