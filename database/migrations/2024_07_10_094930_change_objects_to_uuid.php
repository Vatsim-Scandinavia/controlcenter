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
        Schema::table('training_object_attachments', function (Blueprint $table) {
            // Step 1: Add a new UUID column
            $table->uuid('uuid')->first();
        });

        // Step 2: Populate the new uuid column with UUIDs
        DB::table('training_object_attachments')->get()->each(function ($item) {
            DB::table('training_object_attachments')
                ->where('id', $item->id)
                ->update(['uuid' => \Illuminate\Support\Str::uuid()]);
        });

        // Step 3: Remove auto-increment from the 'id' column
        Schema::table('training_object_attachments', function (Blueprint $table) {
            $table->dropPrimary(['id']); // Drop the primary key
            $table->unsignedBigInteger('id')->autoIncrement(false)->change(); // Remove auto-increment
        });

        // Step 4: Drop the 'id' column
        Schema::table('training_object_attachments', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        // Step 5 & 6: Rename 'uuid' column to 'id' and set it as the primary key
        Schema::table('training_object_attachments', function (Blueprint $table) {
            $table->renameColumn('uuid', 'id');
            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Breaking change, but harmless as things continue to work
    }
};