<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_districts', function (Blueprint $table): void {
            $table->uuid('user_id');
            $table->uuid('district_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('district_id')->references('id')->on('districts')->cascadeOnDelete();
            $table->primary(['user_id', 'district_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_districts');
    }
};
