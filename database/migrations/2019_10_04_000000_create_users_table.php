<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('visiting_controller')->default(false);
            $table->unsignedInteger('country')->nullable(); // Tie a user to a main country, also used by usergroup to show correct FIR.
            $table->unsignedInteger('group')->nullable(); // Used to set usergroup, also set to assign a Training Assistant to a specific country.
            $table->timestamp('last_login');
            $table->rememberToken();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
