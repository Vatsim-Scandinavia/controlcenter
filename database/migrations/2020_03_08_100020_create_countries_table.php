<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        DB::table('countries')->insert([
            ['name' => "Norway", 'contact' => "training-norway@vatsim-scandinavia.org"],
            ['name' => "Sweden", 'contact' => "training-sweden@vatsim-scandinavia.org"],
            ['name' => "Denmark", 'contact' => "training-denmark@vatsim-scandinavia.org"],
            ['name' => "Finland", 'contact' => "training-finland@vatsim-scandinavia.org"],
            ['name' => "Iceland", 'contact' => "training-iceland@vatsim-scandinavia.org"],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('countries');
    }
}
