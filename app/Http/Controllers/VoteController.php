<?php

namespace App\Http\Controllers;

use App\Models\Vote;
use App\Models\VoteOption;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Controller handling votes and results
 */
class VoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('index', Vote::class);
        $votes = Vote::all();

        return view('vote.index', compact('votes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create()
    {
        $this->authorize('create', Vote::class);

        return view('vote.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('store', Vote::class);

        $data = request()->validate([
            'expire_date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'expire_time' => 'required|regex:/^\d{2}:\d{2}$/',
            'require_active' => '',
            'require_our_member' => '',
            'question' => 'required|string',
            'vote_options' => 'required|string',
        ]);

        // Concat expire date and time
        $expire = Carbon::createFromFormat('d/m/Y', $data['expire_date']);
        $expire_time = explode(':', $data['expire_time']);
        $expire->setTime($expire_time[0], $expire_time[1]);

        // Split vote options
        $options = preg_split('/\r\n|\r|\n/', $data['vote_options']);
        if (count($options) < 2) {
            return back()->withInput()->withErrors(['vote_options' => 'There must be at least two voting options separated by a new line.']);
        }

        // Only ATC active can vote ticked?
        isset($data['require_active']) ? $require_active = true : $require_active = false;
        isset($data['require_our_member']) ? $require_our_member = true : $require_our_member = false;

        // Store the new data
        $vote = new Vote();

        $vote->question = $data['question'];
        $vote->require_active = $require_active;
        $vote->require_member = $require_our_member;
        $vote->closed = false;
        $vote->end_at = $expire->format('Y-m-d H:i:s');

        $vote->save();

        foreach ($options as $option) {
            $vote_option = new VoteOption();
            $vote_option->vote_id = $vote->id;
            $vote_option->option = $option;
            $vote_option->voted = 0;
            $vote_option->save();
        }

        ActivityLogController::danger('OTHER', 'Created vote ' . $vote->id . ' ― Question: ' . $vote->question);

        return redirect()->intended(route('vote.overview'))->withSuccess('Vote succesfully created.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id  voteId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function show($id)
    {
        $vote = Vote::findOrFail($id);
        $this->isVoteValid($vote);

        return view('vote.show', compact('vote'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id  voteId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $vote = Vote::findOrFail($id);

        $this->authorize('vote', [Vote::class, $vote]);

        if (! $this->isVoteValid($vote)) {
            return back()->withInput()->withErrors('You vote could not be registered. The vote deadline has passed.');
        }

        $availableOptions = '';
        foreach ($vote->option as $option) {
            $availableOptions .= $option->id . ',';
        }

        $data = request()->validate([
            'vote' => 'required|in:' . $availableOptions,
        ]);

        $votedOption = $vote->option->where('id', $data['vote'])->first();
        $votedOption->voted = $votedOption->voted + 1;
        $votedOption->save();

        $user = \Auth::user();
        $vote->user()->attach($user);

        ActivityLogController::info('OTHER', 'Voted in vote poll ' . $vote->id);

        return redirect()->intended(route('vote.show', $vote->id))->withSuccess('Your vote is succesfully registered.');
    }

    /**
     * Check and close vote if it's still active after deadline
     * If a vote is submited after deadline and it's still open, let's close it. Cron handles closing, but this is an extra check
     *
     * @return void
     */
    private function isVoteValid(Vote $vote)
    {
        if ($vote->closed == false && Carbon::create($vote->end_at)->isPast()) {
            $vote->closed = true;
            $vote->save();

            return false;
        }

        return true;
    }
}
