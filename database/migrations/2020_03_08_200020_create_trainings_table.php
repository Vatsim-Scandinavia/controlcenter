<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('type')->default(1)->comment('1=Standard, 2=Refresh, 3=Transfer, 4=Fast Track, 5=Familiarisation');
            $table->tinyInteger('status')->default(0)->comment('-4: Closed by system, -3: Closed on student’s request, -2: Closed on TA request, -1: Completed, 0: In queue, 1: Pre-training, 2: Active training, 3: Awaiting exam');
            $table->unsignedInteger('country_id');
            $table->text('notes')->nullable();
            $table->text('motivation');
            $table->boolean('english_only_training');
            $table->tinyInteger('experience')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->unsignedInteger('paused_length')->default(0);
            $table->string('closed_reason')->nullable();
            $table->timestamps();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('country_id')->references('id')->on('countries');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trainings');
    }
}
