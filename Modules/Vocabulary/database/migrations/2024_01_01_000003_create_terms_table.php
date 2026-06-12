<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deck_id')->constrained()->cascadeOnDelete();
            $table->text('term');
            $table->text('definition');
            $table->string('pronunciation')->nullable();
            $table->enum('part_of_speech', ['noun','verb','adjective','adverb','phrase','other'])->nullable();
            $table->enum('gender', ['der','die','das'])->nullable(); // German
            $table->string('plural_form')->nullable();
            $table->enum('level', ['A1','A2','B1','B2','C1','C2'])->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('terms'); }
};
