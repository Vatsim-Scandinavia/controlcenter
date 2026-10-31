<?php

namespace App\Contracts;

use App\Models\ActivityLog;

/**
 * Implemented by activity-log subject models that can resolve a URL to their
 * own detail page.
 *
 * The generic activity-log view stays domain-agnostic: instead of the
 * {@see ActivityLog} model hard-coding a route per subject type, a
 * subject model (including one shipped by a module) declares how to link to
 * itself. Core models continue to work through the built-in fallback.
 */
interface ResolvesActivitySubjectRoute
{
    /**
     * A URL to the subject's detail page for the given id, or null when the
     * subject is not linkable.
     */
    public static function activitySubjectRoute(int|string $id): ?string;
}
