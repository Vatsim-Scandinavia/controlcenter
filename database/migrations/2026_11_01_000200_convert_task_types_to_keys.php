<?php

use App\Tasks\Types\Custom;
use App\Tasks\Types\RatingUpgrade;
use App\Tasks\Types\SoloEndorsement;
use App\Tasks\Types\TheoreticalExam;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Task types used to be stored as fully-qualified class names. The task type
 * registry now addresses types by a stable key. This migration rewrites the
 * existing tasks.type values from class name to key and is fully reversible.
 */
return new class extends Migration
{
    /**
     * @var array<class-string, string> class name => stable key
     */
    private array $map = [
        Custom::class => Custom::KEY,
        RatingUpgrade::class => RatingUpgrade::KEY,
        SoloEndorsement::class => SoloEndorsement::KEY,
        TheoreticalExam::class => TheoreticalExam::KEY,
    ];

    public function up(): void
    {
        foreach ($this->map as $class => $key) {
            DB::table('tasks')->where('type', $class)->update(['type' => $key]);
        }
    }

    public function down(): void
    {
        foreach ($this->map as $class => $key) {
            DB::table('tasks')->where('type', $key)->update(['type' => $class]);
        }
    }
};
