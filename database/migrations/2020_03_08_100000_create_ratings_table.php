<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * Short explanation of the ratings data
         *
         * vatsim_rating is the number that Handover/Connect gives us to distingush ratings
         * required_vatsim_rating is used to set restrictions to which endorsements needs which vatsim_rating
         * country is used to restrict a specific rating to a country
         * available makes the rating possible to apply for, for instance we disable S1, but the possibility will be there if future calls for it.
         */
        Schema::create('ratings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('description', 100);

            $table->unsignedInteger('vatsim_rating')->nullable()->comment('NULL = Endorsement');
        });

        DB::table('ratings')->insert([
            ['vatsim_rating' => 2, 'name' => 'S1', 'description' => 'Rating required to sit GND position'],
            ['vatsim_rating' => 3, 'name' => 'S2', 'description' => 'Rating required to sit TWR position'],
            ['vatsim_rating' => 4, 'name' => 'S3', 'description' => 'Rating required to sit APP position'],
            ['vatsim_rating' => 5, 'name' => 'C1', 'description' => 'Rating required to sit ACC position'],
            ['vatsim_rating' => 7, 'name' => 'C3', 'description' => 'Rating required to sit ACC position'],
            ['vatsim_rating' => 8, 'name' => 'I1', 'description' => 'Rating required to sit ACC position'],
            ['vatsim_rating' => 10, 'name' => 'I3', 'description' => 'Rating required to sit ACC position'],
        ]);

        DB::table('ratings')->insert([
            ['name' => 'MAE ENGM TWR', 'description' => 'Major Airport endorsement for tower position'],
            ['name' => 'MAE ENGM APP', 'description' => 'Major Airport endorsement for approach position'],
            ['name' => 'MAE ESSA TWR', 'description' => 'Major Airport endorsement for tower position'],
            ['name' => 'MAE ESSA APP', 'description' => 'Major Airport endorsement for approach position'],
            ['name' => 'MAE EKCH TWR', 'description' => 'Major Airport endorsement for tower position'],
            ['name' => 'MAE EKCH APP', 'description' => 'Major Airport endorsement for approach position'],
            ['name' => 'Oceanic BICC', 'description' => 'Endorsement for oceanic position'],
            ['name' => 'Oceanic ENOB', 'description' => 'Endorsement for oceanic position'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ratings');
    }
}
