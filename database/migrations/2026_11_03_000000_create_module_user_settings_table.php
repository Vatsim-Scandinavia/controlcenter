<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user settings contributed by modules through Registrar::userSettings().
 * Global module settings use the l4-settings store (`modules.<slug>.<key>`);
 * this table holds the per-user variant, keyed by user and the same dotted key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_user_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_user_settings');
    }
};
