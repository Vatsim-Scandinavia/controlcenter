<?php

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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->text('feedback');
            $table->unsignedBigInteger('submitter_user_id');
            $table->unsignedBigInteger('reference_user_id')->nullable();
            $table->unsignedBigInteger('reference_position_id')->nullable();
            $table->boolean('forwarded')->default(false);
            $table->timestamps();

            $table->foreign('submitter_user_id')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('reference_user_id')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('reference_position_id')->references('id')->on('positions')->onUpdate('CASCADE')->onDelete('SET NULL');
        });

        Schema::table('settings', function (Blueprint $table) {
            DB::table(Config::get('settings.table'))->insert([
                ['key' => 'feedbackEnable', 'value' => true],
                ['key' => 'feedbackForwardEmail', 'value' => false],
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('feedback');

        Schema::table('settings', function (Blueprint $table) {
            DB::table(Config::get('settings.table'))->where('key', 'feedbackEnable')->delete();
            DB::table(Config::get('settings.table'))->where('key', 'feedbackForwardEmail')->delete();
        });
    }
};
