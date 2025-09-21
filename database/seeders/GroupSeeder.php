<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Group::updateOrCreate(['id' => 1], ['name' => 'Admin', 'description' => 'Administrator']);
        Group::updateOrCreate(['id' => 2], ['name' => 'Moderator', 'description' => 'Moderator']);
        Group::updateOrCreate(['id' => 3], ['name' => 'Mentor', 'description' => 'Mentor']);
    }
}
