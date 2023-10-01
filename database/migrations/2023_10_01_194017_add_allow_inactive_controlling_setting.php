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
        Schema::table('settings', function (Blueprint $table) {
            DB::table(Config::get('settings.table'))->insert([
                ['key' => 'atcActivityAllowInactiveControlling', 'value' => 0],
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            DB::table(Config::get('settings.table'))->where('key', 'atcActivityAllowInactiveControlling')->delete();
        });
    }
};
