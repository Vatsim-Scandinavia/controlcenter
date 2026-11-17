<?php

namespace App\Http\Controllers;

use anlutro\LaravelSettings\Facade as Setting;
use App\Http\Requests\StoreFeedbackRequest;
use App\Http\Requests\UpdateFeedbackRequest;
use App\Models\Area;
use App\Models\Feedback;
use App\Models\Position;
use App\Models\User;
use App\Notifications\FeedbackNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class FeedbackController extends Controller
{
    /**
     * Show the area picker that precedes the feedback form.
     */
    public function selectArea(): View|RedirectResponse
    {
        if (! Setting::get('feedbackEnabled')) {
            return redirect()->route('dashboard')->withErrors('Feedback is currently disabled.');
        }

        $areas = Area::orderBy('name')->get();

        return view('feedback.areas', compact('areas'));
    }

    /**
     * Show the feedback form for the given area, or for general feedback
     * when no area is given. Positions and controllers are limited to the area.
     */
    public function create(?Area $area = null): View|RedirectResponse
    {
        if (! Setting::get('feedbackEnabled')) {
            return redirect()->route('dashboard')->withErrors('Feedback is currently disabled.');
        }

        $positions = $area ? Position::where('area_id', $area->id)->orderBy('callsign')->get() : Position::all();
        $controllers = User::getActiveAtcMembers(areaId: $area?->id);

        return view('feedback.create', compact('positions', 'controllers', 'area'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(StoreFeedbackRequest $request)
    {

        if (! Setting::get('feedbackEnabled')) {
            return redirect()->route('dashboard')->withErrors('Feedback is currently disabled.');
        }

        $data = $request->validated();

        $position = isset($data['position']) ? Position::where('callsign', $data['position'])->get()->first() : null;
        $controller = isset($data['controller']) ? User::find($data['controller']) : null;
        $feedback = $data['feedback'];

        $submitter = auth()->user();

        $feedback = Feedback::create([
            'feedback' => $feedback,
            'submitter_user_id' => $submitter->id,
            'reference_user_id' => isset($controller) ? $controller->id : null,
            'reference_position_id' => isset($position) ? $position->id : null,
            // A position already implies its area, and the two are kept mutually exclusive.
            'area_id' => ! isset($position) && ! empty($data['area']) ? (int) $data['area'] : null,
        ]);

        // Forward email if configured
        if (Setting::get('feedbackForwardEmail')) {
            $feedback->notify(new FeedbackNotification($feedback));
        }

        return redirect()->route('dashboard')->with('success', 'Feedback submitted!');

    }

    /**
     * Update the reference controller/position/area of an existing feedback
     * entry. Every reference is replaced wholesale, so an omitted or empty
     * field clears its column.
     * Validation and authorization are enforced by UpdateFeedbackRequest, the
     * latter against the entry as it stands before the update.
     */
    public function update(UpdateFeedbackRequest $request, Feedback $feedback): RedirectResponse
    {
        $data = $request->validated();

        $position = ! empty($data['position']) ? Position::where('callsign', $data['position'])->first() : null;
        $controller = ! empty($data['controller']) ? User::find($data['controller']) : null;

        $feedback->update([
            'reference_user_id' => $controller?->id,
            'reference_position_id' => $position?->id,
            'area_id' => ! empty($data['area']) ? (int) $data['area'] : null,
        ]);

        return redirect()->route('reports.feedback')->with('success', 'Feedback updated successfully!');
    }
}
