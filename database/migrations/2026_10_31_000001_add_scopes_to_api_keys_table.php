<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an optional set of API scopes to each key. A null value preserves the
     * existing behaviour: the key is unscoped and is accepted on any route that
     * does not require a scope.
     */
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->json('scopes')->nullable()->after('read_only');
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn('scopes');
        });
    }
};
