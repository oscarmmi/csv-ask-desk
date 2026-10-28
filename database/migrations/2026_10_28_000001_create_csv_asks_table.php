<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('csv_asks', function (Blueprint $table): void {
            $table->id();
            $table->text('question');
            $table->json('generated_filter');
            $table->text('answer');
            $table->unsignedInteger('row_count');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('csv_asks');
    }
};
