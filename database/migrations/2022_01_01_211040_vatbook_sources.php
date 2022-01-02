<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VatbookSources extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vatbooks', function (Blueprint $table) {
            $table->enum('source', ['CC', 'VATBOOK', 'DISCORD'])->default('CC')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vatbooks', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
}
