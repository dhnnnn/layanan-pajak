<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Trim 4-digit codes ending in 0 to 3 digits (e.g., 2300 -> 230)
        // to match the data in SimpaduTaxPayerRealization table
        DB::statement("UPDATE districts SET simpadu_code = LEFT(simpadu_code, 3) WHERE LENGTH(simpadu_code) = 4 AND simpadu_code LIKE '%0'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed for this data correction
    }
};
