<?php

namespace Tests\Fixtures\Modules;

use App\Contracts\ResolvesActivitySubjectRoute;
use Illuminate\Database\Eloquent\Model;

/**
 * Fixture activity-log subject model that resolves its own detail URL, standing
 * in for a module-shipped model.
 */
class ExampleSubject extends Model implements ResolvesActivitySubjectRoute
{
    public static function activitySubjectRoute(int|string $id): ?string
    {
        return "https://cc.test/modules/example/subjects/{$id}";
    }
}
