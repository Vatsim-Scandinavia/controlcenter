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
        Schema::create('moodle_enrolments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moodle_course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moodle_user_link_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();

            $table->unique(['training_id', 'moodle_course_id']);
            $table->index(['status', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moodle_enrolments');
    }
};
