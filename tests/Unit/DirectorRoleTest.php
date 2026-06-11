<?php

namespace Tests\Unit;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DirectorRoleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function director_should_not_have_system_level_permissions()
    {
        $area = Area::factory()->create();
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => $area->id]);

        $this->assertFalse($director->hasPermission('view-system-health'));
        $this->assertFalse($director->hasPermission('manage-area', $area));
    }

    #[Test]
    public function area_director_permissions_do_not_leak_to_other_areas()
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => $area->id]);

        $this->assertFalse($director->hasPermission('manage-users', $otherArea));
    }
}
