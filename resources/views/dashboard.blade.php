@extends('layouts.app')

@section('title', 'Dashboard')
@section('content')

{{-- Success message fed via JS for TR  --}}
<div class="alert alert-success d-none" id="success-message"></div>

@if($dueInterestRequest)
<div class="alert alert-warning" role="alert">
    <i class="fas fa-exclamation-triangle"></i>&nbsp;&nbsp;Please confirm your continued training interest by <a href="{{ route('training.confirm.interest', ['training' => $dueInterestRequest->training->id, 'key' => $dueInterestRequest->key] ) }}">clicking here</a>, within the deadline at {{ $dueInterestRequest->deadline->toEuropeanDate() }}. Your training will be otherwise be closed.
</div>
@endif

@if($atcInactiveMessage)
<div class="alert alert-warning" role="alert">
    <i class="fas fa-exclamation-triangle"></i>&nbsp;&nbsp;Your ATC rating is marked as inactive in {{ config('app.owner_name') }}. <a href="{{ Setting::get('linkContact') }}" target="_blank">Contact {{ Setting::get('atcActivityContact') }}</a> to request a refresh or transfer training to be allowed to control in our airspace.
</div>
@endif

@if($completedTrainingMessage)
<div class="alert alert-success" role="alert">
    <i class="fas fa-star"></i>&nbsp;<b>Congratulations on your completed training!</b>&nbsp;<i class="fas fa-star"></i> You'll receive an email from VATSIM when your rating has been upgraded and ready to be used.
</div>
@endif

@if($workmailRenewal)
<div class="alert alert-warning" role="alert">
    <i class="fas fa-exclamation-triangle"></i>&nbsp;&nbsp;Your registered work e-mail address expires soon. <a href="{{ route('user.settings.extendworkmail') }}">Click here to extend for another 30 days</a>. If not extended, all e-mails will go to your default VATSIM account e-mail upon expire.
</div>
@endif

@if($activeVote)
<div class="alert alert-info" role="alert">
    <i class="fas fa-vote-yea"></i>&nbsp;&nbsp;Vote <i>"{{ $activeVote->question }}"</i> is available. Vote closes {{ \Carbon\Carbon::create($activeVote->end_at)->toEuropeanDateTime() }}. <a href="{{ route('vote.show', $activeVote) }}">Click here to vote</a>.
</div>
@endif

@if($cronJobError)
<div class="alert alert-danger" role="alert">
    <i class="fas fa-exclamation-triangle"></i>&nbsp;&nbsp;<b>Configuration Error:</b> Cronjob is not running! Are the cron jobs set up according to the manual?
</div>
@endif

@if($oudatedVersionWarning)
<div class="alert alert-info" role="alert">
    <i class="fas fa-info-circle"></i>&nbsp;&nbsp;<b>Update Available:</b> Control Center {{ Setting::get('_updateAvailable') }} is available. You are running v{{ config('app.version') }}. <a href="https://github.com/Vatsim-Scandinavia/controlcenter/releases" target="_blank">See details here.</a>
</div>
@endif

