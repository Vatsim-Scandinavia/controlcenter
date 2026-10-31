<?php

namespace Tests\Feature\Modules;

use App\Models\User;
use App\View\Components\ModuleSlot;
use ControlCenter\ModuleApi\Ui\Slot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\SlotFixtureModule;
use Tests\TestCase;

class ModuleSlotTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        View::addNamespace('slotfix', base_path('tests/Fixtures/Modules/slot-views'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderSlot(string $slot, array $data = []): string
    {
        return (new ModuleSlot($slot, $data))->render()->render();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        return $admin;
    }

    #[Test]
    public function it_renders_registered_slot_views_for_an_authenticated_user(): void
    {
        $this->bootModules([SlotFixtureModule::class], ['slotfix']);
        $this->actingAs(User::factory()->create());

        $html = $this->renderSlot(Slot::DashboardWidgets);

        $this->assertStringContainsString('OK_WIDGET_CONTENT', $html);
    }

    #[Test]
    public function a_throwing_slot_is_isolated_logged_and_skipped(): void
    {
        $this->bootModules([SlotFixtureModule::class], ['slotfix']);
        $this->actingAs(User::factory()->create());
        Log::spy();

        // Must not throw, and the healthy widget must still render.
        $html = $this->renderSlot(Slot::DashboardWidgets);

        $this->assertStringContainsString('OK_WIDGET_CONTENT', $html);
        $this->assertStringNotContainsString('BOOM_SHOULD_NOT_RENDER', $html);
        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    #[Test]
    public function slot_items_are_hidden_when_the_user_lacks_the_permission(): void
    {
        $this->bootModules([SlotFixtureModule::class], ['slotfix']);
        $this->actingAs(User::factory()->create());

        $html = $this->renderSlot(Slot::UserPanels);

        $this->assertStringNotContainsString('PANEL_CONTENT', $html);
    }

    #[Test]
    public function slot_items_are_shown_when_the_user_has_the_permission(): void
    {
        $this->bootModules([SlotFixtureModule::class], ['slotfix']);
        $admin = $this->admin();
        $this->actingAs($admin);

        $html = $this->renderSlot(Slot::UserPanels, ['user' => $admin]);

        $this->assertStringContainsString('PANEL_CONTENT', $html);
        $this->assertStringContainsString('user=' . $admin->id, $html);
    }

    #[Test]
    public function a_guest_sees_no_slot_content(): void
    {
        $this->bootModules([SlotFixtureModule::class], ['slotfix']);

        $html = $this->renderSlot(Slot::DashboardWidgets);

        $this->assertStringNotContainsString('OK_WIDGET_CONTENT', $html);
    }
}
