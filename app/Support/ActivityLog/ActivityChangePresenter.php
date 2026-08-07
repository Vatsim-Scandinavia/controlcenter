<?php

namespace App\Support\ActivityLog;

use App\Contracts\DescribesActivityChanges;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * Turns raw activity-log attribute changes into presentable lines for the
 * generic log view.
 *
 * Foreign-key attributes declared by a subject model (via
 * {@see DescribesActivityChanges}) are batch-resolved to their display text and
 * optional link; every other change falls back to its raw scalar value. This
 * keeps the controller and view free of any per-resource knowledge.
 */
class ActivityChangePresenter
{
    /**
     * Build change lines keyed by activity id.
     *
     * @param  iterable<ActivityLog>  $logs
     * @return array<int|string, list<array{label: string, from: array{text: string, url: string|null}|null, to: array{text: string, url: string|null}}>>
     */
    public static function present(iterable $logs): array
    {
        $referenceModels = self::batchResolveReferences($logs);

        $lines = [];

        foreach ($logs as $log) {
            $lines[$log->getKey()] = self::linesFor($log, $referenceModels);
        }

        return $lines;
    }

    /**
     * Reference configuration declared by a log's subject model, or an empty
     * array when the subject does not describe its changes.
     *
     * @return array<string, array{label: string, model: class-string, display: callable, link: callable|null}>
     */
    private static function referenceConfig(ActivityLog $log): array
    {
        $subjectType = $log->subject_type;

        if ($subjectType === null) {
            return [];
        }

        $class = Relation::getMorphedModel($subjectType) ?? $subjectType;

        if (! is_a($class, DescribesActivityChanges::class, true)) {
            return [];
        }

        return $class::activityChangeReferences();
    }

    /**
     * One `whereIn` per referenced model across the whole page (no N+1).
     *
     * @param  iterable<ActivityLog>  $logs
     * @return array<class-string, Collection<int, Model>>
     */
    private static function batchResolveReferences(iterable $logs): array
    {
        $idsByModel = [];

        foreach ($logs as $log) {
            $config = self::referenceConfig($log);

            if ($config === []) {
                continue;
            }

            $new = (array) data_get($log->attribute_changes, 'attributes', []);
            $old = (array) data_get($log->attribute_changes, 'old', []);

            foreach ($config as $key => $meta) {
                foreach ([$new[$key] ?? null, $old[$key] ?? null] as $id) {
                    if (! empty($id)) {
                        $idsByModel[$meta['model']][] = (int) $id;
                    }
                }
            }
        }

        $resolved = [];

        foreach ($idsByModel as $modelClass => $ids) {
            $resolved[$modelClass] = $modelClass::whereIn('id', array_unique($ids))->get()->keyBy('id');
        }

        return $resolved;
    }

    /**
     * @param  array<class-string, Collection<int, Model>>  $referenceModels
     * @return list<array{label: string, from: array{text: string, url: string|null}|null, to: array{text: string, url: string|null}}>
     */
    private static function linesFor(ActivityLog $log, array $referenceModels): array
    {
        $new = (array) data_get($log->attribute_changes, 'attributes', []);
        $old = (array) data_get($log->attribute_changes, 'old', []);
        $config = self::referenceConfig($log);

        $lines = [];

        foreach ($new as $key => $value) {
            $meta = $config[$key] ?? null;

            $lines[] = [
                'label' => $meta['label'] ?? $key,
                'from' => array_key_exists($key, $old)
                    ? self::presentValue($old[$key], $meta, $referenceModels)
                    : null,
                'to' => self::presentValue($value, $meta, $referenceModels),
            ];
        }

        return $lines;
    }

    /**
     * Resolve a single value: a declared reference resolves to display text and
     * an optional link; anything else renders as its raw scalar.
     *
     * @param  array{label: string, model: class-string, display: callable, link: callable|null}|null  $meta
     * @param  array<class-string, Collection<int, Model>>  $referenceModels
     * @return array{text: string, url: string|null}
     */
    private static function presentValue(mixed $value, ?array $meta, array $referenceModels): array
    {
        if ($meta === null) {
            return ['text' => is_scalar($value) ? (string) $value : (string) json_encode($value), 'url' => null];
        }

        if (empty($value)) {
            return ['text' => 'N/A', 'url' => null];
        }

        $model = $referenceModels[$meta['model']][(int) $value] ?? null;

        if ($model === null) {
            return ['text' => class_basename($meta['model']) . " #{$value}", 'url' => null];
        }

        return [
            'text' => ($meta['display'])($model),
            'url' => $meta['link'] !== null ? ($meta['link'])($model) : null,
        ];
    }
}
