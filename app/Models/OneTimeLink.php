<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OneTimeLink extends Model
{
    const TRAINING_REPORT_TYPE = 'report';

    const TRAINING_EXAMINATION_TYPE = 'examination';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the training related to the one time link
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Get the one time access link
     *
     * @return string
     */
    public function getLink()
    {
        return route('training.onetimelink.redirect', ['key' => $this->key]);
    }

    /**
     * Return if the one time link is for a report
     *
     * @return bool
     */
    public function reportType()
    {
        return $this->training_object_type == self::TRAINING_REPORT_TYPE;
    }

    /**
     * Return if the one time link is for a report
     *
     * @return bool
     */
    public function examinationType()
    {
        return $this->training_object_type == self::TRAINING_EXAMINATION_TYPE;
    }

    /**
     * Get the redirect link
     *
     * @return string
     */
    public function getRelatedLink()
    {
        switch ($this->training_object_type) {
            case self::TRAINING_REPORT_TYPE:
                return route('training.report.create', ['training' => $this->training]);
                break;
            case self::TRAINING_EXAMINATION_TYPE:
                return route('training.examination.create', ['training' => $this->training]);
                break;
            default:
                return '';
                break;
        }
    }
}
