<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('answers', function (Blueprint $table) {
            $table->string('AnswerID', 10)->primary(); 
            $table->string('QuestionID'); // Foreign key to question
            $table->text('answer_text'); // Correct answer
            $table->text('explanation'); // Explanation
            $table->timestamps();

            $table->foreign('QuestionID')->references('QuestionID')->on('questions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('answers');
    }
};
