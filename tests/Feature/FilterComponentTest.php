<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FilterComponentTest extends TestCase
{
    public function test_only_the_active_pill_is_solid(): void
    {
        $html = Blade::render(
            '<x-filter.group>'
            . '<x-filter.item href="/reports/trainings" :active="true">All Areas</x-filter.item>'
            . '<x-filter.item href="/reports/training/1">Oslo</x-filter.item>'
            . '</x-filter.group>'
        );

        $this->assertStringContainsString('class="input-group input-group-sm w-auto align-self-center"', $html);
        $this->assertStringContainsString('btn btn-sm btn-primary" href="/reports/trainings"', $html);
        $this->assertStringContainsString('btn btn-sm btn-outline-primary" href="/reports/training/1"', $html);
    }

    public function test_pills_carry_the_current_query_string(): void
    {
        $this->app->instance('request', Request::create('/reports/training/1?start_date=2026-01-01'));

        $html = Blade::render('<x-filter.item href="/reports/trainings">All Areas</x-filter.item>');

        $this->assertStringContainsString('href="/reports/trainings?start_date=2026-01-01"', $html);
    }
}
