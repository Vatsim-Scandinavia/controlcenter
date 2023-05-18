<?php

use Illuminate\Database\Migrations\Migration;

class AddNewAcitivtyContactRow extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('settings')->insert([
            ['key' => 'atcActivityContact', 'value' => 'local training staff'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('settings')->where('key', 'atcActivityContact')->delete();
    }
}
