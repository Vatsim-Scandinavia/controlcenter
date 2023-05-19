<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountryColumnToPositions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->unsignedInteger('country')->nullable()->after('fir');
            $table->foreign('country')
                ->references('id')
                ->on('countries')
                ->onDelete('cascade');
        });

        DB::table('positions')->where('fir', 'EKDK')->update(['country' => 1]);
        DB::table('positions')->where('fir', 'EFIN')->update(['country' => 2]);
        DB::table('positions')->where('fir', 'BIRD')->update(['country' => 3]);
        DB::table('positions')->where('fir', 'BGGL')->update(['country' => 3]);
        DB::table('positions')->where('fir', 'ENOR')->update(['country' => 4]);
        DB::table('positions')->where('fir', 'ENOB')->update(['country' => 4]);
        DB::table('positions')->where('fir', 'ESAA')->update(['country' => 5]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
}
