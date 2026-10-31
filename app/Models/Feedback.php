<?php

namespace App\Models;

use App\Contracts\DescribesActivityChanges;
use App\Helpers\LogName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Feedback extends Model implements DescribesActivityChanges
{
    use HasFactory, LogsActivity, Notifiable;

    /**
     * Only log staff edits. MUST stay static — spatie v5 checks
     * isset(static::$recordEvents); a non-static property is ignored and every
     * event (including public submissions) would be logged.
     *
     * @var array<int, string>
     */
    protected static array $recordEvents = ['updated'];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'feedback',
        'submitter_user_id',
        'reference_user_id',
        'reference_position_id',
        'area_id',
        'forwarded',
    ];

    /**
     * Record reference re-assignments to the activity log under the "feedback"
     * category, storing old→new for the reference foreign keys.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(LogName::Feedback)
            ->logOnly(['reference_user_id', 'reference_position_id', 'area_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "Feedback {$eventName}");
    }

    /**
     * Present the logged reference foreign keys as resolved names. The `link`
     * closures are stubbed until the position and controller-feedback routes
     * exist; wiring them here is all that is needed to make the log entries
     * link through — the generic log view needs no changes.
     *
     * {@inheritDoc}
     */
    public static function activityChangeReferences(): array
    {
        return [
            'reference_user_id' => [
                'label' => 'Controller',
                'model' => User::class,
                'display' => fn (User $user): string => "{$user->name} ({$user->id})",
                'link' => null, // future: fn (User $user) => route('reports.feedback', ['controller' => $user->id])
            ],
            'reference_position_id' => [
                'label' => 'Position',
                'model' => Position::class,
                'display' => fn (Position $position): string => $position->callsign,
                'link' => null, // future: fn (Position $position) => $position->path()
            ],
            'area_id' => [
                'label' => 'Area',
                'model' => Area::class,
                'display' => fn (Area $area): string => $area->name,
                'link' => null,
            ],
        ];
    }

    /**
     * Match feedback belonging to any of the given areas, explicit or inherited
     * from the referenced position. Query-side twin of the `area` accessor;
     * use it rather than rolling the same condition, so visibility,
     * authorization and filtering cannot disagree.
     *
     * Pass an iterable: `collect()` flattens a lone model into its attributes.
     *
     * @param  Builder<self>  $query
     * @param  Collection<int, Area|int>|array<int, Area|int>  $areas
     */
    public function scopeInArea(Builder $query, Collection|array $areas): void
    {
        $areaIds = collect($areas)->map(fn (Area|int $area): int => $area instanceof Area ? $area->id : $area)->all();

        $query->where(fn (Builder $q) => $q
            ->whereIn('area_id', $areaIds)
            ->orWhereHas('referencePosition', fn (Builder $q) => $q->whereIn('area_id', $areaIds)));
    }

    /**
     * Scope feedback to what the given user may see: correlated feedback within
     * their permitted areas (or all correlated when global), plus uncorrelated
     * feedback when they hold that permission. Single source of truth for
     * feedback visibility — used by the report listing and the filter option
     * providers, so a crafted filter can never widen it.
     *
     * An explicit area correlates a feedback just as a referenced position
     * does, so only feedback carrying neither is uncorrelated.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $correlatedScope = $user->accessibleAreasForPermission('feedback.correlated.view');
        $canViewUncorrelated = $user->accessibleAreasForPermission('feedback.uncorrelated.view')->hasAccess();

        $query->where(function (Builder $q) use ($correlatedScope, $canViewUncorrelated) {
            if ($correlatedScope->isGlobal) {
                $q->where(fn (Builder $q) => $q->whereNotNull('reference_position_id')->orWhereNotNull('area_id'));
            } else {
                $q->inArea($correlatedScope->areas);
            }

            if ($canViewUncorrelated) {
                $q->orWhere(fn (Builder $q) => $q->whereNull('reference_position_id')->whereNull('area_id'));
            }
        });
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitter_user_id');
    }

    public function referenceUser()
    {
        return $this->belongsTo(User::class, 'reference_user_id');
    }

    public function referencePosition()
    {
        return $this->belongsTo(Position::class, 'reference_position_id');
    }

    public function explicitArea(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /**
     * Explicit area wins; otherwise falls back to the position's area. The two
     * are mutually exclusive (enforced in UpdateFeedbackRequest). Eager-load it
     * as `with('explicitArea')`; `with('area')` is not a relation.
     */
    public function getAreaAttribute(): ?Area
    {
        return $this->explicitArea ?? $this->referencePosition?->area;
    }
}
