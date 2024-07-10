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
            // Add a new UUID column
            $table->uuid('uuid')->first(); // Adding it as the first column for convenience
        });

        // Assuming all your rows have unique IDs and you can iterate through them
        // Populate the new uuid column with UUIDs
        DB::table('training_object_attachments')->get()->each(function ($item) {
            DB::table('training_object_attachments')
                ->where('id', $item->id)
                ->update(['uuid' => \Illuminate\Support\Str::uuid()]);
        });

        Schema::table('training_object_attachments', function (Blueprint $table) {
            // Drop the old primary key and the old 'id' column
            $table->dropPrimary();
            $table->dropColumn('id');

            // Rename the 'uuid' column to 'id'
            $table->renameColumn('uuid', 'id');

            // Set the new 'id' column as the primary key
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
