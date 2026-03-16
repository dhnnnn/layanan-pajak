<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('months', function (Blueprint $table): void {
            $table->tinyIncrements('id');
            $table->unsignedTinyInteger('number')->unique();
            $table->string('name', 20);
            $table->string('abbreviation', 5);
            $table->string('column_name', 20);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('months');
    }
};
