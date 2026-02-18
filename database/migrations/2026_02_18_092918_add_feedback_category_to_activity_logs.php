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
            DB::statement("
                ALTER TABLE activity_logs
                MODIFY COLUMN category ENUM('ACCESS','TRAINING','BOOKING','ENDORSEMENT','FEEDBACK','OTHER') NULL
            ");
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
            DB::statement("
                ALTER TABLE activity_logs
                MODIFY COLUMN category ENUM('ACCESS','TRAINING','BOOKING','ENDORSEMENT','OTHER') NULL
            ");
        }
    }
};
