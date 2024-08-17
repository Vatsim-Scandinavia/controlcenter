<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        $driver = Schema::getConnection()->getDriverName();

        if ($driver != 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign('users_country_foreign');
                $table->dropForeign('users_group_foreign');
                $table->dropColumn(['country', 'group']);
            });
        } else {
            // For SQLite, we need to recreate the table without the foreign keys and columns to be dropped
            Schema::create('users_temp', function (Blueprint $table) {
                $table->increments('id');
                $table->dateTime('last_login')->nullable();
                $table->string('remember_token')->nullable();
                $table->boolean('setting_notify_newreport')->default(1);
                $table->boolean('setting_notify_newreq')->default(1);
                $table->boolean('setting_notify_closedreq')->default(1);
                $table->boolean('setting_notify_newexamreport')->default(1);
            });

            DB::statement('
                INSERT INTO users_temp (id, last_login, remember_token, setting_notify_newreport, setting_notify_newreq, setting_notify_closedreq, setting_notify_newexamreport)
                SELECT id, last_login, remember_token, setting_notify_newreport, setting_notify_newreq, setting_notify_closedreq, setting_notify_newexamreport
                FROM users
            ');

            Schema::dropIfExists('users');

            Schema::rename('users_temp', 'users');
        }

        Schema::dropIfExists('training_role_country');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Optionally, you can add code here to reverse the migration
    }
}
