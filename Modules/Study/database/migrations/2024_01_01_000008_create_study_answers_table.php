<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('study_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_session_id')->constrained('study_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('study_answers'); }
};
