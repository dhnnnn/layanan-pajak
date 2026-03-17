<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_realizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tax_type_id');
            $table->uuid('district_id');
            $table->uuid('user_id');
            $table->year('year');
            $table->decimal('january', 15, 2)->default(0);
            $table->decimal('february', 15, 2)->default(0);
            $table->decimal('march', 15, 2)->default(0);
            $table->decimal('april', 15, 2)->default(0);
            $table->decimal('may', 15, 2)->default(0);
            $table->decimal('june', 15, 2)->default(0);
            $table->decimal('july', 15, 2)->default(0);
            $table->decimal('august', 15, 2)->default(0);
            $table->decimal('september', 15, 2)->default(0);
            $table->decimal('october', 15, 2)->default(0);
            $table->decimal('november', 15, 2)->default(0);
            $table->decimal('december', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('tax_type_id')->references('id')->on('tax_types')->cascadeOnDelete();
            $table->foreign('district_id')->references('id')->on('districts')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(
                ['tax_type_id', 'district_id', 'year'],
                'unique_realization',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_realizations');
    }
};
