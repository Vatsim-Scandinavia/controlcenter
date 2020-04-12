<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{

    protected $guarded = [];

    protected $dates = [
      'started_at',
      'finished_at'
    ];

    public function path()
    {
        return route('training.update', ['training' => $this->id]);
    }

    public function updateStatus(int $status)
    {
        $oldStatus = $this->fresh()->status;

        if (($status == 0 || $status == -1) && $status < $oldStatus) {
            // Training was set back in queue
            $this->started_at = null;
            $this->finished_at = null;
            $this->save();
        }

        if ($status == 1) {
            if ($status > $oldStatus) {
                // Training has begun
                $this->started_at = now();
                $this->save();
            } elseif ($status < $oldStatus) {
                $this->finished_at = null;
                $this->save();
            }
        }

        if ($status == 3 && $status > $oldStatus) {
            if ($this->started_at == null)
                $this->started_at = now();

            $this->finished_at = now();
            $this->save();
        }
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function country(){
        return $this->belongsTo(Country::class);
    }

    public function reports(){
        return $this->hasMany(TrainingReport::class);
    }

    public function ratings(){
        return $this->belongsToMany(Rating::class);
    }

    public function mentors(){
        return $this->belongsToMany(User::class);
    }
}
