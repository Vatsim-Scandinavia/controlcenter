<?php

use anlutro\LaravelSettings\Facade as Setting;
use App\Models\AtcActivity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('atc_activities', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->double('hours')->default(0);
            $table->timestamp('start_of_grace_period')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE')->onUpdate('CASCADE');
        });

        // Re-generate grace periods
        $trainings = DB::table('trainings')
            ->where('closed_at', '>=', now()->subMonths(Setting::get('atcActivityGracePeriod', 12))->toDateTimeString())
            ->where('type', '<=', 4)
            ->where('status', -1)
            ->orderBy('closed_at', 'asc')->get();

        foreach ($trainings as $training) {
            try {
                $activity = AtcActivity::findOrFail($training->user_id);
                $activity->start_of_grace_period = $training->closed_at;
                $activity->save();
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                AtcActivity::create([
                    'user_id' => $training->user_id,
                    'hours' => 0,
                    'start_of_grace_period' => $training->closed_at,
                ]);
            }

            // Set user as active
            $training->user->atc_active = true;
            $training->user->save();
        }

        Schema::drop('atc_activity');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('atc_activity', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->double('atc_hours');
            $table->string('favourite_position')->nullable()->default(null);
            $table->boolean('inside_grace_period')->default(false);
            $table->timestamp('valid_until')->default(\Illuminate\Support\Facades\DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE')->onUpdate('CASCADE');
        });
        Schema::drop('atc_activities');
    }
};
