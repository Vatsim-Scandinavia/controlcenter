<?php

namespace Tests\Feature\Api\V1;

use Dedoc\Scramble\Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionsEndpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The documented 200 response for `/positions` must describe exactly the fields the
     * endpoint actually returns. Scramble cannot infer the column list from
     * `Position::select([...])->get()`, so without an explicit response annotation it
     * documents every column of the `Position` model instead of the three selected ones.
     */
    public function test_documented_response_matches_the_actual_payload(): void
    {
        $data = $this->getJson('/api/v1/positions')->assertOk()->json('data');

        $this->assertNotEmpty($data, 'The seeded positions must be present, otherwise this assertion passes vacuously.');

        $actualFields = array_keys($data[0]);
        sort($actualFields);

        $documentedFields = $this->documentedPositionFields();
        sort($documentedFields);

        $this->assertSame($documentedFields, $actualFields);
    }

    public function test_documented_response_lists_only_the_selected_columns(): void
    {
        $documentedFields = $this->documentedPositionFields();
        sort($documentedFields);

        $this->assertSame(['callsign', 'frequency', 'name'], $documentedFields);
    }

    public function test_endpoint_returns_only_the_selected_columns(): void
    {
        $data = $this->getJson('/api/v1/positions')->assertOk()->json('data');

        $this->assertNotEmpty($data);

        foreach ($data as $position) {
            $fields = array_keys($position);
            sort($fields);
            $this->assertSame(['callsign', 'frequency', 'name'], $fields);
        }
    }

    public function test_legacy_unversioned_endpoint_returns_the_same_payload(): void
    {
        $legacy = $this->getJson('/api/positions')->assertOk()->json('data');
        $versioned = $this->getJson('/api/v1/positions')->assertOk()->json('data');

        $this->assertSame($versioned, $legacy);
    }

    /**
     * Resolve the property names of the documented `/positions` 200 response item schema,
     * following a `$ref` into `components.schemas` when Scramble emits one.
     *
     * @return array<int, string>
     */
    private function documentedPositionFields(): array
    {
        $document = app(Generator::class)();

        $schema = $document['paths']['/positions']['get']['responses'][200]['content']['application/json']['schema'];

        $items = $this->resolveRef($document, $schema)['properties']['data']['items'];

        return array_keys($this->resolveRef($document, $items)['properties']);
    }

    /**
     * @param  array<array-key, mixed>  $document
     * @param  array<array-key, mixed>  $schema
     * @return array<array-key, mixed>
     */
    private function resolveRef(array $document, array $schema): array
    {
        if (! isset($schema['$ref'])) {
            return $schema;
        }

        $name = basename($schema['$ref']);

        return $document['components']['schemas'][$name];
    }
}
