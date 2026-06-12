<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('term_examples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->text('sentence');
            $table->text('translation')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('term_examples'); }
};
