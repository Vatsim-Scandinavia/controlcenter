<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;

class AddExamSheetSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table(Config::get('settings.table'))->insert([
            ['key' => 'trainingExamTemplate', 'value' => ''],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Breaking change
    }
}
