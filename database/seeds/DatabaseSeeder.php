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
        for ($i = 1; $i <= 250; $i++) {
            factory(App\User::class)->create([
                'id' => 10000000 + $i,
            ]);
            factory(App\Handover::class)->create([
                'id' => 10000000 + $i,
            ]);
        }

        for ($i = 1; $i <= rand(100, 125); $i++) {
            $training = factory(App\Training::class)->create();

            /*if ($i % 6 == 0) {
                factory(App\SoloEndorsement::class)->create();
            }*/

            if ($i % 7 == 0) {
                $training->mentors()->attach(App\User::where('group', 3)->inRandomOrder()->first(), ['expire_at' => now()->addYears(5)]);
                
                factory(App\TrainingReport::class)->create([
                    'training_id' => $training->id,
                    'written_by_id' => $training->mentors()->inRandomOrder()->first(),
                ]);
            }

            if ($i % 9 == 0) {
                factory(App\TrainingExamination::class)->create([
                    'training_id' => $training->id,
                    'examiner_id' => App\User::where('id', '!=', $training->user_id)->inRandomOrder()->first(),
                ]);
            }
        }
    }
}
