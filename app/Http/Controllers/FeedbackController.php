<?php

namespace App\Http\Controllers;

use anlutro\LaravelSettings\Facade as Setting;
use App\Http\Requests\StoreFeedbackRequest;
use App\Http\Requests\UpdateFeedbackRequest;
use App\Models\Feedback;
use App\Models\Position;
use App\Models\User;
use App\Notifications\FeedbackNotification;
use App\Services\PilotFlightHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class FeedbackController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(PilotFlightHistoryService $flightHistory)
    {

        if (! Setting::get('feedbackEnabled')) {
            return redirect()->route('dashboard')->withErrors('Feedback is currently disabled.');
        }

        $positions = Position::all();
        $controllers = User::getActiveAtcMembers();
        $flights = $flightHistory->recentFlights(auth()->id());

        return view('feedback.create', compact('positions', 'controllers', 'flights'));
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
            'pilot_callsign' => $data['pilot_callsign'] ?? null,
            'event_date' => $data['event_date'] ?? null,
            'departure_airport' => $data['departure_airport'] ?? null,
            'arrival_airport' => $data['arrival_airport'] ?? null,
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
