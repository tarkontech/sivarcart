<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use SivarcartCore\SivarcartCore\Support\Auth;

/*
|--------------------------------------------------------------------------
| The roles of the staff of the store
|--------------------------------------------------------------------------
|
| The staff are the users of the host application, so the pivot points at
| the model of the "users" provider of "config/auth.php". The column and
| the constraint are derived from that model, so an application whose users
| have another kind of key, or another table, does not need any change here.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Auth::assertUserTableExists();

        $user = Auth::newModel();

        Schema::create('sivarcart_store_role_user', function (Blueprint $table) use ($user): void {
            $table->id();

            $column = $table->foreignIdFor($user, 'user_id');
            $column->nullable();
            $column->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->foreignId('store_role_id')->constrained('sivarcart_store_roles')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['user_id', 'store_role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_store_role_user');
    }
};
