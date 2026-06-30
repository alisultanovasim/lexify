<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('term_images', function (Blueprint $table) {
            $table->text('original_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('term_images', function (Blueprint $table) {
            $table->string('original_url')->nullable()->change();
        });
    }
};
