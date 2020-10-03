<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{

    const CONTINUED_INTEREST_NOTIFICATION_LOG_TABLE = 'training_interest_log';

    protected $guarded = [];

    protected $dates = [
        'started_at',
        'closed_at'
    ];

    /**
     * Get the URL to the training page
     *
     * @return string
     */
    public function path()
    {
        return route('training.show', ['training' => $this->id]);
    }

    /**
     * Update the status of the training.
     * This method will make sure that when updating the status the training
     * that the timestamps are also correctly updated.
     *
     * @param int $status
     */
    public function updateStatus(int $newStatus)
    {
        $oldStatus = $this->fresh()->status;

        if($newStatus != $oldStatus){

            // Training was put back in queue or closed
            if($newStatus == 0){
                $this->update(['started_at' => null, 'closed_at' => null]);
            }

            // If training is set as active
            if($newStatus > 0 || $newStatus == -1){

                // In case someone resurrects a closed training
                if($oldStatus < 0){
                    $this->update(['closed_at' => null]);
                }

                if(!isset($this->started_at)){
                    $this->update(['started_at' => now()]);
                }
            }

            // If training is completed or closed
            if($newStatus < 0){
                $this->update(['closed_at' => now()]);

                // Delete all related training interest models, as they will only cause problems if training is re-opened.
                TrainingInterest::where('training_id', $this->id)->delete();
            }

            $this->update(['status' => $newStatus]);
        }

    }

    /**
     * Get a inline string of ratings associated with a training.
     *
     * @param string $status
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
     * @param string $status
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
     * Get the student.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the country of the training
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
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
