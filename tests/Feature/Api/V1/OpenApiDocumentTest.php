<?php

namespace Tests\Feature\Api\V1;

use Dedoc\Scramble\Generator;
use Tests\TestCase;

class OpenApiDocumentTest extends TestCase
{
    public function test_document_covers_all_v1_paths(): void
    {
        $document = app(Generator::class)();

        // Scramble scopes the document to the single `api/v1` server (see config/scramble.php
        // `api_path`), so path keys are relative to that server and do not repeat the prefix.
        $paths = array_keys($document['paths']);

        foreach ([
            '/bookings',
            '/bookings/create',
            '/bookings/{booking}',
            '/positions',
            '/users',
        ] as $expected) {
            $this->assertContains($expected, $paths, "Missing $expected in OpenAPI document");
        }
    }

    public function test_user_endpoint_is_not_documented(): void
    {
        $document = app(Generator::class)();

        // The `api.v1.user` route stays live but is intentionally excluded from the docs
        // (it was never part of the published API reference).
        $this->assertArrayNotHasKey('/user', $document['paths']);
    }

    public function test_document_excludes_legacy_unversioned_paths(): void
    {
        $document = app(Generator::class)();

        // Path keys are already relative to the versioned server, so a legacy `/api/bookings`
        // key could never appear regardless of scoping. Assert the facts that actually prove
        // the legacy, unversioned routes are excluded: the document's only server is scoped to
        // `api/v1`, and the booking index operation carries the v1 route name.
        $this->assertCount(1, $document['servers']);
        $this->assertStringEndsWith('/api/v1', $document['servers'][0]['url']);

        $this->assertSame(
            'v1.booking.index',
            $document['paths']['/bookings']['get']['operationId'],
        );
    }

    public function test_component_schema_names_are_alphabetically_ordered(): void
    {
        $document = app(Generator::class)();

        // Scramble derives model schemas from the physical database column order, which differs
        // between engines (MySQL locally vs. SQLite in CI). Sorting the whole document makes the
        // exported `docs/api-v1.json` byte-identical regardless of the database it is generated
        // against, so the CI drift check is stable.
        $names = array_keys($document['components']['schemas'] ?? []);
        $this->assertNotEmpty($names);

        $sorted = $names;
        sort($sorted);

        $this->assertSame($sorted, $names, 'Component schema names must be alphabetically ordered.');
    }

    public function test_all_schema_object_properties_are_alphabetically_ordered(): void
    {
        $document = app(Generator::class)();

        $this->assertSchemaOrdering($document['components']['schemas'] ?? [], 'components.schemas');
    }

    /**
     * Recursively assert that every object node exposes its `properties` keys and `required`
     * entries in alphabetical order.
     *
     * @param  array<array-key, mixed>  $node
     */
    private function assertSchemaOrdering(array $node, string $path): void
    {
        if (isset($node['properties']) && is_array($node['properties'])) {
            $keys = array_keys($node['properties']);
            $sorted = $keys;
            sort($sorted);
            $this->assertSame($sorted, $keys, "Properties at {$path} must be alphabetically ordered.");
        }

        if (isset($node['required']) && is_array($node['required'])) {
            $sorted = $node['required'];
            sort($sorted);
            $this->assertSame($sorted, $node['required'], "Required list at {$path} must be alphabetically ordered.");
        }

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $this->assertSchemaOrdering($value, "{$path}.{$key}");
            }
        }
    }
}
