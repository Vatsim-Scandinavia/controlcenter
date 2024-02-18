<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::table(Config::get('settings.table'))->insert([
            ['key' => 'divisionApiEnabled', 'value' => 0],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::raw('DELETE FROM ' . Config::get('settings.table') . ' WHERE key = `divisionApiEnabled`');
    }
};
