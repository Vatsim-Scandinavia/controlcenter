<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterEndorsementPivot extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('rating_user');
        Schema::create('endorsement_rating', function (Blueprint $table) {
            $table->unsignedBigInteger('endorsement_id');
            $table->unsignedInteger('rating_id');

            $table->foreign('endorsement_id')->references('id')->on('endorsements')->onDelete('cascade');
            $table->foreign('rating_id')->references('id')->on('ratings')->onDelete('cascade');
        });

        Schema::create('endorsement_position', function (Blueprint $table) {
            $table->unsignedBigInteger('endorsement_id');
            $table->unsignedBigInteger('position_id');

            $table->foreign('endorsement_id')->references('id')->on('endorsements')->onDelete('cascade');
            $table->foreign('position_id')->references('id')->on('positions')->onDelete('cascade');
        });

        Schema::create('area_endorsement', function (Blueprint $table) {
            $table->unsignedInteger('area_id');
            $table->unsignedBigInteger('endorsement_id');

            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
            $table->foreign('endorsement_id')->references('id')->on('endorsements')->onDelete('cascade');
        });

        // Re-create the MAE endorsements from pre-upgrade
        $trainings = \App\Models\Training::where('status', -1)->get();

        foreach ($trainings as $training) {
            if (\App\Models\TrainingExamination::where('result', '=', 'PASSED')->where('training_id', $training->id)->exists()) {
                foreach ($training->ratings as $rating) {
                    if ($rating->vatsim_rating == null) {
                        $endorsement = new \App\Models\Endorsement();
                        $endorsement->user_id = $training->user->id;
                        $endorsement->type = 'MASC';
                        if (isset($training->closed_at)) {
                            $endorsement->valid_from = $training->closed_at->format('Y-m-d H:i:s');
                        } else {
                            $endorsement->valid_from = now()->format('Y-m-d H:i:s');
                        }
                        $endorsement->valid_to = null;
                        $endorsement->issued_by = null;
                        $endorsement->save();

                        $endorsement->ratings()->save(\App\Models\Rating::find($rating->id));
                    }
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
}
