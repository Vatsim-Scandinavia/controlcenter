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
        Schema::table('positions', function (Blueprint $table) {
            $table->unsignedInteger('required_facility_rating_id')->nullable()->after('mae');
            $table->foreign('required_facility_rating_id')->references('id')->on('ratings')->onDelete('set null');
            $table->dropColumn('mae');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropForeign('positions_required_facility_rating_id_foreign');
            $table->dropColumn('required_facility_rating_id');
            $table->unsignedInteger('mae')->nullable()->after('rating');
        });
    }
};
