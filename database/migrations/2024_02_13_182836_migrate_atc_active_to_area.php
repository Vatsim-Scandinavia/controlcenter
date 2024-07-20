<?php

use anlutro\LaravelSettings\Facade as Setting;
use App\Models\AtcActivity;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('atc_activities', function (Blueprint $table) {
            $table->boolean('atc_active')->default(false)->after('start_of_grace_period');
        });

        // Update atc hours as this will basis for the migration
        if (App::environment('production')) {
            Artisan::call('update:atc:hours');
        }

        // Get all atc_active `users` and loop through them and their hours and set the `atc_active` column in `atc_activities` to true if they meet the requirements
        $users = User::where('atc_active', true)->get();
        foreach ($users as $user) {
            $activities = $user->atcActivity;
            if ($activities && $activities->count() > 0) {
                foreach ($activities as $activity) {
                    // Let's deem everyone with hours active, this will be most correct as they might get withdrawn active status later on nightly cron job triggering a notification.
                    if ($activity->hours > 0 || $activity->start_of_grace_period != null && $activity->start_of_grace_period->addMonths((int) Setting::get('atcActivityGracePeriod'))->isFuture()) {
                        $activity->atc_active = true;
                        $activity->save();
                    }
                }
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('atc_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('atc_active')->nullable()->after('subdivision');
        });

        // Loop through all atc_active `atc_activities` and set the `atc_active` column in `users` to true if they meet the requirements
        $activities = AtcActivity::where('atc_active', true)->get();
        foreach ($activities as $activity) {
            if ($activity->atc_active) {
                $activity->user->atc_active = true;
                $activity->user->save();
            }
        }

        Schema::table('atc_activities', function (Blueprint $table) {
            $table->dropColumn('atc_active');
        });

    }
};
