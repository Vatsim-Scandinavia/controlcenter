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
        Schema::create('ratings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('vatsim_rating')->unsigned()->nullable();
            $table->string('name', 50);
            $table->string('description');
            $table->boolean('rating_upgrade');
        });

        DB::table('ratings')->insert([
            ['vatsim_rating' => 2, 'name' => 'S1', 'description' => 'Rating required to sit GND position', 'rating_upgrade' => true],
            ['vatsim_rating' => 3, 'name' => 'S2', 'description' => 'Rating required to sit TWR position', 'rating_upgrade' => true],
            ['vatsim_rating' => 4, 'name' => 'S3', 'description' => 'Rating required to sit APP position', 'rating_upgrade' => true],
            ['vatsim_rating' => 5, 'name' => 'C1', 'description' => 'Rating required to sit ACC position', 'rating_upgrade' => true],
            ['vatsim_rating' => 7, 'name' => 'C3', 'description' => 'Rating required to sit ACC position', 'rating_upgrade' => true],
            ['vatsim_rating' => 8, 'name' => 'I1', 'description' => 'Rating required to sit ACC position', 'rating_upgrade' => true],
            ['vatsim_rating' => 10, 'name' => 'I3', 'description' => 'Rating required to sit ACC position', 'rating_upgrade' => true],
        ]);

        DB::table('ratings')->insert([
            ['name' => 'MAE ENGM TWR', 'description' => 'Major Airport endorsement for tower position', 'rating_upgrade' => false],
            ['name' => 'MAE ENGM APP', 'description' => 'Major Airport endorsement for approach position', 'rating_upgrade' => false],
            ['name' => 'MAE EKCH TWR', 'description' => 'Major Airport endorsement for tower position', 'rating_upgrade' => false],
            ['name' => 'MAE EKCH APP', 'description' => 'Major Airport endorsement for approach position', 'rating_upgrade' => false],
            ['name' => 'MAE ESSA TWR', 'description' => 'Major Airport endorsement for tower position', 'rating_upgrade' => false],
            ['name' => 'MAE ESSA APP', 'description' => 'Major Airport endorsement for approach position', 'rating_upgrade' => false],
            ['name' => 'MAE EFHK TWR', 'description' => 'Major Airport endorsement for tower position', 'rating_upgrade' => false],
            ['name' => 'MAE EFHK APP', 'description' => 'Major Airport endorsement for approach position', 'rating_upgrade' => false],
            ['name' => 'Oceanic BICC', 'description' => 'Endorsement for oceanic position', 'rating_upgrade' => false],
            ['name' => 'Oceanic ENOB', 'description' => 'Endorsement for oceanic position', 'rating_upgrade' => false],
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
