<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'description',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'permissions')->withPivot('area_id')->withTimestamps();
    }

    public static function admins()
    {
        return static::where('id', 1)->first()->users;
    }

    public static function moderators()
    {
        return static::where('id', 2)->first()->users;
    }

    public static function mentors()
    {
        return static::where('id', 3)->first()->users;
    }
}
