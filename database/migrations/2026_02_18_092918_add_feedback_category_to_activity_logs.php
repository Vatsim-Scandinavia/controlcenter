<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Append FEEDBACK to the existing enum without dropping the column to avoid data loss.
        // Current enum (from 2022_05_15_095715_add_endorsement_log_type.php) is:
        // ['ACCESS', 'TRAINING', 'BOOKING', 'ENDORSEMENT', 'OTHER']
        if (Schema::hasTable('activity_logs')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql') {
                // MySQL: safely alter the enum in place without losing data
                DB::statement("
                    ALTER TABLE activity_logs
                    MODIFY COLUMN category ENUM('ACCESS','TRAINING','BOOKING','ENDORSEMENT','FEEDBACK','OTHER') NULL
                ");
            } else {
                // SQLite (tests) and other drivers: fall back to drop & recreate,
                // mirroring 2022_05_15_095715_add_endorsement_log_type.php behavior.
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->dropColumn('category');
                });

                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->enum('category', ['ACCESS', 'TRAINING', 'BOOKING', 'ENDORSEMENT', 'FEEDBACK', 'OTHER'])
                        ->nullable()
                        ->after('type');
                });
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
        // Revert enum back to the previous set without FEEDBACK, preserving data.
        if (Schema::hasTable('activity_logs')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql') {
                DB::statement("
                    ALTER TABLE activity_logs
                    MODIFY COLUMN category ENUM('ACCESS','TRAINING','BOOKING','ENDORSEMENT','OTHER') NULL
                ");
            } else {
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->dropColumn('category');
                });

                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->enum('category', ['ACCESS', 'TRAINING', 'BOOKING', 'ENDORSEMENT', 'OTHER'])
                        ->nullable()
                        ->after('type');
                });
            }
        }
    }
};
