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
        Schema::table('area_rating', function (Blueprint $table) {
            $table->integer('hour_requirement')->nullable()->after('allow_mae_bundling');
            $table->renameColumn('allow_mae_bundling', 'allow_bundling');
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
            $table->dropColumn('hour_requirement');
            $table->renameColumn('allow_bundling', 'allow_mae_bundling');
        });
    }
};
