<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('groups')->insert([
            ['id' => 4, 'name' => 'Buddy', 'description' => 'Access meant for buddies, to give them buddy-related functionality.'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('groups')->where('id', 4)->delete();
    }
};
