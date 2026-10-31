<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Persisted enabled/disabled state for an installed module. The source of truth
 * for *what is installed* is Composer and the bundled list; this table only
 * records which installed modules an operator has turned on.
 */
class Module extends Model
{
    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
        'enabled_at' => 'datetime',
    ];
}
