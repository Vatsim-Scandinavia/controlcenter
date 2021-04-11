@extends('layouts.app')

@section('title', 'Dashboard')
@section('content')

<!-- Success message fed via JS for TR -->
<div class="alert alert-success d-none" id="success-message"></div>

@if($dueInterestRequest)
    <div class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-triangle"></i>&nbsp;&nbsp;Please confirm your continued training interest by <a href="{{ route('training.confirm.interest', ['training' => $dueInterestRequest->training->id, 'key' => $dueInterestRequest->key] ) }}">clicking here</a>, within the deadline at {{ $dueInterestRequest->deadline->toEuropeanDate() }}. Your training will be otherwise be closed.
    </div>
@endif

@if($atcInactiveMessage)
    <div class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-triangle"></i>&nbsp;&nbsp;Your ATC rating is marked as inactive in {{ Config::get('app.owner') }}. <a href="{{ Setting::get('linkContact') }}" target="_blank">Contact local training staff</a> to request a refresh or transfer training to be allowed to control in our airspace.
    </div>
@endif

@if($activeVote)
    <div class="alert alert-info" role="alert">
        <i class="fas fa-vote-yea"></i>&nbsp;&nbsp;Vote <i>"{{ $activeVote->question }}"</i> is available. Vote closes {{ \Carbon\Carbon::create($activeVote->end_at)->toEuropeanDateTime() }}. <a href="{{ route('vote.show', $activeVote) }}">Click here to vote</a>.
    </div>
@endif

<div class="row">
    <!-- Current rating card  -->
    <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
            <div class="text-xs font-weight-bold text-uppercase text-gray-600 mb-1">Current Rating</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $data['rating'] }} ({{ $data['rating_short'] }})</div>
            </div>
            <div class="col-auto">
            <i class="fas fa-id-badge fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

    <!-- Division card -->
    <div class="col-xl-3 col-md-6 mb-4 d-none d-xl-block d-lg-block d-md-block">
    <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
            <div class="text-xs font-weight-bold text-uppercase text-gray-600 mb-1">Your associated division</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">
                {{ $data['division'] }}/{{ $data['subdivision'] }}
            </div>
            </div>
            <div class="col-auto">
            <i class="fas fa-star fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

    <!-- ATC Hours card -->
    <div class="col-xl-3 col-md-6 mb-4 d-none d-xl-block d-lg-block d-md-block">
    <div class="card {{ ($atcHours < Setting::get('atcActivityRequirement', 10)) ? 'border-left-danger' : 'border-left-success' }} shadow h-100 py-2">
        <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">ATC Hours (Last 12 months)</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $atcHours ? $atcHours.' hours of 10 required' : 'N/A' }}</div>
            </div>
            <div class="col-auto">
            <i class="fas fa-clock fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>



    <!-- Last training card -->
    <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-info shadow h-100 py-2">
        <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">My last training</div>
            <div class="row no-gutters align-items-center">
                <div class="col-auto">
                @if ($data['report'] != null) <a href="{{ $data['report']->training->path() }}"> @endif
                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">{{ $data['report'] != null ? $data['report']->report_date->toEuropeanDate() : "-" }}</div>
                @if ($data['report'] != null) </a> @endif
                </div>
            </div>
            </div>
            <div class="col-auto">
            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

</div>

