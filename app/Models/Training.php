<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Training extends Model
{

    use HasFactory;

    protected $guarded = [];

    protected $table = 'trainings';

    protected $dates = [
        'started_at',
        'closed_at'
    ];

    /**
     * Get the URL to the training page
     * @return string
     */
    public function path()
    {
        return route('training.show', ['training' => $this->id]);
    }

    /**
     * Update the status of the training. This method will make sure that when updating the status the training that the timestamps are also correctly updated.
     *
     * @param int $newStatus the new status to set
     * @param bool $expiredInterest optional bool this expired an interest request
     * @return void
     */
    public function updateStatus(int $newStatus, bool $expiredInterest = false)
    {
        $oldStatus = $this->fresh()->status;

        if($newStatus != $oldStatus){

            // Training was put back in queue or closed
            if($newStatus == 0){
                $this->update(['started_at' => null, 'closed_at' => null]);
            }

            // If training is as active or complete
            if($newStatus >= 1 || $newStatus == -1){

                // In case someone resurrects a closed training
                if($oldStatus < 0){
                    $this->update(['closed_at' => null]);
                }

                if(!isset($this->started_at)){
                    $this->update(['started_at' => now()]);
                }

                // Expire all related training interest models, as we assume the student is contacted and interested if their training status changes positively.
                $expired = 1;
                if($expiredInterest) $expired = 2;
                TrainingInterest::where([['training_id', $this->id], ['expired', false]])->update(['updated_at' => now(), 'expired' => $expired]);
            }

            // If training is completed or closed
            if($newStatus < 0){
                $this->update(['closed_at' => now()]);

                // Expire all related training interest models, as they will only cause problems if training is re-opened.
                $expired = 1;
                if($expiredInterest) $expired = 2;
                TrainingInterest::where([['training_id', $this->id], ['expired', false]])->update(['updated_at' => now(), 'expired' => $expired]);

                // If paused is unchecked but training is paused, sum up the length and unpause.
                if(isset($this->paused_at)){
                    $this->paused_length = $this->paused_length + Carbon::create($this->paused_at)->diffInSeconds(Carbon::now());
                    $this->update(['paused_at' => null, 'paused_length' => $this->paused_length]);
                }
            }

            $this->update(['status' => $newStatus]);
        }

    }

    /**
     * Get a inline string of ratings associated with a training.
     *
     * @return string
     */
     public function getInlineRatings(){

        $output = "";

        if( is_iterable($ratings = $this->ratings->toArray()) ){
            for( $i = 0; $i < sizeof($ratings); $i++ ){
                if( $i == (sizeof($ratings) - 1) ){
                    $output .= $ratings[$i]["name"];
                } else {
                    $output .= $ratings[$i]["name"] . " + ";
                }
            }
        } else {
            $output .= $ratings["name"];
        }

        return $output;
     }

    /**
     * Get a inline string of ratings associated with a training.
     *
     * @return string
     */
    public function getInlineMentors(){

        $output = "";

        if( is_iterable($mentors = $this->mentors->pluck('name')->toArray()) ){
            for( $i = 0; $i < sizeof($mentors); $i++ ){
                if( $i == (sizeof($mentors) - 1) ){
                    $output .= $mentors[$i];
                } else {
                    $output .= $mentors[$i] . " & ";
                }
            }
        } else {
            $output .= $mentors;
        }

        return $output;
     }

     /**
      * Check if training holds one or multiple MAE specific ratings
      *
      * @return boolean
      */
    public function isMaeTraining(){

        foreach($this->ratings as $rating){
            if($rating->vatsim_rating == NULL) return true;
        }

        return false;

    }

    /**
     * Get the student.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the area of the training
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the training reports for the training.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reports()
    {
        return $this->hasMany(TrainingReport::class);
    }

    /**
     * Get the ratings of the training
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function ratings()
    {
        return $this->belongsToMany(Rating::class);
    }

    /**
     * Get the mentors for the training
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function mentors()
    {
        return $this->belongsToMany(User::class, 'training_mentor')->withPivot('expire_at');
    }

    /**
     * Get training interests of this training
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function interests()
    {
        return $this->hasMany(TrainingInterest::class);
    }

    /**
     * Get the one time link associated with the training
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function onetimelink()
    {
        return $this->hasMany(OneTimeLink::class);
    }
}
