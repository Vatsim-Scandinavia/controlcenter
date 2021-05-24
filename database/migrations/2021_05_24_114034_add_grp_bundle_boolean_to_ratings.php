<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGrpBundleBooleanToRatings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('area_rating', function (Blueprint $table) {
            $table->boolean('allow_mae_bundling')->nullable()->after('required_vatsim_rating');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('area_rating', function (Blueprint $table) {
            $table->dropColumn('allow_grp_bundling');
        });
    }
}
