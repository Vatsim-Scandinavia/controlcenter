<?php

namespace App\Models;

use App\Helpers\VatsimRating;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * This model is special, as it's using the table non-prefixed mysql connection and garthers data provided by Handover.
 *
 * @todo Move non-authenticatinon related information out of Handover.
 */
class Handover extends Model
{

    use HasFactory;

    public $table = 'users';
    public $timestamps = false;

    protected $fillable = ['atc_active'];

    /**
     * Initialises the Handover model with a custom, yet dynamic, connection.
     * The custom connection is a prerequisite for the current coupling between
     * Control Center and Handover.
     */
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        $this->setConnection(config('database.handover'));
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    /**
     * Fetch members that are active as ATC.
     *
     * @param array $userIds
     * @return EloquentCollection<Handover>
     */
    public static function getActiveAtcMembers(array $userIds = [])
    {
        // Return S1+ users who are VATSCA members
        if (!empty($userIds)) {
            return Handover::whereIn('id', $userIds)
                ->where('atc_active', true)
                ->get();
        } else {
            return Handover::where('atc_active', true)->get();
        }
    }

    /**
     * Fetch members with a rating that are in our subdivision
     * @param array $userIds
     * @return EloquentCollection<Handover>
     */
    public static function getRatedMembers(array $userIds = [])
    {
        // Return S1+ users who are VATSCA members
        if (!empty($userIds)) {
            return Handover::whereIn('id', $userIds)
                ->where('rating', '>=', VatsimRating::S1)
                ->where('subdivision', Config::get('app.owner_short'))
                ->get();
        } else {
            return Handover::where([
                ['rating', '>=', VatsimRating::S1],
                ['subdivision', '=', Config::get('app.owner_short')]
            ])->get();
        }
    }

}
