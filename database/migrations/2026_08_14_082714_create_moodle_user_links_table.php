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
        Schema::create('moodle_user_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('moodle_user_id')->unique();
            $table->string('moodle_username')->nullable();
            $table->string('moodle_full_name');
            $table->string('match_type', 16);
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moodle_user_links');
    }
};
