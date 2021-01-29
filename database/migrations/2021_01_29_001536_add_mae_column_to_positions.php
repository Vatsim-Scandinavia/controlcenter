<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMaeColumnToPositions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->unsignedInteger('mae')->nullable()->after('rating');
        });

        // Update endorsed positions
        DB::table('positions')->where('callsign', 'like', 'ENGM_%')->update(['mae' => 1]);
        DB::table('positions')->where('callsign', 'like', 'ESSA_%')->update(['mae' => 1]);
        DB::table('positions')->where('callsign', 'like', 'EKCH_%')->update(['mae' => 1]);
        DB::table('positions')->where('callsign', 'BICC_FSS')->update(['mae' => 1]);
        DB::table('positions')->where('callsign', 'ENOB_CTR')->update(['mae' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn('mae');
        });
    }
}
