<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The free form settings are gone
|--------------------------------------------------------------------------
|
| The table of the free form settings was the declared extension point of the
| optional modules, but nothing in the core ever read it or wrote to it: the
| modules that need their own settings keep them in their own tables.
|
| The migration that created the table is not part of the package anymore, so
| a new installation never creates it. This one cleans the installations that
| already have it, and it does nothing when the table is not there.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sivarcart_settings');
    }

    public function down(): void
    {
        Schema::create('sivarcart_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group', 64)->default('general');
            $table->timestamps();

            $table->index('group');
        });
    }
};
