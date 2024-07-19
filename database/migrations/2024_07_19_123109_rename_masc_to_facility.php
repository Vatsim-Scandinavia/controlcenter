<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('endorsements')->where('type', 'MASC')->update(['type' => 'FACILITY']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('endorsements')->where('type', 'FACILITY')->update(['type' => 'MASC']);
    }
};
