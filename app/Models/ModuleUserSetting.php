<?php

namespace App\Models;

use App\Modules\ModuleUserSettings;
use Illuminate\Database\Eloquent\Model;

/**
 * One per-user module setting value. Managed through
 * {@see ModuleUserSettings}; the `key` is the dotted
 * `modules.<slug>.<field>` key and secret values are stored encrypted.
 */
class ModuleUserSetting extends Model
{
    protected $guarded = [];
}
