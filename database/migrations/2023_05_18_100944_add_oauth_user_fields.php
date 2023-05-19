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
        Schema::table('users', function (Blueprint $table) {

            if (Schema::getConnection()->getDriverName() != 'sqlite') {

                $table->string('email', 64)->after('id');

                $table->string('first_name')->after('email');
                $table->string('last_name')->after('first_name');

                $table->tinyInteger('rating')->after('last_name');
                $table->string('rating_short', 3)->after('rating');
                $table->string('rating_long', 24)->after('rating_short');

                $table->string('region', 8)->after('rating_long');
                $table->string('division', 20)->nullable()->after('region');
                $table->string('subdivision', 20)->nullable()->after('division');

                $table->tinyInteger('atc_active')->nullable()->after('subdivision');

                $table->text('access_token')->nullable()->after('remember_token');
                $table->text('refresh_token')->nullable()->after('access_token');
                $table->unsignedBigInteger('token_expires')->nullable()->after('refresh_token');

            } else {

                $table->string('email', 64)->nullable()->after('id');

                $table->string('first_name')->nullable()->after('email');
                $table->string('last_name')->nullable()->after('first_name');

                $table->tinyInteger('rating')->nullable()->after('last_name');
                $table->string('rating_short', 3)->nullable()->after('rating');
                $table->string('rating_long', 24)->nullable()->after('rating_short');

                $table->string('region', 8)->nullable()->after('rating_long');
                $table->string('division', 20)->nullable()->after('region');
                $table->string('subdivision', 20)->nullable()->after('division');

                $table->tinyInteger('atc_active')->nullable()->after('subdivision');

                $table->text('access_token')->nullable()->after('remember_token');
                $table->text('refresh_token')->nullable()->after('access_token');
                $table->unsignedBigInteger('token_expires')->nullable()->after('refresh_token');

            }

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
            $table->dropColumn('email');

            $table->dropColumn('first_name');
            $table->dropColumn('last_name');

            $table->dropColumn('rating');
            $table->dropColumn('rating_short');
            $table->dropColumn('rating_long');

            $table->dropColumn('region');
            $table->dropColumn('division');
            $table->dropColumn('subdivision');

            $table->dropColumn('atc_active');

            $table->dropColumn('access_token');
            $table->dropColumn('refresh_token');
            $table->dropColumn('token_expires');
        });
    }
};
