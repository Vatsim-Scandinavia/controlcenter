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
        Schema::create('moodle_course_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moodle_course_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('area_id');
            $table->unsignedInteger('rating_id');
            $table->timestamps();

            $table->foreign('area_id')->references('id')->on('areas')->cascadeOnDelete();
            $table->foreign('rating_id')->references('id')->on('ratings')->cascadeOnDelete();
            $table->unique(
                ['moodle_course_id', 'area_id', 'rating_id'],
                'moodle_course_rules_assignment_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moodle_course_rules');
    }
};