<div class="row">
    <!-- Area Chart -->
    <div class="col-xl-8 col-lg-7 ">

    @if(\Auth::user()->isMentor())
        @php
            $student_trainings = \Auth::user()->mentoringTrainings();
        @endphp

            <div class="card shadow mb-4 d-none d-xl-block d-lg-block d-md-block">
                <!-- Card Header - Dropdown -->
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-white">My Students</h6>
                </div>
                <!-- Card Body -->
                <div class="card-body {{ sizeof($student_trainings) == 0 ? '' : 'p-0' }}">

                    @if (sizeof($student_trainings) == 0)
                        <p class="mb-0">You have no students.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                                <thead class="thead-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Level</th>
                                    <th>Area</th>
                                    <th>State</th>
                                    <th>Last Training</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($student_trainings as $training)
                                    <tr class="link-row" data-href="{{ $training->path() }}">
                                        <td>{{ $training->user->name }}</td>
                                        <td>
                                            @foreach($training->ratings as $rating)
                                                @if ($loop->last)
                                                    {{ $rating->name }}
                                                @else
                                                    {{ $rating->name . " + " }}
                                                @endif
                                            @endforeach
                                        </td>
                                        <td>{{ $training->area->name }}</td>
                                        <td>
                                            <i class="{{ $statuses[$training->status]["icon"] }} text-{{ $statuses[$training->status]["color"] }}"></i>&ensp;{{ $statuses[$training->status]["text"] }}{{ isset($training->paused_at) ? ' (PAUSED)' : '' }}
                                        </td>
                                        <td>
                                            @if(\App\Models\TrainingReport::where(['written_by_id' => Auth::user()->id, 'training_id' => $training->id])->count() > 0)
                                                @php
                                                    $reportDate = Carbon\Carbon::make(\App\Models\TrainingReport::where('training_id', $training->id)->get()->sortBy('report_date')->last()->report_date));
                                                    $trainingIntervalExceeded = $reportDate->diffInDays() > Setting::get('trainingInterval');
                                                @endphp
                                                <span title="{{ $reportDate->toEuropeanDate() }}">
                                                    @if($reportDate->isToday())
                                                        <span class="{{ $trainingIntervalExceeded ? 'text-danger' : '' }}">Today</span>
                                                    @elseif($reportDate->isYesterday())
                                                        <span class="{{ $trainingIntervalExceeded ? 'text-danger' : '' }}">Yesterday</span>
                                                    @elseif($reportDate->diffInDays() <= 7)
                                                        <span class="{{ $trainingIntervalExceeded ? 'text-danger' : '' }}">{{ $reportDate->diffForHumans(['parts' => 1]) }}</span>
                                                    @else
                                                        <span class="{{ $trainingIntervalExceeded ? 'text-danger' : '' }}">{{ $reportDate->diffForHumans(['parts' => 2]) }}</span>
                                                    @endif

                                                </span>
                                            @else
                                                No registered training yet
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
    @endif

    <div class="card shadow mb-4">
        <!-- Card Header - Dropdown -->
        <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-white">My Trainings</h6>
        </div>
        <!-- Card Body -->
        <div class="card-body {{ $trainings->count() == 0 ? '' : 'p-0' }}">

            @if ($trainings->count() == 0)
                <p>You have no registered trainings.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="thead-light">
                        <tr>
                            <th>Level</th>
                            <th>Area</th>
                            <th>Period</th>
                            <th>State</th>
                            <th>Reports</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($trainings as $training)
                            <tr class="link-row" data-href="{{ $training->path() }}">
                                <td>
                                    @foreach($training->ratings as $rating)
                                        @if ($loop->last)
                                            {{ $rating->name }}
                                        @else
                                            {{ $rating->name . " + " }}
                                        @endif
                                    @endforeach
                                </td>
                                <td>{{ $training->area->name }}</td>
                                <td>
                                    @if ($training->started_at == null && $training->closed_at == null)
                                        Training not started
                                    @elseif ($training->closed_at == null)
                                        {{ $training->started_at->toEuropeanDate() }} -
                                    @elseif ($training->started_at != null)
                                        {{ $training->started_at->toEuropeanDate() }} - {{ $training->closed_at->toEuropeanDate() }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    <i class="{{ $statuses[$training->status]["icon"] }} text-{{ $statuses[$training->status]["color"] }}"></i>&ensp;{{ $statuses[$training->status]["text"] }}{{ isset($training->paused_at) ? ' (PAUSED)' : '' }}
                                </td>
                                <td>
                                    <a href="{{ $training->path() }}" class="btn btn-sm btn-primary"><i class="fas fa-clipboard"></i>&nbsp;{{ sizeof($training->reports->toArray()) }}</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    </div>

    <!-- Pie Chart -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <!-- Card Header - Dropdown -->
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-white">Request Training</h6>
            </div>
            <!-- Card Body -->
            <div class="card-body">
                <div class="text-center">
                    <img class="img-fluid px-3 px-sm-4 mt-3 mb-4" style="width: 25rem;" src="images/undraw_aircraft_fbvl.svg" alt="">
                </div>
                <p>Are you interested in becoming an Air Traffic Controller? Wish to receive training for a higher rating? Request training below and you will be notified when a space is available.</p>

                @can('apply', \App\Models\Training::class)
                    <a href="{{ route('training.apply') }}" class="btn btn-success btn-block">
                        Request training
                    </a>
                @else

                    <div class="btn btn-{{ (\Auth::user()->hasActiveTrainings() && Setting::get('trainingEnabled')) ? 'success' : 'primary' }} btn-block disabled not-allowed" role="button" aria-disabled="true">
                        @if(\Auth::user()->hasActiveTrainings() && Setting::get('trainingEnabled'))
                            <i class="fas fa-check"></i>
                        @else
                            <i class="fas fa-exclamation-triangle"></i>
                        @endif
                        {{ Gate::inspect('apply', \App\Models\Training::class)->message() }}
                    </div>

                    @if(Setting::get('trainingEnabled'))
                        <div class="alert alert-primary" role="alert">
                            <p class="small">
                                <b><i class="fas fa-chevron-right"></i> How do I join the division?</b>
                                <a href="{{ Setting::get('linkJoin') }}" target="_blank">Read about joining here.</a>

                                <br>

                                <b><i class="fas fa-chevron-right"></i> How to apply to be a visiting controller?</b>
                                <a href="{{ Setting::get('linkVisiting') }}" target="_blank">Check this page for more information.</a>

                                <br>

                                <b><i class="fas fa-chevron-right"></i> My rating is inactive?</b>
                                <a href="{{ Setting::get('linkContact') }}" target="_blank">Contact local training staff for refresh or transfer training.</a>

                                <br>

                                <b><i class="fas fa-chevron-right"></i> How long is the queue?</b>
                                {{ Setting::get('trainingQueue') }}
                            </p>
                        </div>
                    @endif

                @endcan
            </div>
        </div>
    </div>

</div>
<style>
    .link-row {
        cursor: pointer;
    }
</style>
@endsection

@section('js')
    <script type="text/javascript">

        if (sessionStorage.getItem('successMessage') != null) {
            $('#success-message').removeClass('d-none');
            document.getElementById("success-message").innerHTML = "<i class=\"fas fa-check\"></i>&nbsp;&nbsp;Training successfully created and placed in queue.";
            sessionStorage.removeItem("successMessage");
        }

        $(document).ready( function () {

            $(".link-row").click(function () {
                window.location = $(this).data('href');
            });

        });

    </script>
@endsection
