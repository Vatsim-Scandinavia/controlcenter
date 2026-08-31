<?php

namespace App\Support\OpenApi;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;

/**
 * Drops component schemas that nothing in the document references.
 *
 * Scramble registers a model schema whenever it analyses a query on that model, even when an
 * explicit `#[Response]` attribute overrides the inferred response type. `PositionController::index`
 * runs `Position::select([...])->get()` but documents a narrower three-column shape, so the
 * `Position` schema (and the `VatsimRating` enum it references) would otherwise linger in
 * `components.schemas` unreferenced. The docs renderer lists component schemas
 * (`scramble.renderers.elements.hideSchemas` is `false`), so an orphan advertises an object shape
 * no endpoint actually returns.
 *
 * Reachability is computed over the serialised document, which is the exact set of bytes exported
 * to `docs/api-v1.json`, and closed transitively so a schema referenced only by another orphan is
 * dropped too while one referenced from anywhere outside `components.schemas` is kept.
 */
class RemovesUnreferencedSchemas implements DocumentTransformer
{
    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $serialized = $document->toArray();
        $schemas = $serialized['components']['schemas'] ?? [];

        if ($schemas === []) {
            return;
        }

        // Everything except the schema definitions themselves: paths, component responses,
        // security schemes. A reference from here is what makes a schema genuinely used.
        $documentWithoutSchemaDefinitions = $serialized;
        unset($documentWithoutSchemaDefinitions['components']['schemas']);

        $reachable = $this->referencedSchemaNames($documentWithoutSchemaDefinitions);

        // Close over schema-to-schema references until the set stops growing, so a chain such as
        // Booking -> Position -> VatsimRating is kept or dropped as a whole.
        do {
            $before = count($reachable);

            foreach ($reachable as $name) {
                if (! isset($schemas[$name])) {
                    continue;
                }

                $reachable = array_values(array_unique(array_merge(
                    $reachable,
                    $this->referencedSchemaNames($schemas[$name]),
                )));
            }
        } while (count($reachable) > $before);

        foreach (array_keys($document->components->schemas) as $fullName) {
            if (! in_array($document->components->uniqueSchemaName($fullName), $reachable, true)) {
                $document->components->removeSchema($fullName);
            }
        }
    }

    /**
     * Collect the component schema names every `$ref` in the given node points at.
     *
     * @param  array<array-key, mixed>  $node
     * @return array<int, string>
     */
    private function referencedSchemaNames(array $node): array
    {
        $names = [];

        array_walk_recursive($node, function ($value, $key) use (&$names): void {
            if ($key === '$ref' && is_string($value) && str_starts_with($value, '#/components/schemas/')) {
                $names[] = basename($value);
            }
        });

        return array_values(array_unique($names));
    }
}
