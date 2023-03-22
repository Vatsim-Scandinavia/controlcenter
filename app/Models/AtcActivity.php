<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtcActivity extends Model
{
    public $primaryKey = 'user_id';

    protected $fillable = ['user_id', 'hours', 'start_of_grace_period'];

    protected $dates = [
        'created_at',
        'updated_at',
        'start_of_grace_period',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
