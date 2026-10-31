<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiKey;
use App\Models\Area;
use App\Models\AtcActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regressions from 7.0.0: globally scoped role assignments matched no area
 * bucket and were dropped, and `onlyAtcActive` rejected the boolean spelling
 * the published OpenAPI spec advertises.
 */
class UsersEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function token(): string
    {
        ApiKey::create(['id' => 'users-endpoint-key', 'name' => 't', 'read_only' => true, 'created_at' => now()]);

        return 'users-endpoint-key';
    }

    /**
     * @return array<string, mixed>
     */
    private function roles(): array
    {
        return $this->withToken($this->token())
            ->getJson('/api/v1/users?include[]=roles')
            ->assertOk()
            ->json('data.0.roles');
    }

    /**
     * The roles map without the areas the user holds nothing in.
     *
     * @return array<string, mixed>
     */
    private function assignedRoles(): array
    {
        return array_filter($this->roles(), fn ($roles) => $roles !== null);
    }

    public function test_global_and_area_roles_are_reported_separately(): void
    {
        $area = Area::factory()->create(['name' => 'Alpha']);
        $user = User::factory()->create();
        $user->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);
        $user->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $this->assertSame(['global' => ['admin'], 'Alpha' => ['moderator']], $this->assignedRoles());
    }

    /**
     * The regression: such a user is selected because they hold a role, so an
     * all-null roles map means the assignment was lost.
     */
    public function test_a_user_holding_only_a_global_role_still_reports_it(): void
    {
        Area::factory()->create(['name' => 'Alpha']);
        $user = User::factory()->create();
        $user->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $this->assertSame(['global' => ['admin']], $this->assignedRoles());
    }

    public function test_global_is_null_without_a_global_role(): void
    {
        $area = Area::factory()->create(['name' => 'Alpha']);
        $user = User::factory()->create();
        $user->roleAssignments()->create(['role' => 'training-staff', 'area_id' => $area->id]);

        $roles = $this->roles();

        $this->assertNull($roles['global']);
        $this->assertSame(['training-staff'], $roles['Alpha']);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function booleanSpellings(): array
    {
        return [
            'true' => ['true', true],
            '1' => ['1', true],
            'yes' => ['yes', true],
            'on' => ['on', true],
            'false' => ['false', false],
            '0' => ['0', false],
            'no' => ['no', false],
            'off' => ['off', false],
        ];
    }

    #[DataProvider('booleanSpellings')]
    public function test_only_atc_active_accepts_boolean_spellings(string $value, bool $filters): void
    {
        $area = Area::factory()->create();
        $active = User::factory()->create();
        $inactive = User::factory()->create();

        foreach ([[$active, true], [$inactive, false]] as [$user, $isActive]) {
            AtcActivity::create(['user_id' => $user->id, 'area_id' => $area->id, 'atc_active' => $isActive, 'hours' => 20]);
            $user->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);
        }

        $ids = $this->withToken($this->token())
            ->getJson("/api/v1/users?include[]=roles&onlyAtcActive={$value}")
            ->assertOk()
            ->json('data.*.id');

        $this->assertEqualsCanonicalizing($filters ? [$active->id] : [$active->id, $inactive->id], $ids);
    }

    public function test_only_atc_active_rejects_values_that_are_not_boolean(): void
    {
        User::factory()->create();

        $this->withToken($this->token())
            ->getJson('/api/v1/users?include[]=roles&onlyAtcActive=banana')
            ->assertStatus(422)
            ->assertJsonValidationErrors('onlyAtcActive');
    }

    public function test_legacy_and_v1_users_endpoints_stay_identical(): void
    {
        $area = Area::factory()->create(['name' => 'Alpha']);
        $user = User::factory()->create();
        $user->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);
        $user->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $token = $this->token();
        $query = 'include[]=roles&onlyAtcActive=false';

        $this->assertSame(
            $this->withToken($token)->getJson("/api/users?{$query}")->json(),
            $this->withToken($token)->getJson("/api/v1/users?{$query}")->json(),
        );
    }
}
