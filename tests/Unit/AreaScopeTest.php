<?php

namespace Tests\Unit;

use App\Support\AreaScope;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AreaScopeTest extends TestCase
{
    #[Test]
    public function global_scope_is_global_and_has_access(): void
    {
        $scope = AreaScope::global();

        $this->assertTrue($scope->isGlobal);
        $this->assertTrue($scope->hasAccess());
        $this->assertTrue($scope->areas->isEmpty());
    }

    #[Test]
    public function for_areas_with_items_is_not_global_and_has_access(): void
    {
        $scope = AreaScope::forAreas(collect(['anything']));

        $this->assertFalse($scope->isGlobal);
        $this->assertTrue($scope->hasAccess());
    }

    #[Test]
    public function for_areas_with_empty_collection_has_no_access(): void
    {
        $scope = AreaScope::forAreas(collect());

        $this->assertFalse($scope->isGlobal);
        $this->assertFalse($scope->hasAccess());
    }
}
