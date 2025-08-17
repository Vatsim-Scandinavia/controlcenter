<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('atc_activities', function (Blueprint $table) {
            $table->timestamp('last_inactivity_warning')->nullable()->after('atc_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('atc_activities', function (Blueprint $table) {
            $table->dropColumn('last_inactivity_warning');
        });
    }
};
