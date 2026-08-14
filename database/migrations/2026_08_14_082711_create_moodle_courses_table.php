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
        Schema::create('moodle_courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('moodle_id')->unique();
            $table->string('short_name');
            $table->string('full_name');
            $table->boolean('enabled')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moodle_courses');
    }
};
