<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteOldPermissionSystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('users', function (Blueprint $table) {
            // $table->dropForeign('users_country_foreign');
            // $table->dropForeign('users_group_foreign');

            $table->dropColumn(['country', 'group']);
        });

        Schema::dropIfExists('training_role_country');
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
