<?php

namespace App\Http\Controllers;

use App\Vote;
use App\VoteOption;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {

        $this->authorize('store', Vote::class);

        $data = request()->validate([
            'expire_date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'expire_time' => 'required|regex:/^\d{2}:\d{2}$/',
            'require_active' => '',
            'require_vatsca_member' => '',
            'question' => 'required|string',
            'vote_options' => 'required|string'
        ]);

        // Concat expire date and time
        $expire = Carbon::createFromFormat('d/m/Y', $data['expire_date']);
        $expire_time = explode(':', $data['expire_time']);
        $expire->setTime($expire_time[0], $expire_time[1]);

        // Split vote options
        $options = preg_split('/\r\n|\r|\n/', $data['vote_options']);
        if(count($options) < 2) return back()->withInput()->withErrors(['vote_options' => 'There must be at least two voting options separated by a new line.']);

        // Only ATC active can vote ticked?
        isset($data['require_active']) ? $require_active = true : $require_active = false;
        isset($data['require_vatsca_member']) ? $require_vatsca_member = true : $require_vatsca_member = false;

        // Store the new data
        $vote = new Vote();

        $vote->question = $data['question'];
        $vote->require_active = $require_active;
        $vote->require_vatsca_member = $require_vatsca_member;
        $vote->closed = false;
        $vote->end_at = $expire->format('Y-m-d H:i:s');;

        $vote->save();

        foreach($options as $option){
            $vote_option = new VoteOption();
            $vote_option->vote_id = $vote->id;
            $vote_option->option = $option;
            $vote_option->voted = 0;
            $vote_option->save();
        }

        ActivityLogController::danger("Created vote ".$vote->id." with question ".$vote->question);

        return redirect()->intended(route('vote.overview'))->withSuccess('Vote succesfully created.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Vote  $vote
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function show($id)
    {
        $vote = Vote::findOrFail($id);
        return view('vote.show', compact('vote'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Vote  $vote
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $vote = Vote::findOrFail($id);

        $availableOptions = "";
        foreach($vote->option as $option) $availableOptions .= $option->id.',';

        $data = request()->validate([
            'vote' => 'required|in:'.$availableOptions,
        ]);

        $votedOption = $vote->option->where('id', $data['vote'])->first();
        $votedOption->voted = $votedOption->voted + 1;
        $votedOption->save();

        $user = \Auth::user();
        $vote->user()->attach($user);

        ActivityLogController::info("Voted in vote poll ".$vote->id);

        return redirect()->intended(route('vote.show', $vote->id))->withSuccess('Your vote is succesfully registered.');
    }
}
