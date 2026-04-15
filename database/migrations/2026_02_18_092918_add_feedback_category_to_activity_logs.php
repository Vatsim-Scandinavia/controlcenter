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
            $connection = Schema::getConnection();
            $driver = $connection->getDriverName();
            $connectionName = $connection->getName();

            if ($driver === 'mysql') {
                // MySQL: safely alter the enum in place without dropping the column
                DB::statement("
                    ALTER TABLE activity_logs
                    MODIFY COLUMN category ENUM('ACCESS','TRAINING','BOOKING','ENDORSEMENT','FEEDBACK','OTHER') NULL
                ");
            } elseif ($driver === 'sqlite' && $connectionName === 'sqlite-testing') {
                // Test suite (sqlite-testing): drop & recreate, mirroring the 2022 migration pattern.
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->dropColumn('category');
                });

                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->enum('category', ['ACCESS', 'TRAINING', 'BOOKING', 'ENDORSEMENT', 'FEEDBACK', 'OTHER'])
                        ->nullable()
                        ->after('type');
                });
            } else {
                // Any other non-MySQL environment is not supported to avoid accidental data loss.
                throw new RuntimeException('add_feedback_category_to_activity_logs supports only MySQL or sqlite-testing.');
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
            $connection = Schema::getConnection();
            $driver = $connection->getDriverName();
            $connectionName = $connection->getName();

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
            } elseif ($driver === 'sqlite' && $connectionName === 'sqlite-testing') {
                // sqlite-testing rollback: mirror the up() behavior with drop & recreate.
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->dropColumn('category');
                });

                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->enum('category', ['ACCESS', 'TRAINING', 'BOOKING', 'ENDORSEMENT', 'OTHER'])
                        ->nullable()
                        ->after('type');
                });
            } else {
                throw new RuntimeException('add_feedback_category_to_activity_logs down() supports only MySQL or sqlite-testing.');
            }
        }
    }
};
