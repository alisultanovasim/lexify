<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropForeign(['deck_id']);
            $table->json('deck_ids')->nullable()->after('deck_id');
        });

        DB::statement('ALTER TABLE study_sessions MODIFY COLUMN deck_id BIGINT UNSIGNED NULL');

        Schema::table('study_sessions', function (Blueprint $table) {
            $table->foreign('deck_id')->references('id')->on('decks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropForeign(['deck_id']);
            $table->dropColumn('deck_ids');
        });

        DB::statement('ALTER TABLE study_sessions MODIFY COLUMN deck_id BIGINT UNSIGNED NOT NULL');

        Schema::table('study_sessions', function (Blueprint $table) {
            $table->foreign('deck_id')->references('id')->on('decks')->cascadeOnDelete();
        });
    }
};
