<?php

namespace App\Http\Controllers;

use anlutro\LaravelSettings\Facade as Setting;
use App\Http\Requests\StoreFeedbackRequest;
use App\Http\Requests\UpdateFeedbackRequest;
use App\Models\Feedback;
use App\Models\Position;
use App\Models\User;
use App\Notifications\FeedbackNotification;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        if (! Setting::get('feedbackEnabled')) {
            return redirect()->route('dashboard')->withErrors('Feedback is currently disabled.');
        }

        $positions = Position::all();
        $controllers = User::getActiveAtcMembers();

        return view('feedback.create', compact('positions', 'controllers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
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
        ]);

        // Forward email if configured
        if (Setting::get('feedbackForwardEmail')) {
            $feedback->notify(new FeedbackNotification($feedback));
        }

        return redirect()->route('dashboard')->with('success', 'Feedback submitted!');

    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateFeedbackRequest $request, Feedback $feedback)
    {
        $this->authorize('update', $feedback);

        $data = $request->validated();

        // Track old values for logging
        $oldController = $feedback->referenceUser;
        $oldPosition = $feedback->referencePosition;
        $oldControllerId = $feedback->reference_user_id;
        $oldPositionId = $feedback->reference_position_id;

        // Get new values
        $newPosition = isset($data['position']) && ! empty($data['position']) ? Position::where('callsign', $data['position'])->first() : null;
        $newController = isset($data['controller']) && ! empty($data['controller']) ? User::find($data['controller']) : null;
        $newControllerId = $newController ? $newController->id : null;
        $newPositionId = $newPosition ? $newPosition->id : null;

        // Update the feedback
        $feedback->reference_user_id = $newControllerId;
        $feedback->reference_position_id = $newPositionId;
        $feedback->save();

        // Build log message
        $changes = [];

        if ($oldControllerId != $newControllerId) {
            $oldControllerText = $oldController ? $oldController->name . ' (' . $oldControllerId . ')' : 'N/A';
            $newControllerText = $newController ? $newController->name . ' (' . $newControllerId . ')' : 'N/A';
            $changes[] = 'Controller: ' . $oldControllerText . ' → ' . $newControllerText;
        }

        if ($oldPositionId != $newPositionId) {
            $oldPositionText = $oldPosition ? $oldPosition->callsign : 'N/A';
            $newPositionText = $newPosition ? $newPosition->callsign : 'N/A';
            $changes[] = 'Position: ' . $oldPositionText . ' → ' . $newPositionText;
        }

        if (! empty($changes)) {
            try {
                ActivityLogController::info('FEEDBACK', 'Updated feedback ' . $feedback->id . ' ― ' . implode(', ', $changes));
            } catch (\Exception $e) {
                // Log error but don't fail the request if logging fails
                Log::error('Failed to log feedback update: ' . $e->getMessage());
            }
        }

        return redirect()->route('reports.feedback')->with('success', 'Feedback updated successfully!');
    }
}
