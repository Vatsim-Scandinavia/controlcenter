<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SidebarComponentTest extends TestCase
{
    public function test_item_renders_as_a_top_level_nav_item_by_default(): void
    {
        $html = Blade::render(
            '<x-sidebar.item href="/dashboard" icon="fa-table-columns" title="Dashboard" />'
        );

        $this->assertStringContainsString('<li class="nav-item"', $html);
        $this->assertStringContainsString('class="nav-link"', $html);
        $this->assertStringContainsString('href="/dashboard"', $html);
        $this->assertStringContainsString('<span>Dashboard</span>', $html);
        $this->assertStringNotContainsString('collapse-item', $html);
    }

    public function test_item_renders_as_a_collapse_item_inside_a_section(): void
    {
        $html = Blade::render(
            '<x-sidebar.item href="/users" title="Member Overview" collapse />'
        );

        $this->assertStringContainsString('class="collapse-item"', $html);
        $this->assertStringContainsString('href="/users"', $html);
        $this->assertStringContainsString('Member Overview', $html);
        $this->assertStringNotContainsString('nav-item', $html);
        $this->assertStringNotContainsString('nav-link', $html);
    }

    public function test_collapse_item_never_renders_an_icon(): void
    {
        $html = Blade::render(
            '<x-sidebar.item href="/positions" icon="fa-location" title="Positions" collapse />'
        );

        $this->assertStringNotContainsString('fa-location', $html);
        $this->assertStringNotContainsString('<i ', $html);
    }

    public function test_item_marks_active_state_in_both_modes(): void
    {
        $topLevel = Blade::render(
            '<x-sidebar.item href="/dashboard" title="Dashboard" :active="true" />'
        );
        $collapsed = Blade::render(
            '<x-sidebar.item href="/users" title="Users" collapse :active="true" />'
        );

        $this->assertStringContainsString('nav-item active', $topLevel);
        $this->assertStringContainsString('collapse-item active', $collapsed);
    }

    public function test_section_renders_a_collapse_toggle_wrapping_its_slot(): void
    {
        $html = Blade::render(
            '<x-sidebar.section icon="fa-users" title="Users" id="collapseMem">'
            . '<x-sidebar.item href="/users" title="Member Overview" collapse />'
            . '</x-sidebar.section>'
        );

        $this->assertStringContainsString('data-bs-target="#collapseMem"', $html);
        $this->assertStringContainsString('id="collapseMem"', $html);
        $this->assertStringContainsString('collapse-inner', $html);
        // The nested item is rendered as a collapse link, not a top-level item.
        $this->assertStringContainsString('class="collapse-item"', $html);
    }
}
