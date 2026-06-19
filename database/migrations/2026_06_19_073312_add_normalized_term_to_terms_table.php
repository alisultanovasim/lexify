<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->string('normalized_term', 500)->nullable()->after('term');
            $table->index(['deck_id', 'normalized_term'], 'idx_terms_deck_normalized');
        });

        // Backfill: normalize existing records in DB
        // REGEXP_REPLACE is available in MySQL 8.0+ (Laragon ships 8.4)
        DB::statement("
            UPDATE terms
            SET normalized_term = LOWER(TRIM(
                REGEXP_REPLACE(
                    term,
                    '^(der|die|das|le|la|les|el|los|las|il|lo|gli|un|une|a|an|the)[[:space:]]+',
                    ''
                )
            ))
        ");
    }

    public function down(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->dropIndex('idx_terms_deck_normalized');
            $table->dropColumn('normalized_term');
        });
    }
};
