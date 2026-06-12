<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sweatbooks', function (Blueprint $table) {
            $table->dateTime('time_start')->nullable()->after('user_id');
            $table->dateTime('time_end')->nullable()->after('time_start');
        });

        DB::table('sweatbooks')->orderBy('id')->chunkById(500, function ($bookings) {
            foreach ($bookings as $booking) {
                DB::table('sweatbooks')->where('id', $booking->id)->update([
                    'time_start' => Carbon::parse($booking->date)->setTimeFromTimeString($booking->start_at),
                    'time_end' => Carbon::parse($booking->date)->setTimeFromTimeString($booking->end_at),
                ]);
            }
        });

        Schema::table('sweatbooks', function (Blueprint $table) {
            $table->dateTime('time_start')->nullable(false)->change();
            $table->dateTime('time_end')->nullable(false)->change();
            $table->dropColumn(['date', 'start_at', 'end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sweatbooks', function (Blueprint $table) {
            $table->date('date')->nullable()->after('user_id');
            $table->time('start_at')->nullable()->after('date');
            $table->time('end_at')->nullable()->after('start_at');
        });

        DB::table('sweatbooks')->orderBy('id')->chunkById(500, function ($bookings) {
            foreach ($bookings as $booking) {
                DB::table('sweatbooks')->where('id', $booking->id)->update([
                    'date' => Carbon::parse($booking->time_start)->format('Y-m-d'),
                    'start_at' => Carbon::parse($booking->time_start)->format('H:i:s'),
                    'end_at' => Carbon::parse($booking->time_end)->format('H:i:s'),
                ]);
            }
        });

        Schema::table('sweatbooks', function (Blueprint $table) {
            $table->date('date')->nullable(false)->change();
            $table->time('start_at')->nullable(false)->change();
            $table->time('end_at')->nullable(false)->change();
            $table->dropColumn(['time_start', 'time_end']);
        });
    }
};
