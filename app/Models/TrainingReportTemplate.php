<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingReportTemplate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'draft' => 'boolean',
    ];

    /**
     * Get the areas that this template is assigned to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function areas()
    {
        return $this->belongsToMany(Area::class, 'training_report_template_area');
    }
}

