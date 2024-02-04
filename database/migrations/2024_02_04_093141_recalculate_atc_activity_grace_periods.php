<?php

use anlutro\LaravelSettings\Facade as Setting;
use App\Models\AtcActivity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Re-generate grace periods
        $trainings = DB::table('trainings')
            ->where('closed_at', '>=', now()->subMonths(Setting::get('atcActivityGracePeriod', 12))->toDateTimeString())
            ->where('type', '<=', 4)
            ->where('status', -1)
            ->orderBy('closed_at', 'asc')->get();

        foreach ($trainings as $training) {
            try {
                $activity = AtcActivity::where('user_id', $training->user_id)->where('area_id', $training->area_id)->firstOrFail();
                $activity->start_of_grace_period = $training->closed_at;
                $activity->save();
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                AtcActivity::create([
                    'user_id' => $training->user_id,
                    'area_id' => $training->area_id,
                    'hours' => 0,
                    'start_of_grace_period' => $training->closed_at,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Breaking change
    }
};
