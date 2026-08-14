<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\MoodleCourse;
use App\Models\MoodleCourseRule;
use App\Services\MoodleClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class MoodleController extends Controller
{
    public function index(Request $request, MoodleClient $moodle): View
    {
        Gate::authorize('training.moodle.manage');
        $areaScope = $request->user()->accessibleAreasForPermission('training.moodle.manage');
        $areas = $areaScope->isGlobal
            ? Area::query()->with('ratings')->orderBy('name')->get()
            : $areaScope->areas
                ->map(fn (Area $area): Area => $area->loadMissing('ratings'))
                ->sortBy('name');

        return view('admin.moodle', [
            'configured' => $moodle->isConfigured(),
            'courses' => MoodleCourse::query()->where('enabled', true)->orderBy('full_name')->get(),
            'rules' => MoodleCourseRule::all()->groupBy(fn (MoodleCourseRule $rule): string => $rule->area_id . '.' . $rule->rating_id),
            'areas' => $areas,
        ]);
    }

    public function syncCourses(MoodleClient $moodle): RedirectResponse
    {
        Gate::authorize('training.moodle.manage');

        try {
            $courses = collect($moodle->courses());
            DB::transaction(function () use ($courses): void {
                MoodleCourse::query()->update(['enabled' => false]);

                foreach ($courses as $course) {
                    MoodleCourse::updateOrCreate(
                        ['moodle_id' => (int) $course['id']],
                        [
                            'short_name' => (string) $course['shortname'],
                            'full_name' => (string) $course['fullname'],
                            'enabled' => true,
                            'synced_at' => now(),
                        ]
                    );
                }
            });
        } catch (Throwable $exception) {
            return back()->withErrors('Could not refresh Moodle courses: ' . $exception->getMessage());
        }

        return back()->withSuccess("Refreshed {$courses->count()} Moodle courses.");
    }

    public function updateRules(Request $request): RedirectResponse
    {
        Gate::authorize('training.moodle.manage');

        $validated = $request->validate([
            'rules' => ['nullable', 'array'],
            'rules.*' => ['array'],
            'rules.*.*' => ['array'],
            'rules.*.*.*' => ['integer', 'exists:moodle_courses,id'],
        ]);

        $areaScope = $request->user()->accessibleAreasForPermission('training.moodle.manage');
        $manageableAreas = $areaScope->isGlobal
            ? Area::query()->with('ratings')->get()
            : $areaScope->areas->map(fn (Area $area): Area => $area->loadMissing('ratings'));
        $manageableAreaIds = $manageableAreas->pluck('id');

        foreach ($validated['rules'] ?? [] as $areaId => $ratings) {
            $area = $manageableAreas->firstWhere('id', (int) $areaId);
            if ($area === null) {
                throw ValidationException::withMessages(['rules' => 'You cannot manage Moodle courses for that training area.']);
            }

            foreach (array_keys($ratings) as $ratingId) {
                if (! $area->ratings->contains('id', (int) $ratingId)) {
                    throw ValidationException::withMessages(['rules' => 'That training rating is not configured for the selected area.']);
                }
            }
        }

        DB::transaction(function () use ($manageableAreaIds, $validated): void {
            MoodleCourseRule::query()->whereIn('area_id', $manageableAreaIds)->delete();

            foreach ($validated['rules'] ?? [] as $areaId => $ratings) {
                foreach ($ratings as $ratingId => $courseIds) {
                    foreach (array_unique($courseIds) as $courseId) {
                        MoodleCourseRule::create([
                            'moodle_course_id' => $courseId,
                            'area_id' => $areaId,
                            'rating_id' => $ratingId,
                        ]);
                    }
                }
            }
        });

        return back()->withSuccess('Moodle course assignments updated.');
    }
}
