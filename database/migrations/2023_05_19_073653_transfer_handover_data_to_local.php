<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        // Lets copy data from Handover to Contol Center to make it work on it's own

        // Only do this if Handover is configured
        if (config('database.connections.mysql-handover.host') && config('database.connections.mysql-handover.host') != '') {
            $handoverDb = DB::connection('mysql-handover');

            $users = User::all();
            foreach ($users as $user) {

                $handoverData = $handoverDb->select('select * from users where id = ?', [$user->id]);
                if (! empty($handoverData)) {
                    $handoverData = $handoverData[0];

                    $user->email = $handoverData->email;
                    $user->first_name = $handoverData->first_name;
                    $user->last_name = $handoverData->last_name;

                    $user->rating = $handoverData->rating;
                    $user->rating_short = $handoverData->rating_short;
                    $user->rating_long = $handoverData->rating_long;

                    $user->region = $handoverData->region;
                    $user->division = $handoverData->division;
                    $user->subdivision = $handoverData->subdivision;

                    $user->atc_active = $handoverData->atc_active;

                    $user->save();

                }

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
        // Breaking change
    }
};
