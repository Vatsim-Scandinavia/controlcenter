<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        // Remove the defaults
        Schema::table('votes', function (Blueprint $table) {
            $table->timestamp('end_at')->default(null)->change();
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->timestamp('created_at')->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->timestamp('end_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
        });
    }
};
