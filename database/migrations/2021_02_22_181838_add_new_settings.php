<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table(Config::get('settings.table'))->insert([
            ['key' => 'atcActivityQualificationPeriod', 'value' => 12],
            ['key' => 'atcActivityGracePeriod', 'value' => 12],
            ['key' => 'atcActivityRequirement', 'value' => 10],
            ['key' => 'linkDomain', 'value' => 'vatsim-scandinavia.org'],
            ['key' => 'linkHome', 'value' => 'https://vatsim-scandinavia.org/'],
            ['key' => 'linkJoin', 'value' => 'https://vatsim-scandinavia.org/about/join/'],
            ['key' => 'linkContact', 'value' => 'https://vatsim-scandinavia.org/about/staff/'],
            ['key' => 'linkVisiting', 'value' => 'https://vatsim-scandinavia.org/atc/visiting-controller/'],
            ['key' => 'linkDiscord', 'value' => 'http://discord.vatsim-scandinavia.org'],
            ['key' => 'linkMoodle', 'value' => 'https://moodle.vatsim-scandinavia.org/'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            //
        });
    }
}
