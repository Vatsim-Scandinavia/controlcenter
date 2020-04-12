<?php

namespace App\Http\Controllers;

use App\Training;
use App\TrainingReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainingReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Training $training
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Training $training)
    {
        $this->authorize('viewReports', $training);

        $reports = TrainingReport::where('training_id', $training->id);

        foreach ($reports as $key => $report) {
            if ( ! Auth::user()->can('view', $report)) {
                $reports->pull($key);
            }
        }

        return view('trainingReport.index', compact('reports'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Training $training
     * @return void
     */
    public function create(Training $training)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\TrainingReport  $trainingReport
     * @return \Illuminate\Http\Response
     */
    public function show(TrainingReport $trainingReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\TrainingReport  $trainingReport
     * @return \Illuminate\Http\Response
     */
    public function edit(TrainingReport $trainingReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\TrainingReport  $trainingReport
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TrainingReport $trainingReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\TrainingReport  $trainingReport
     * @return \Illuminate\Http\Response
     */
    public function destroy(TrainingReport $trainingReport)
    {
        //
    }
}
