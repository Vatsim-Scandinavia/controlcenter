<?php

namespace App\Http\Controllers;

use anlutro\LaravelSettings\Facade as Setting;
use App\Http\Requests\StoreFeedbackRequest;
use App\Http\Requests\UpdateFeedbackRequest;
use App\Models\Feedback;
use App\Models\Position;
use App\Models\User;
use App\Notifications\FeedbackNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FeedbackController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {

        if (! Setting::get('feedbackEnabled')) {
            return redirect()->route('dashboard')->withErrors('Feedback is currently disabled.');
        }

        $this->authorize('create', Feedback::class);

        $positions = Position::all();
        $controllers = User::getActiveAtcMembers();

        return view('feedback.create', compact('positions', 'controllers'));
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

        $this->authorize('create', Feedback::class);

        $data = $request->validate([
            'position' => 'nullable|exists:positions,callsign',
            'controller' => 'nullable|exists:users,id',
            'time' => 'nullable|string',
            'feedback' => 'required',
        ]);

        $position = isset($data['position']) ? Position::where('callsign', $data['position'])->get()->first() : null;
        $controller = isset($data['controller']) ? User::find($data['controller']) : null;
        $feedback = $data['feedback'];
        $time = $data['time'];

        $submitter = auth()->user();

        if($submitter->is($controller)){
            return redirect()->back()->withErrors(["You can not write feedback for yourself"]);
        }

        $feedback = Feedback::create([
            'feedback' => $feedback,
            'submitter_user_id' => $submitter->id,
            'reference_user_id' => isset($controller) ? $controller->id : null,
            'reference_position_id' => isset($position) ? $position->id : null,
            'time' => $time,
        ]);

        // Forward email if configured
        if (Setting::get('feedbackForwardEmail')) {
            $feedback->notify(new FeedbackNotification($feedback));
        }

        return redirect()->route('dashboard')->with('success', 'Feedback submitted!');

    }

    public function show($id = null){

        if($id){
            $user = User::find($id);

            $this->authorize('view', [Feedback::class, $user]);
        } else {
            $this->authorize('viewAll', Feedback::class);
        }

        $feedback = Feedback::orderBy('created_at', 'DESC');

        if($id){
            $feedback = $feedback->where('reference_user_id', $id)->where('published', 1);
        }

        $feedback = $feedback->paginate(15);

        $feedbackUser = User::find($id);

        return view('feedback.show', compact('feedback', 'feedbackUser'));

    }

    public function acknowledge(Request $r){

        $this->authorize('acknowledge', Feedback::class);

        $data = $r->validate([
            'feedback_id' => 'required|exists:feedback,id'
        ]);

        $id = $data['feedback_id'];

        $feedback = Feedback::find($id);

        $feedback->acknowledged = true;

        $feedback->save();

        return response()->json(['success' => 'success'], 200);
    }

    public function publish(Request $r){

        $this->authorize('publish', Feedback::class);

        $data = $r->validate([
            'feedback_id' => 'required|exists:feedback,id'
        ]);

        $id = $data['feedback_id'];

        $feedback = Feedback::find($id);

        $feedback->acknowledged = true;
        $feedback->published = true;

        $feedback->save();

        return response()->json(['success' => 'success'], 200);
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
