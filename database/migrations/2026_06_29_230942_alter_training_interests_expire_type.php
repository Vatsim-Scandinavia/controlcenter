<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // 1. Drop old boolean default
            DB::statement('ALTER TABLE training_interests ALTER COLUMN expired DROP DEFAULT');

            // 2. Coerce type to smallint
            DB::statement('ALTER TABLE training_interests ALTER COLUMN expired TYPE smallint USING (CASE WHEN expired THEN 1 ELSE 0 END)::smallint');

            // 3. Apply new smallint default
            DB::statement('ALTER TABLE training_interests ALTER COLUMN expired SET DEFAULT 0');
        } else {
            Schema::table('training_interests', function (Blueprint $table): void {
                $table->unsignedTinyInteger('expired')->default(0)->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // 1. Drop smallint default
            DB::statement('ALTER TABLE training_interests ALTER COLUMN expired DROP DEFAULT');

            // 2. Coerce type back to boolean
            DB::statement('ALTER TABLE training_interests ALTER COLUMN expired TYPE boolean USING (expired <> 0)');

            // 3. Apply old boolean default
            DB::statement('ALTER TABLE training_interests ALTER COLUMN expired SET DEFAULT FALSE');
        } else {
            Schema::table('training_interests', function (Blueprint $table): void {
                $table->boolean('expired')->default(false)->change();
            });
        }
    }
};
