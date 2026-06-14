<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Upgrade the legacy activity_logs table in place to the spatie/laravel-activitylog
     * schema, preserving the existing rows. Rather than create a parallel table and copy
     * data across, the new columns are added alongside the old ones, the legacy data is
     * mapped onto them, and the obsolete columns are dropped:
     *
     *   type     -> level        (lower-cased)
     *   category -> log_name     (lower-cased)
     *   message  -> description  (renamed, keeps its content)
     *   user_id  -> causer_id / causer_type
     */
    public function up(): void
    {
        // Add the new spatie columns alongside the legacy ones.
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('log_name')->nullable()->after('id')->index();
            $table->string('event')->nullable();
            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');
            $table->json('attribute_changes')->nullable();
            $table->json('properties')->nullable();
            $table->string('level')->default('info');
            $table->timestamp('updated_at')->nullable();
        });

        // Preserve the existing message content by renaming the column.
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->renameColumn('message', 'description');
        });

        // Map the legacy columns onto the new schema before dropping them.
        DB::table('activity_logs')->update([
            'level' => DB::raw('LOWER(type)'),
            'updated_at' => DB::raw('created_at'),
        ]);
        DB::table('activity_logs')->whereNotNull('category')->update([
            'log_name' => DB::raw('LOWER(category)'),
        ]);
        DB::table('activity_logs')->whereNotNull('user_id')->update([
            'causer_id' => DB::raw('user_id'),
            'causer_type' => User::class,
        ]);

        // Remove the legacy foreign key first; on SQLite this rebuilds the table,
        // which must happen before user_id can be dropped.
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Drop the legacy columns now that their data has been carried over.
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['type', 'category', 'user_id']);
        });

        // Ordering for the admin view and the retention/scrub queries.
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    /**
     * Restore the legacy activity_logs schema, mapping the spatie columns back onto
     * the original ones. The richer spatie context (subject, properties, ...) cannot
     * be represented in the legacy schema and is lost on rollback.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->enum('type', ['DEBUG', 'INFO', 'WARNING', 'DANGER'])->default('INFO')->after('id');
            $table->enum('category', ['ACCESS', 'TRAINING', 'BOOKING', 'ENDORSEMENT', 'OTHER'])->nullable()->after('type');
            $table->foreignId('user_id')->nullable()->after('category')->constrained()->cascadeOnDelete();
        });

        DB::table('activity_logs')->update([
            'type' => DB::raw('UPPER(level)'),
            'user_id' => DB::raw('causer_id'),
        ]);
        DB::table('activity_logs')->whereNotNull('log_name')->update([
            'category' => DB::raw('UPPER(log_name)'),
        ]);

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->renameColumn('description', 'message');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('subject');
            $table->dropIndex('causer');
            $table->dropIndex(['log_name']);
            $table->dropIndex(['created_at']);
            $table->dropColumn([
                'log_name', 'event', 'subject_type', 'subject_id',
                'causer_type', 'causer_id', 'attribute_changes', 'properties',
                'level', 'updated_at',
            ]);
        });
    }
};
