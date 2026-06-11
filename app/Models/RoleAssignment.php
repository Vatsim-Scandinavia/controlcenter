<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleAssignment extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'role_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'role',
        'area_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'area_id' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        $validate = function (RoleAssignment $assignment) {
            $scope = config("roles.roles.{$assignment->role}.scope");

            if ($scope === null) {
                throw new \InvalidArgumentException(
                    "Role '{$assignment->role}' is not a recognised role."
                );
            }

            if ($scope === 'global' && $assignment->area_id !== null) {
                throw new \InvalidArgumentException(
                    "Role '{$assignment->role}' is global and cannot be assigned to an area."
                );
            }

            if ($scope === 'area' && $assignment->area_id === null) {
                throw new \InvalidArgumentException(
                    "Role '{$assignment->role}' requires an area assignment."
                );
            }
        };

        static::creating($validate);
        static::updating($validate);
    }

    /**
     * Get the user that owns the role assignment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the area associated with the role assignment.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
