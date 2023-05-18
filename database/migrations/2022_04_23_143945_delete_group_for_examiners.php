<?php

use Illuminate\Database\Migrations\Migration;

class DeleteGroupForExaminers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('groups')->where('id', 4)->delete();
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
