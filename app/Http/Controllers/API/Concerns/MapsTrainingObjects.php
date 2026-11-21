<?php

namespace App\Http\Controllers\API\Concerns;

use App\Models\TrainingObject;
use App\Models\TrainingObjectAttachment;
use Carbon\Carbon;

trait MapsTrainingObjects
{
    /**
     * Format a timestamp as ISO-8601, passing null through.
     */
    protected function iso(mixed $value): ?string
    {
        return $value === null ? null : Carbon::parse($value)->toIso8601String();
    }

    /**
     * Map the attachments of a report or examination.
     *
     * @return array<int, array{id: string, name: string|null, hidden: bool, url: string, created_at: string|null}>
     */
    protected function mapAttachments(TrainingObject $object): array
    {
        return $object->attachments->map(fn (TrainingObjectAttachment $attachment) => [
            'id' => $attachment->id,
            'name' => $attachment->file?->name,
            'hidden' => (bool) $attachment->hidden,
            'url' => route('api.v1.attachments.show', ['attachment' => $attachment->id]),
            'created_at' => $this->iso($attachment->created_at),
        ])->values()->all();
    }
}
