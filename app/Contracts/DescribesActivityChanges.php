<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Implemented by models that are activity-log subjects and whose logged
 * foreign-key attributes should be rendered as resolved, optionally linked,
 * references instead of raw ids.
 *
 * The generic activity-log view stays domain-agnostic: it asks the subject
 * model (via its logged `subject_type`) how to present its own changes.
 */
interface DescribesActivityChanges
{
    /**
     * Describe how logged foreign-key attributes should be presented, keyed by
     * the logged attribute name (e.g. `reference_user_id`).
     *
     * Each entry:
     *  - label:   human label for the attribute (e.g. "Controller")
     *  - model:   related Eloquent model class the stored id resolves to
     *  - display: fn (Model $related): string  — the text to show
     *  - link:    (fn (Model $related): ?string)|null — a URL, or null when not linkable
     *
     * @return array<string, array{label: string, model: class-string<Model>, display: callable(Model): string, link: (callable(Model): ?string)|null}>
     */
    public static function activityChangeReferences(): array;
}
