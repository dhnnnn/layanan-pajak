<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maps_discovery_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id')->index();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('subtitle')->nullable();
            $table->string('category')->nullable();
            $table->string('place_id')->nullable();
            $table->text('url')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->unsignedInteger('reviews')->nullable();
            $table->string('price_range')->nullable();
            $table->string('status')->default('potensi_baru');
            $table->string('matched_npwpd')->nullable();
            $table->string('matched_name')->nullable();
            $table->decimal('similarity_score', 5, 4)->default(0);
            $table->string('tax_type_code')->nullable();
            $table->string('district_name')->nullable();
            $table->string('keyword')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maps_discovery_results');
    }
};
