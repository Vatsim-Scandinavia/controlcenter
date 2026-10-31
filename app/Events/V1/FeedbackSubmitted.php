<?php

namespace App\Events\V1;

use App\Models\Feedback;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Feedback was submitted. Fired from the authoritative Eloquent `created` event
 * on {@see Feedback}.
 */
class FeedbackSubmitted
{
    use Dispatchable;

    public function __construct(public readonly Feedback $feedback) {}
}
