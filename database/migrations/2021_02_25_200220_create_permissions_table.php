<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->primary(['user_id', 'country_id', 'group_id']);

            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('country_id');
            $table->unsignedInteger('group_id');

            $table->unsignedBigInteger('inserted_by')->nullable();
            $table->timestamps();
        });
        
        // To-do: Delete the old training_role_country table 
        // and group and unused country column in users table
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permissions');
    }
}
