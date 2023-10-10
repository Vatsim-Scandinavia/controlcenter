<?php

namespace Database\Seeders;

use App\Helpers\FactoryHelper;
use App\Helpers\TrainingStatus;
use App\Models\Endorsement;
use App\Models\Group;
use App\Models\Position;
use App\Models\Rating;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\TrainingReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create the default dev accounts corresponding to VATSIM Connect
        for ($i = 1; $i <= 11; $i++) {
            $name_first = 'Web';
            $name_last = 'X';
            $email = 'auth.dev' . $i . '@vatsim.net';

            $rating_id = 1;
            $group = null;

            switch ($i) {
                case 1:
                    $name_last = 'One';
                    break;
                case 2:
                    $name_last = 'Two';
                    $rating_id = 2;
                    break;
                case 3:
                    $name_last = 'Three';
                    $rating_id = 3;
                    break;
                case 4:
                    $name_last = 'Four';
                    $rating_id = 4;
                    break;
                case 5:
                    $name_last = 'Five';
                    $rating_id = 5;
                    break;
                case 6:
                    $name_last = 'Six';
                    $rating_id = 7;
                    break;
                case 7:
                    $name_last = 'Seven';
                    $rating_id = 8;
                    $group = 3;
                    break;
                case 8:
                    $name_last = 'Eight';
                    $rating_id = 10;
                    $group = 3;
                    break;
                case 9:
                    $name_last = 'Nine';
                    $rating_id = 11;
                    $group = 2;
                    break;
                case 10:
                    $name_first = 'Team';
                    $name_last = 'Web';
                    $rating_id = 12;
                    $email = 'noreply@vatsim.net';
                    $group = 1;
                    break;
                case 11:
                    $name_first = 'Suspended';
                    $name_last = 'User';
                    $rating_id = 0;
                    $email = 'suspended@vatsim.net';
                    break;
            }

            User::factory()->create([
                'id' => 10000000 + $i,
                'email' => $email,
                'first_name' => $name_first,
                'last_name' => $name_last,
                'rating' => $rating_id,
                'rating_short' => FactoryHelper::shortRating($rating_id),
                'rating_long' => FactoryHelper::longRating($rating_id),
                'region' => 'EMEA',
                'division' => 'EUD',
                'subdivision' => 'SCA',
            ])->groups()->attach(Group::find($group), ['area_id' => 1]);
        }

        // Create random Scandinavian users
        for ($i = 12; $i <= 125; $i++) {
            User::factory()->create([
                'id' => 10000000 + $i,
                'region' => 'EMEA',
                'division' => 'EUD',
                'subdivision' => 'SCA',
            ]);
        }

        // Create random users
        for ($i = 126; $i <= 250; $i++) {
            User::factory()->create([
                'id' => 10000000 + $i,
            ]);
        }

        // Populate trainings and other of the Scandinavian users
        for ($i = 1; $i <= rand(100, 125); $i++) {
            $training = Training::factory()->create();
            $training->ratings()->attach(Rating::where('vatsim_rating', '>', 1)->inRandomOrder()->first());

            // Give all non-queued trainings a mentor
            if ($training->status > TrainingStatus::IN_QUEUE->value) {
                $training->mentors()->attach(
                    User::whereHas('groups', function ($query) {
                        $query->where('id', 3);
                    })->inRandomOrder()->first(),
                    ['expire_at' => now()->addYears(5)]
                );
                TrainingReport::factory()->create([
                    'training_id' => $training->id,
                    'written_by_id' => $training->mentors()->inRandomOrder()->first(),
                ]);
            }

            // Give all exam awaiting trainings a solo endorsement
            if ($training->status == TrainingStatus::AWAITING_EXAM->value) {
                if (! Endorsement::where('user_id', $training->user_id)->exists()) {
                    $soloEndorsement = Endorsement::factory()->create([
                        'user_id' => $training->user_id,
                        'type' => 'SOLO',
                        'valid_to' => Carbon::now()->addWeeks(4),
                    ]);

                    // Add position for solo
                    $soloEndorsement->positions()->save(Position::where('rating', '>', 1)->inRandomOrder()->first());
                }

                // And some a exam result
                if ($i % 7 == 0) {
                    TrainingExamination::factory()->create([
                        'training_id' => $training->id,
                        'examiner_id' => User::where('id', '!=', $training->user_id)->inRandomOrder()->first(),
                    ]);
                }
            }
        }
    }
}
