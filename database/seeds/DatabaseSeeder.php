<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        for ($i = 1; $i <= 100; $i++) {
            factory(App\User::class)->create([
                'id' => 10000000 + $i,
            ]);
            factory(App\Handover::class)->create([
                'id' => 10000000 + $i,
            ]);
        }

        for ($i = 1; $i <= rand(50, 100); $i++) {
            factory(App\Training::class)->create();
        }
    }
}
