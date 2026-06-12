<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deck_id')->constrained()->cascadeOnDelete();
            $table->enum('mode', ['flashcard','learn','write','match','test'])->default('flashcard');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('total_cards')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('incorrect_count')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('study_sessions'); }
};
