<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sivarcart_document_sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 32);
            $table->char('period', 4);
            $table->unsignedBigInteger('number')->default(0);
            $table->timestamps();

            $table->unique(['type', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sivarcart_document_sequences');
    }
};
