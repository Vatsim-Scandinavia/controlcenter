<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCountryToAreas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('countries', 'areas');
        Schema::rename('country_rating', 'area_rating');

        Schema::table('area_rating', function (Blueprint $table) {
            $table->renameColumn('country_id', 'area_id');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->renameColumn('country_id', 'area_id');
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->renameColumn('country', 'area');
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->renameColumn('country_id', 'area_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Let's just not support rollback, it's a mayor breaking update either way.
    }
}
