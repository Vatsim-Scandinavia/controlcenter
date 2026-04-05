<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create new table (safe for SQLite and MySQL)
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('role');
            $table->unsignedInteger('area_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'role', 'area_id']);
        });

        // 2. Migrate data from old permissions table
        if (Schema::hasTable('permissions')) {
            $permissions = DB::table('permissions')->get();
            foreach ($permissions as $p) {
                $role = null;
                $area_id = $p->area_id;

                if ($p->group_id == 1) {
                    $role = 'admin';
                    $area_id = null;
                } elseif ($p->group_id == 2) {
                    $role = 'moderator';
                } elseif ($p->group_id == 3) {
                    $role = 'mentor';
                } elseif ($p->group_id == 4) {
                    $role = 'buddy';
                } else {
                    continue;
                } // Unknown group

                DB::table('role_user')->insert([
                    'user_id' => $p->user_id,
                    'role' => $role,
                    'area_id' => $area_id,
                    'created_at' => $p->created_at,
                    'updated_at' => $p->updated_at,
                ]);
            }
            Schema::dropIfExists('permissions');
        }

        // 3. Drop old tables
        Schema::dropIfExists('groups');
    }

    public function down(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('description');
        });

        DB::table('groups')->insert([
            ['id' => 1, 'name' => 'Administrator', 'description' => 'Rank meant for vACC Director, Training Director and technicaians, access to whole system.'],
            ['id' => 2, 'name' => 'Moderator', 'description' => 'Access meant for FIR Director and Training assistants to have full control over trainings and statistics.'],
            ['id' => 3, 'name' => 'Mentor', 'description' => 'Access meant for mentors, to give them mentor-related functionality.'],
            ['id' => 4, 'name' => 'Buddy', 'description' => 'Access meant for buddies, to give them buddy-related functionality.'],
        ]);

        Schema::create('permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('area_id');
            $table->unsignedInteger('group_id');
            $table->unsignedBigInteger('inserted_by')->nullable();
            $table->timestamps();

            $table->primary(['user_id', 'area_id', 'group_id']);
        });

        $roleToGroup = [
            'moderator' => 2,
            'mentor' => 3,
            'buddy' => 4,
            'admin' => 1,
        ];

        // admin rows had area_id nulled in up(); fall back to the first area's id
        $fallbackAreaId = DB::table('areas')->value('id') ?? 1;

        $roleUsers = DB::table('role_user')->get();
        foreach ($roleUsers as $ru) {
            if (! isset($roleToGroup[$ru->role])) {
                continue;
            }

            $groupId = $roleToGroup[$ru->role];
            $areaId = ($ru->role === 'admin') ? $fallbackAreaId : $ru->area_id;

            DB::table('permissions')->insertOrIgnore([
                'user_id' => $ru->user_id,
                'area_id' => $areaId,
                'group_id' => $groupId,
                'inserted_by' => null,
                'created_at' => $ru->created_at,
                'updated_at' => $ru->updated_at,
            ]);
        }

        Schema::dropIfExists('role_user');
    }
};
