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
        DB::table(Config::get('settings.table'))->insert([
            ['key' => "atcActivityNotifyInactive", 'value' => 1],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::raw('DELETE FROM '.Config::get('settings.table').' WHERE key = `atcActivityNotifyInactive`');
    }
}
