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
        // Drop the table if it exists (from a previous failed migration)
        Schema::dropIfExists('training_report_template_area');
        
        Schema::create('training_report_template_area', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('training_report_template_id');
            $table->unsignedInteger('area_id');
            $table->timestamps();

            $table->foreign('training_report_template_id', 'trt_area_template_id_fk')->references('id')->on('training_report_templates')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('area_id', 'trt_area_area_id_fk')->references('id')->on('areas')->onUpdate('CASCADE')->onDelete('CASCADE');
            
            $table->unique(['training_report_template_id', 'area_id'], 'trt_area_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_report_template_area');
    }
};
