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

        static::created(function (RoleAssignment $assignment) {
            $assignment->logRoleAssignmentActivity('created', 'Role granted');
        });

        static::updated(function (RoleAssignment $assignment) {
            $assignment->logRoleAssignmentActivity('updated', 'Role assignment updated');
        });

        static::deleted(function (RoleAssignment $assignment) {
            $assignment->logRoleAssignmentActivity('deleted', 'Role revoked');
        });
    }

    /**
     * Record this assignment change against the affected user so it surfaces
     * on, and links to, their profile in the activity log. The granted role
     * and its area (or "Global") are stored as properties.
     */
    protected function logRoleAssignmentActivity(string $event, string $description): void
    {
        $user = $this->user;

        if ($user === null) {
            return;
        }

        activity('role')
            ->performedOn($user)
            ->event($event)
            ->withProperties([
                'role' => $this->role,
                'area' => $this->area?->name ?? 'Global',
            ])
            ->log($description);
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
