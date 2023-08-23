<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `vatsca_cc`.`api_keys` CHANGE `created_at` `created_at` timestamp NOT NULL DEFAULT (UTC_TIMESTAMP);');
        DB::statement('ALTER TABLE `vatsca_cc`.`votes` CHANGE `end_at` `end_at` timestamp NOT NULL DEFAULT (UTC_TIMESTAMP);');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->timestamp('created_at')->default('CURRENT_TIMESTAMP')->change();
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->timestamp('end_at')->default('CURRENT_TIMESTAMP')->change();
        });
    }
};