<div class="row">
    <!-- Current rating card  -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="fs-sm fw-bold text-uppercase text-gray-600 mb-1">Current Rating</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $data['rating'] }} ({{ $data['rating_short'] }})</div>
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
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="fs-sm fw-bold text-uppercase text-gray-600 mb-1">Your associated division</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">
                            {{ $data['division'] }}/{{ $data['subdivision'] }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-earth-europe fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ATC Hours card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card {{ ($atcHours < Setting::get('atcActivityRequirement', 10)) ? 'border-left-danger' : 'border-left-success' }} shadow h-100 py-2">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="fs-sm fw-bold text-success text-uppercase mb-1">ATC Hours (Last {{ Setting::get("atcActivityQualificationPeriod") }} months)</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $atcHours ? round($atcHours).' hours of '.Setting::get("atcActivityRequirement").' required' : 'N/A' }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
    
    <!-- Last training card -->
    <div class="col-xl-3 col-md-6 mb-4 d-none d-xl-block d-lg-block d-md-block">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="fs-sm fw-bold text-info text-uppercase mb-1">My last training</div>
                        <div class="row g-0 align-items-center">
                            <div class="col-auto">
                                <div class="h5 mb-0 me-3 fw-bold text-gray-800">{{ $data['report'] != null ? $data['report']->report_date->toEuropeanDate() : "-" }}</div>
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
        <div class="card shadow mb-4 d-none d-xl-block d-lg-block d-md-block">
            <!-- Card Header - Dropdown -->
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">My Students</h6>
            </div>
            <!-- Card Body -->
            <div class="card-body {{ sizeof($studentTrainings) == 0 ? '' : 'p-0' }}">
                
                @if (sizeof($studentTrainings) == 0)
                <p class="mb-0">You have no students.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Level</th>
                                <th>Area</th>
                                <th>State</th>
                                <th>Last Training</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($studentTrainings as $training)
                            <tr>
                                <td><a href="{{ $training->path() }}">{{ $training->user->name }}</a></td>
                                <td>
                                    <i class="{{ $types[$training->type]["icon"] }} text-primary"></i>
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
                                    @if($training->reports->count() > 0)
                                        @php
                                            $reportDate = Carbon\Carbon::make($training->reports->sortBy('report_date')->last()->report_date);
                                            $trainingIntervalExceeded = $reportDate->diffInDays() >= Setting::get('trainingInterval');
                                        @endphp
                                        <span title="{{ $reportDate->toEuropeanDate() }}">
                                            @if($reportDate->isToday())
                                            <span class="{{ ($trainingIntervalExceeded && $training->status != \App\Helpers\TrainingStatus::AWAITING_EXAM->value && !$training->paused_at) ? 'text-danger' : '' }}">Today</span>
                                            @elseif($reportDate->isYesterday())
                                            <span class="{{ ($trainingIntervalExceeded && $training->status != \App\Helpers\TrainingStatus::AWAITING_EXAM->value && !$training->paused_at) ? 'text-danger' : '' }}">Yesterday</span>
                                            @elseif($reportDate->diffInDays() <= 7)
                                            <span class="{{ ($trainingIntervalExceeded && $training->status != \App\Helpers\TrainingStatus::AWAITING_EXAM->value && !$training->paused_at) ? 'text-danger' : '' }}">{{ $reportDate->diffForHumans(['parts' => 1]) }}</span>
                                            @else
                                            <span class="{{ ($trainingIntervalExceeded && $training->status != \App\Helpers\TrainingStatus::AWAITING_EXAM->value && !$training->paused_at) ? 'text-danger' : '' }}">{{ $reportDate->diffForHumans(['parts' => 2]) }}</span>
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
                <h6 class="m-0 fw-bold text-white">My Trainings</h6>
            </div>
            <!-- Card Body -->
            <div class="card-body {{ $trainings->count() == 0 ? '' : 'p-0' }}">
                
                @if ($trainings->count() == 0)
                <p>You have no registered trainings.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>Level</th>
                                <th>Area</th>
                                <th>Period</th>
                                <th>State</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trainings as $training)
                            <tr>
                                <td>
                                    <a href="{{ $training->path() }}">
                                        @foreach($training->ratings as $rating)
                                        @if ($loop->last)
                                        {{ $rating->name }}
                                        @else
                                        {{ $rating->name . " + " }}
                                        @endif
                                        @endforeach
                                    </a>
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
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <!-- Card Header - Dropdown -->
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Request Training</h6>
            </div>
            <!-- Card Body -->
            <div class="card-body">
                <div class="text-center">
                    <img class="img-fluid px-3 px-sm-4 mb-4" style="width: 25rem;" src="images/undraw_speech_to_text_vatsim.svg" alt="">
                </div>
                <p>Are you interested in becoming an Air Traffic Controller? Wish to receive training for a higher rating? Request training below and you will be notified when a space is available.</p>
                
                @can('apply', \App\Models\Training::class)
                <div class="d-grid">
                    <a href="{{ route('training.apply') }}" class="btn btn-success">
                        Request training
                    </a>
                </div>
                @else
                
                <div class="btn btn-{{ (\Auth::user()->hasActiveTrainings(true) && Setting::get('trainingEnabled')) ? 'success' : 'primary' }} d-block disabled not-allowed" role="button" aria-disabled="true">
                    @if(\Auth::user()->hasActiveTrainings(true) && Setting::get('trainingEnabled'))
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
                        <a href="{{ Setting::get('linkJoin') }}" target="_blank">Read about joining here. You will be able to apply here within 24 hours after transfer.</a>
                        
                        <br>
                        
                        <b><i class="fas fa-chevron-right"></i> How to apply to be a visiting controller?</b>
                        <a href="{{ Setting::get('linkVisiting') }}" target="_blank">Check this page for more information.</a>
                        
                        <br>
                        
                        <b><i class="fas fa-chevron-right"></i> My rating is inactive?</b>
                        <a href="{{ Setting::get('linkContact') }}" target="_blank">Contact local training staff for refresh or transfer training.</a>
                        
                        <br>
                        
                        <b><i class="fas fa-chevron-right"></i> How long is the queue?</b>
                        {{ \Auth::user()->getActiveTraining()->area->waiting_time ?? 'Unknown waiting time' }}
                    </p>
                </div>
                @endif
                
                @endcan
            </div>
        </div>
    </div>
    
</div>
@endsection
