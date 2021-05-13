<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkmailToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('setting_workmail_address', 64)->nullable()->after('remember_token');
            $table->timestamp('setting_workmail_expire')->nullable()->after('setting_workmail_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('setting_workmail_address');
            $table->dropColumn('setting_workmail_expire');
        });
    }
}
