<?php

namespace App\Support\OpenApi;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\Combined\AllOf;
use Dedoc\Scramble\Support\Generator\Combined\AnyOf;
use Dedoc\Scramble\Support\Generator\Combined\OneOf;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\Type;

/**
 * Sorts the generated component schemas alphabetically.
 *
 * Scramble derives model schemas from the physical database column order, which differs between
 * engines (MySQL locally vs. SQLite in CI) and made the exported `docs/api-v1.json` drift. Order is
 * insignificant in OpenAPI, so sorting yields a byte-identical export regardless of the engine.
 */
class SortsDocumentAlphabetically implements DocumentTransformer
{
    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        ksort($document->components->schemas);

        foreach ($document->components->schemas as $schema) {
            $this->sortType($schema);
        }
    }

    private function sortType(Schema|Type|null $node): void
    {
        if ($node instanceof Schema) {
            $this->sortType($node->type);

            return;
        }

        if ($node instanceof ObjectType) {
            ksort($node->properties);
            sort($node->required);

            foreach ($node->properties as $property) {
                $this->sortType($property);
            }

            $this->sortType($node->additionalProperties);

            return;
        }

        if ($node instanceof ArrayType) {
            $this->sortType($node->items);

            foreach ($node->prefixItems as $item) {
                $this->sortType($item);
            }

            return;
        }

        if ($node instanceof AllOf || $node instanceof AnyOf || $node instanceof OneOf) {
            foreach ($node->items as $item) {
                $this->sortType($item);
            }
        }
    }
}
