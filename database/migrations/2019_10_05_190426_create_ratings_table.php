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
            $table->unsignedInteger('vatsim_rating')->nullable();
            $table->unsignedInteger('required_vatsim_rating')->nullable();
            $table->unsignedInteger('country')->nullable();
            $table->string('name', 50);
            $table->string('description');
            $table->boolean('rating_upgrade');
            $table->boolean('available')->default(1);
        });

        DB::table('ratings')->insert([
            ['vatsim_rating' => 2, 'name' => 'S1', 'description' => 'Rating required to sit GND position', 'rating_upgrade' => true, 'available' => false],
            ['vatsim_rating' => 3, 'name' => 'S2', 'description' => 'Rating required to sit TWR position', 'rating_upgrade' => true, 'available' => true],
        ]);

        DB::table('ratings')->insert([
            ['required_vatsim_rating' => 3, 'vatsim_rating' => 4, 'name' => 'S3', 'description' => 'Rating required to sit APP position', 'rating_upgrade' => true],
            ['required_vatsim_rating' => 4, 'vatsim_rating' => 5, 'name' => 'C1', 'description' => 'Rating required to sit ACC position', 'rating_upgrade' => true],
            ['required_vatsim_rating' => 5, 'vatsim_rating' => 7, 'name' => 'C3', 'description' => 'Rating required to sit ACC position', 'rating_upgrade' => true],
            ['required_vatsim_rating' => 7, 'vatsim_rating' => 8, 'name' => 'I1', 'description' => 'Rating required to sit ACC position', 'rating_upgrade' => true],
            ['required_vatsim_rating' => 8, 'vatsim_rating' => 10, 'name' => 'I3', 'description' => 'Rating required to sit ACC position', 'rating_upgrade' => true],
        ]);

        DB::table('ratings')->insert([
            ['required_vatsim_rating' => 3, 'name' => 'MAE ENGM TWR', 'country' => 1, 'description' => 'Major Airport endorsement for tower position', 'rating_upgrade' => false],
            ['required_vatsim_rating' => 3,'name' => 'MAE ENGM APP', 'country' => 1, 'description' => 'Major Airport endorsement for approach position', 'rating_upgrade' => false],
            ['required_vatsim_rating' => 3,'name' => 'MAE EKCH TWR', 'country' => 3, 'description' => 'Major Airport endorsement for tower position', 'rating_upgrade' => false],
            ['required_vatsim_rating' => 3,'name' => 'MAE EKCH APP', 'country' => 3, 'description' => 'Major Airport endorsement for approach position', 'rating_upgrade' => false],
            ['required_vatsim_rating' => 3,'name' => 'MAE ESSA TWR', 'country' => 2, 'description' => 'Major Airport endorsement for tower position', 'rating_upgrade' => false],
            ['required_vatsim_rating' => 3,'name' => 'MAE ESSA APP', 'country' => 2, 'description' => 'Major Airport endorsement for approach position', 'rating_upgrade' => false],
            ['required_vatsim_rating' => 3,'name' => 'MAE EFHK TWR', 'country' => 4, 'description' => 'Major Airport endorsement for tower position', 'rating_upgrade' => false],
            ['required_vatsim_rating' => 3,'name' => 'MAE EFHK APP', 'country' => 4, 'description' => 'Major Airport endorsement for approach position', 'rating_upgrade' => false],
            ['required_vatsim_rating' => 5, 'name' => 'Oceanic BICC', 'country' => 5, 'description' => 'Endorsement for oceanic position', 'rating_upgrade' => false],
            ['required_vatsim_rating' => 5, 'name' => 'Oceanic ENOB', 'country' => 1, 'description' => 'Endorsement for oceanic position', 'rating_upgrade' => false],
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
