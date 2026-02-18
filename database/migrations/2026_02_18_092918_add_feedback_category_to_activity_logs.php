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
        // Append FEEDBACK to the existing enum.
        // Current enum (from 2022_05_15_095715_add_endorsement_log_type.php) is:
        // ['ACCESS', 'TRAINING', 'BOOKING', 'ENDORSEMENT', 'OTHER']
        if (Schema::hasTable('activity_logs')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql') {
                // MySQL: safely alter the enum in place without dropping the column
                DB::statement("
                    ALTER TABLE activity_logs
                    MODIFY COLUMN category ENUM('ACCESS','TRAINING','BOOKING','ENDORSEMENT','FEEDBACK','OTHER') NULL
                ");
            } elseif (app()->environment('testing', 'local')) {
                // SQLite (tests/local) and other drivers: fall back to drop & recreate,
                // mirroring 2022_05_15_095715_add_endorsement_log_type.php behavior.
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->dropColumn('category');
                });

                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->enum('category', ['ACCESS', 'TRAINING', 'BOOKING', 'ENDORSEMENT', 'FEEDBACK', 'OTHER'])
                        ->nullable()
                        ->after('type');
                });
            } else {
                // In any other non-MySQL, non-testing environment we fail loudly to avoid silent data loss.
                throw new RuntimeException('add_feedback_category_to_activity_logs supports only MySQL outside testing/local.');
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert enum back to the previous set without FEEDBACK.
        if (Schema::hasTable('activity_logs')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql') {
                // Remap any FEEDBACK entries to OTHER before shrinking the enum
                DB::statement("
                    UPDATE activity_logs
                    SET category = 'OTHER'
                    WHERE category = 'FEEDBACK'
                ");

                DB::statement("
                    ALTER TABLE activity_logs
                    MODIFY COLUMN category ENUM('ACCESS','TRAINING','BOOKING','ENDORSEMENT','OTHER') NULL
                ");
            } elseif (app()->environment('testing', 'local')) {
                // For sqlite tests/local, mirror the up() behavior with drop & recreate.
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->dropColumn('category');
                });

                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->enum('category', ['ACCESS', 'TRAINING', 'BOOKING', 'ENDORSEMENT', 'OTHER'])
                        ->nullable()
                        ->after('type');
                });
            } else {
                throw new RuntimeException('add_feedback_category_to_activity_logs down() supports only MySQL outside testing/local.');
            }
        }
    }
};
