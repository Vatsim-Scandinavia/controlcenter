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

        // We need to truncate existing data to make the new columns work
        Schema::drop('atc_activities');

        // Add the new columns
        Schema::create('atc_activities', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('area_id');

            $table->double('hours')->default(0);
            $table->timestamp('start_of_grace_period')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
            $table->unique(['user_id', 'area_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::drop('atc_activities');

        Schema::create('atc_activities', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->double('hours')->default(0);
            $table->timestamp('start_of_grace_period')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE')->onUpdate('CASCADE');
        });

    }
};
