<?php

namespace Tests\Feature;

use anlutro\LaravelSettings\Facade as Setting;
use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\User;
use App\Models\Vote;
use App\Policies\SettingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermissionPoliciesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_passes_admin_only_policies(): void
    {
        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $this->assertTrue($admin->can('index', Vote::class));
        $this->assertTrue($admin->can('index', ActivityLog::class));
        $this->assertTrue((new SettingPolicy)->index($admin, new Setting));
        $this->assertTrue((new SettingPolicy)->edit($admin, new Setting));
    }

    public function test_moderator_fails_admin_only_policies(): void
    {
        $area = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $this->assertFalse($moderator->can('index', Vote::class));
        $this->assertFalse($moderator->can('index', ActivityLog::class));
        $this->assertFalse((new SettingPolicy)->index($moderator, new Setting));
        $this->assertFalse((new SettingPolicy)->edit($moderator, new Setting));
        $this->assertFalse($moderator->can('create', Vote::class));
        $this->assertFalse($moderator->can('store', Vote::class));
    }
}
