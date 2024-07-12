@extends('layouts.app')

@section('title', 'Training')
@section('title-flex')
    <div>
        @can('close', $training)
            <a href="{{ route('training.action.close', $training->id) }}" onclick="return confirm('Are you sure you want to close your training?')" class="btn btn-danger"><i class="fas fa-xmark"></i> Close my training</a>
        @endcan
        @can('togglePreTrainingCompleted', $training)
            @if($training->pre_training_completed)
                <a href="{{ route('training.action.pretraining', $training->id) }}" onclick="return confirm('Are you sure you want to mark this pre-training as not completed?')" class="btn btn-primary"><i class="fas fa-xmark"></i> Mark pre-training as not completed</a>
            @else
                <a href="{{ route('training.action.pretraining', $training->id) }}" onclick="return confirm('Are you sure you want to mark this pre-training as completed?')" class="btn btn-success"><i class="fas fa-check"></i> Mark pre-training as completed</a>
            @endif
        @endcan
    </div>
@endsection
@section('content')

@if($training->status < \App\Helpers\TrainingStatus::COMPLETED->value && $training->status != \App\Helpers\TrainingStatus::CLOSED_BY_STUDENT->value)
    <div class="alert alert-warning" role="alert">
        <b>Training is closed with reason: </b>
        @if(isset($training->closed_reason))
            {{ $training->closed_reason }}
        @else
            No reason given
        @endif
    </div>
@endif

@if($training->status == \App\Helpers\TrainingStatus::CLOSED_BY_STUDENT->value)
    <div class="alert alert-warning" role="alert">
        <b>Training closed by student</b>
    </div>
@endif

<div id="otl-alert" class="alert alert-info" style="display: none" role="alert">
    <b id="otl-type"></b><br>
    <i class="fa fa-clock"></i>&nbsp;Valid for 7 days<br>
    <i class="fa fa-link"></i>&nbsp;<a id="otl-link" href=""></a>&nbsp;<button type="button" id="otl-link-copy-btn" class="btn btn-sm"><i class="fas fa-copy"></i></button>
</div>

<div class="row">
    <div class="col-xl-3 col-md-12 col-sm-12 mb-12">
        <div class="card shadow mb-2">
            <div class="card-header bg-primary py-3 d-flex flex-row column-gap-3 pe-0">
                <h6 class="m-0 fw-bold text-white flex-grow-1">
                    <i class="fas fa-flag"></i>&nbsp;{{ $training->user->first_name }}'s training for
                    @foreach($training->ratings as $rating)
                        @if ($loop->last)
                            {{ $rating->name }}
                        @else
                            {{ $rating->name . " + " }}
                        @endif
                    @endforeach
                </h6>

                @can('create', [\App\Models\Task::class])
                    <button class="btn btn-light btn-icon dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-hand"></i> Request
                    </button>
                    <div class="dropdown">
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @foreach($requestTypes as $requestType)
                                @if($requestType->allowNonVatsimRatings() == true || ($requestType->allowNonVatsimRatings() == false && $training->hasVatsimRatings() == true))
                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#{{ Str::camel($requestType->getName()) }}">
                                        <i class="fas {{ $requestType->getIcon() }}"></i>&nbsp;
                                        {{ $requestType->getName() }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endcan

            </div>
            <div class="card-body">
                <dl class="copyable">
                    <dt>State</dt>
                    <dd>
                        <i class="{{ $statuses[$training->status]["icon"] }} text-{{ $statuses[$training->status]["color"] }}"></i>
                        @if($training->status == \App\Helpers\TrainingStatus::PRE_TRAINING->value && $training->pre_training_completed )
                            <i class="fas fa-check text-success"></i>
                        @endif
                        {{ $statuses[$training->status]["text"] }}
                        {{ isset($training->paused_at) ? ' (PAUSED)' : '' }}
                    </dd>

                    <dt>Type</dt>
                    <dd><i class="{{ $types[$training->type]["icon"] }} text-primary"></i>&ensp;{{ $types[$training->type]["text"] }}</dd>

                    <dt>Level</dt>
                    <dd class="separator pb-3">
                        @if ( is_iterable($ratings = $training->ratings->toArray()) )
                            @for( $i = 0; $i < sizeof($ratings); $i++ )
                                @if ( $i == (sizeof($ratings) - 1) )
                                    {{ $ratings[$i]["name"] }}
                                @else
                                    {{ $ratings[$i]["name"] . " + " }}
                                @endif
                            @endfor
                        @else
                            {{ $ratings["name"] }}
                        @endif
                    </dd>

                    <dt class="pt-2">Vatsim ID</dt>
                    <dd>
                        <a href="{{ route('user.show', $training->user->id) }}">
                            {{ $training->user->id }}
                        </a>
                        <button type="button" onclick="navigator.clipboard.writeText('{{ $training->user->id }}')"><i class="fas fa-copy"></i></button>
                        <a href="https://stats.vatsim.net/stats/{{ $training->user->id }}" target="_blank" title="VATSIM Stats" class="link-btn me-1"><i class="fas fa-chart-simple"></i></button></a>
                        @if($training->user->division == 'EUD')
                            <a href="https://core.vateud.net/manage/controller/{{ $training->user->id }}/view" target="_blank" title="VATEUD Core Profile" class="link-btn"><i class="fa-solid fa-earth-europe"></i></button></a>
                        @endif
                    </dd>

                    <dt>Name</dt>
                    <dd class="separator pb-3"><a href="{{ route('user.show', $training->user->id) }}">{{ $training->user->name }}</a><button type="button" onclick="navigator.clipboard.writeText('{{ $training->user->first_name.' '.$training->user->last_name }}')"><i class="fas fa-copy"></i></button></dd>

                    <dt class="pt-2">Area</dt>
                    <dd>{{ $training->area->name }}</dd>

                    <dt>Mentor</dt>
                    <dd class="separator pb-3">{{ !empty($training->getInlineMentors()) ? $training->getInlineMentors() : '-' }}</dd>

                    <dt class="pt-2">Period</dt>
                    <dd>
                        @if ($training->started_at == null && $training->closed_at == null)
                            Training not started
                        @elseif ($training->closed_at == null)
                            {{ $training->started_at->toEuropeanDate() }} -
                        @elseif ($training->started_at != null)
                            {{ $training->started_at->toEuropeanDate() }} - {{ $training->closed_at->toEuropeanDate() }}
                        @else
                            N/A
                        @endif
                    </dd>

                    <dt>Applied</dt>
                    <dd>{{ $training->created_at->toEuropeanDate() }}</dd>

                    <dt>Closed</dt>
                    <dd>
                        @if ($training->closed_at != null)
                            {{ $training->closed_at->toEuropeanDate() }}
                        @else
                            -
                        @endif
                    </dd>
                </dl>

                @can('edit', [\App\Models\Training::class, $training])
                    <a href="{{ route('training.edit', $training->id) }}" class="btn btn-outline-primary btn-icon"><i class="fas fa-pencil"></i>&nbsp;Edit training</a>
                @endcan
            </div>
        </div>

        @can('update', $training)
            <div class="card shadow mb-4">

                <div class="card-body">
                    <form action="{{ route('training.update.details', ['training' => $training->id]) }}" method="POST">
                        @method('PATCH')
                        @csrf

                        <div class="mb-3">

                            @if($activeTrainingInterest)
                                <div class="alert alert-warning" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i>&nbsp;This training has an active interest request pending.
                                </div>
                            @endif

                            <label class="form-label" for="trainingStateSelect">Select training state</label>
                            <select class="form-select" name="status" id="trainingStateSelect" @if(!Auth::user()->isModeratorOrAbove()) disabled @endif>
                                @foreach($statuses as $id => $data)
                                    @if($data["assignableByStaff"])
                                        @if($id == $training->status)
                                            <option value="{{ $id }}" selected>{{ $data["text"] }}</option>
                                        @else
                                            <option value="{{ $id }}">{{ $data["text"] }}</option>
                                        @endif
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3" id="closedReasonInput" style="display: none">
                            <label class="form-label" for="trainingCloseReason">Closed reason</label>
                            <input type="text" id="trainingCloseReason" class="form-control" name="closed_reason" placeholder="{{ $training->closed_reason }}" maxlength="65">
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check1" name="paused_at" {{ $training->paused_at ? "checked" : "" }} @if(!Auth::user()->isModeratorOrAbove()) disabled @endif>
                            <label class="form-check-label" for="check1">
                                Paused
                                @if(isset($training->paused_at))
                                    <span class='badge bg-danger'>{{ \Carbon\Carbon::create($training->paused_at)->diffForHumans(['parts' => 2]) }}</span>
                                @endif
                            </label>
                        </div>

                        <hr>

                        @if (\Auth::user()->isModeratorOrAbove())
                        <div class="mb-3">
                            <label class="form-label" for="assignMentors">Assigned mentors: <span class="badge bg-secondary">Ctrl/Cmd+Click</span> to select multiple</label>
                            <select multiple class="form-select" name="mentors[]" id="assignMentors">
                                @foreach($trainingMentors as $mentor)
                                    <option value="{{ $mentor->id }}" {{ ($training->mentors->contains($mentor->id)) ? "selected" : "" }}>{{ $mentor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <button type="submit" class="btn btn-primary">Save</button>

                    </form>
                </div>
            </div>
        @endcan

    </div>

    <div class="col-xl-4 col-md-6 col-sm-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Timeline
                </h6>
            </div>
            @can('comment', [\App\Models\TrainingActivity::class, \App\Models\Training::find($training->id)])
                <form action="{{ route('training.activity.comment') }}" method="POST">
                    @csrf
                    <div class="input-group">
                        <input type="hidden" name="training_id" value="{{ $training->id }}">
                        <input type="hidden" name="update_id" id="activity_update_id" value="">
                        <input type="text" name="comment" id="activity_comment" class="form-control border" placeholder="Your internal comment ..." maxlength="512">
                        <button class="btn btn-outline-primary" id="activity_button" type="submit">Comment</button>
                    </div>
                </form>
            @endcan
            <div class="timeline">
                <ul class="sessions">
                    @foreach($activities as $activity)
                        @can('view', [\App\Models\TrainingActivity::class, \App\Models\Training::find($training->id), $activity->type])
                            <li data-id="{{ $activity->id }}">
                                <div class="time">
                                    @if($activity->type == "STATUS" || $activity->type == "TYPE")
                                        <i class="fas fa-right-left"></i>
                                    @elseif($activity->type == "MENTOR")
                                        @if($activity->new_data)
                                            <i class="fas fa-user-plus"></i>
                                        @elseif($activity->old_data)
                                            <i class="fas fa-user-minus"></i>
                                        @endif
                                    @elseif($activity->type == "PAUSE")
                                        <i class="fas fa-circle-pause"></i>
                                    @elseif($activity->type == "ENDORSEMENT")
                                        <i class="fas fa-check-square"></i>
                                    @elseif($activity->type == "COMMENT")
                                        <i class="fas fa-comment"></i>
                                    @elseif($activity->type == 'PRETRAINING')
                                        <i class="fas fa-graduation-cap"></i>
                                    @endif

                                    @isset($activity->triggered_by_id)
                                        {{ \App\Models\User::find($activity->triggered_by_id)->name }} —
                                    @endisset

                                    {{ $activity->created_at->toEuropeanDateTime() }}
                                    @can('comment', [\App\Models\TrainingActivity::class, \App\Models\Training::find($training->id)])
                                        @if($activity->type == "COMMENT" && now() <= $activity->created_at->addDays(1) && $activity->triggered_by_id == \Auth::user()->id)
                                            <button class="btn btn-sm float-end" onclick="updateComment({{ $activity->id }}, '{{ $activity->comment }}')"><i class="fas fa-pencil"></i></button>
                                        @endif
                                    @endcan
                                </div>
                                <p>

                                    @if($activity->type == "STATUS")
                                        @if(($activity->new_data == -2 || $activity->new_data == -4) && isset($activity->comment))
                                            Status changed from <span class="badge text-bg-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->old_data]["text"] }}</span>
                                        to <span class="badge text-bg-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->new_data]["text"] }}</span>
                                        with reason <span class="badge text-bg-light">{{ $activity->comment }}</span>
                                        @else
                                            Status changed from <span class="badge text-bg-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->old_data]["text"] }}</span>
                                        to <span class="badge text-bg-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->new_data]["text"] }}</span>
                                        @endif
                                    @elseif($activity->type == "TYPE")
                                        Training type changed from <span class="badge text-bg-light">{{ \App\Http\Controllers\TrainingController::$types[$activity->old_data]["text"] }}</span>
                                        to <span class="badge text-bg-light">{{ \App\Http\Controllers\TrainingController::$types[$activity->new_data]["text"] }}</span>
                                    @elseif($activity->type == "MENTOR")
                                        @if($activity->new_data)
                                            <span class="badge text-bg-light">{{ \App\Models\User::find($activity->new_data)->name }}</span> assigned as mentor
                                        @elseif($activity->old_data)
                                        <span class="badge text-bg-light">{{ \App\Models\User::find($activity->old_data)->name }}</span> removed as mentor
                                        @endif
                                    @elseif($activity->type == "PAUSE")
                                        @if($activity->new_data)
                                            Training paused
                                        @else
                                            Training unpaused
                                        @endif
                                    @elseif($activity->type == "ENDORSEMENT")
                                        @if(\App\Models\Endorsement::find($activity->new_data) !== null)
                                            @empty($activity->comment)
                                                <span class="badge text-bg-light">
                                                    {{ str(\App\Models\Endorsement::find($activity->new_data)->type)->lower()->ucfirst() }} endorsement
                                                </span> granted, valid to
                                                <span class="badge text-bg-light">
                                                    @isset(\App\Models\Endorsement::find($activity->new_data)->valid_to)
                                                        {{ \App\Models\Endorsement::find($activity->new_data)->valid_to->toEuropeanDateTime() }}
                                                    @else
                                                        Forever
                                                    @endisset
                                                </span>
                                            @else
                                                <span class="badge text-bg-light">
                                                    {{ str(\App\Models\Endorsement::find($activity->new_data)->type)->lower()->ucfirst() }} endorsement
                                                </span> granted, valid to
                                                <span class="badge text-bg-light">
                                                    @isset(\App\Models\Endorsement::find($activity->new_data)->valid_to)
                                                        {{ \App\Models\Endorsement::find($activity->new_data)->valid_to->toEuropeanDateTime() }}
                                                    @else
                                                        Forever
                                                    @endisset
                                                </span>
                                                for positions:
                                                @foreach(explode(',', $activity->comment) as $p)
                                                    <span class="badge text-bg-light">{{ $p }}</span>
                                                @endforeach
                                            @endempty
                                        @endif
                                    @elseif($activity->type == "COMMENT")
                                        {!! nl2br($activity->comment) !!}

                                        @if($activity->created_at != $activity->updated_at)
                                            <span class="text-muted">(edited)</span>
                                        @endif
                                    @elseif($activity->type == "PRETRAINING")
                                        Pre-training marked as
                                        <span class="badge text-bg-light">
                                            @if($activity->new_data)
                                                <i class="fas fa-check"></i>
                                                Completed
                                            @else
                                                <i class="fas fa-xmark"></i>
                                                Not completed
                                            @endif
                                        </span>
                                    @endif

                                </p>
                            </li>
                        @endcan
                    @endforeach
                    <li>
                        <div class="time">
                            <i class="fas fa-flag"></i>
                            @isset($training->created_by)
                                {{ \App\Models\User::find($training->created_by)->name }} —
                            @endisset 
                            {{ $training->created_at->toEuropeanDateTime() }}
                        </div>
                        <p>
                            Training created
                        </p>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Application
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="card bg-light mb-3">
                    <div class="card-body">

                        @if($training->english_only_training)
                            <i class="fas fa-flag-usa"></i>&nbsp;&nbsp;Requesting training in English only<br>
                        @else
                            <i class="fas fa-flag"></i>&nbsp;&nbsp;Requesting training in local language or English<br>
                        @endif

                        @isset($training->experience)
                            <i class="fas fa-book"></i>&nbsp;&nbsp;{{ $experiences[$training->experience]["text"] }}
                        @endisset

                    </div>
                </div>
            </div>

            <div class="p-4">
                <p class="fw-bold text-primary">
                    <i class="fas fa-envelope-open-text"></i>&nbsp;Letter of motivation
                </p>

                @if(empty($training->motivation))
                    <p><i>Not provided / relevant</i></p>
                @else
                    <p>{{ $training->motivation }}</p>
                @endif
            </div>
        </div>

    </div>

    <div class="col-xl-5 col-md-6 col-sm-12 mb-12">

        <div class="card shadow mb-4 ">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">

                @if($training->status >= \App\Helpers\TrainingStatus::PRE_TRAINING->value && $training->status <= \App\Helpers\TrainingStatus::AWAITING_EXAM->value)
                    <h6 class="m-0 fw-bold text-white">
                @else
                    <h6 class="m-0 mt-1 mb-2 fw-bold text-white">
                @endif
                    Training Reports
                </h6>

                @if(
                    \Auth::user()->can('create', [\App\Models\OneTimeLink::class, $training, \App\Models\OneTimeLink::TRAINING_REPORT_TYPE]) ||
                    \Auth::user()->can('create', [\App\Models\OneTimeLink::class, $training, \App\Models\OneTimeLink::TRAINING_EXAMINATION_TYPE]) ||
                    ($training->status >= \App\Helpers\TrainingStatus::PRE_TRAINING->value && $training->status <= \App\Helpers\TrainingStatus::AWAITING_EXAM->value)
                )
                    <div class="dropdown" style="display: inline;">
                        <button class="btn btn-light btn-icon dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-plus"></i> Create
                        </button>
                    
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @can('create', [\App\Models\TrainingReport::class, $training])
                                @if($training->status >= \App\Helpers\TrainingStatus::PRE_TRAINING->value)
                                    <a class="dropdown-item" href="{{ route('training.report.create', ['training' => $training->id]) }}"><i class="fas fa-file"></i> Training Report</a>
                                @endif
                            @else
                                <a class="dropdown-item disabled" href="#"><i class="fas fa-lock"></i>&nbsp;Training Report</a>
                            @endcan

                            @can('create', [\App\Models\TrainingExamination::class, $training])
                                @if($training->status == \App\Helpers\TrainingStatus::AWAITING_EXAM->value)
                                    <a class="dropdown-item" href="{{ route('training.examination.create', ['training' => $training->id]) }}"><i class="fas fa-file"></i> Exam Report</a>
                                @endif
                            @else
                                <a class="dropdown-item disabled" href="#"><i class="fas fa-lock"></i>&nbsp;Exam Report</a>
                            @endcan

                            @can('create', [\App\Models\OneTimeLink::class, $training, \App\Models\OneTimeLink::TRAINING_REPORT_TYPE])
                                <button class="dropdown-item" id="getOneTimeLinkReport"><i class="fas fa-link"></i> Report one-time link</button>
                            @endif
                            @can('create', [\App\Models\OneTimeLink::class, $training, \App\Models\OneTimeLink::TRAINING_EXAMINATION_TYPE])
                                <button class="dropdown-item" id="getOneTimeLinkExam"><i class="fas fa-link"></i> Examination one-time link</button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            <div class="card-body p-0">

                @can('viewAny', [\App\Models\TrainingReport::class, $training])
                    <div class="accordion" id="reportAccordion">
                        @if ($reportsAndExams->count() == 0)
                            <div class="card-text text-primary p-3">
                                No training reports yet.
                            </div>
                        @else

                            @foreach($reportsAndExams as $reportModel)
                                @if(is_a($reportModel, '\App\Models\TrainingReport'))

                                    @if(!$reportModel->draft || $reportModel->draft && \Auth::user()->isMentorOrAbove())

                                        @php
                                            $uuid = "instance-".Ramsey\Uuid\Uuid::uuid4();
                                        @endphp

                                        <div class="card">
                                            <div class="card-header p-0">
                                                <h5 class="mb-0">
                                                    <button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $uuid }}" aria-expanded="true">
                                                        <i class="fas fa-fw fa-chevron-right me-2"></i>{{ $reportModel->report_date->toEuropeanDate() }}
                                                        @if($reportModel->draft)
                                                            <span class='badge bg-danger'>Draft</span>
                                                        @endif
                                                    </button>
                                                </h5>
                                            </div>

                                            <div id="{{ $uuid }}" class="collapse" data-bs-parent="#reportAccordion">
                                                <div class="card-body">

                                                    <small class="text-muted">
                                                        @if(isset($reportModel->position))
                                                            <i class="fas fa-map-marker-alt"></i> {{ $reportModel->position }}&emsp;
                                                        @endif
                                                        <i class="fas fa-user-edit"></i> {{ isset(\App\Models\User::find($reportModel->written_by_id)->name) ? \App\Models\User::find($reportModel->written_by_id)->name : "Unknown"  }}
                                                        @can('update', $reportModel)
                                                            <a class="float-end" href="{{ route('training.report.edit', $reportModel->id) }}"><i class="fa fa-pen-square"></i> Edit</a>
                                                        @endcan
                                                    </small>

                                                    <div class="mt-2" id="markdown-content">
                                                        @markdown($reportModel->content)
                                                    </div>

                                                    @if(isset($reportModel->contentimprove) && !empty($reportModel->contentimprove))
                                                        <hr>
                                                        <p class="fw-bold text-primary">
                                                            <i class="fas fa-clipboard-list-check"></i>&nbsp;Areas to improve
                                                        </p>
                                                        <div id="markdown-improve">
                                                            @markdown($reportModel->contentimprove)
                                                        </div>
                                                    @endif

                                                    @if($reportModel->attachments->count() > 0)
                                                        <hr>
                                                        @foreach($reportModel->attachments as $attachment)
                                                            <div>
                                                                <a href="{{ route('training.object.attachment.show', ['attachment' => $attachment]) }}" target="_blank">
                                                                    <i class="fa fa-file"></i>&nbsp;{{ $attachment->file->name }}
                                                                </a>
                                                            </div>
                                                        @endforeach
                                                    @endif

                                                </div>
                                            </div>
                                        </div>

                                    @endif


                                @else


                                    @php
                                        $uuid = "instance-".Ramsey\Uuid\Uuid::uuid4();
                                    @endphp

                                    <div class="card">
                                        <div class="card-header p-0">
                                            <h5 class="mb-0 bg-lightorange">
                                                <button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $uuid }}" aria-expanded="true">
                                                    <i class="fas fa-fw fa-chevron-right me-2"></i>{{ $reportModel->examination_date->toEuropeanDate() }}
                                                </button>
                                            </h5>
                                        </div>

                                        <div id="{{ $uuid }}" class="collapse" data-bs-parent="#reportAccordion">
                                            <div class="card-body">

                                                <small class="text-muted">
                                                    @if(isset($reportModel->position))
                                                        <i class="fas fa-map-marker-alt"></i> {{ \App\Models\Position::find($reportModel->position_id)->callsign }}&emsp;
                                                    @endif
                                                    <i class="fas fa-user-edit"></i> {{ isset(\App\Models\User::find($reportModel->examiner_id)->name) ? \App\Models\User::find($reportModel->examiner_id)->name : "Unknown" }}
                                                    @can('delete', [\App\Models\TrainingExamination::class, $reportModel])
                                                        <a class="float-end" href="{{ route('training.examination.delete', $reportModel->id) }}" onclick="return confirm('Are you sure you want to delete this examination?')"><i class="fa fa-trash"></i> Delete</a>
                                                    @endcan
                                                </small>

                                                <div class="mt-2">
                                                    @if($reportModel->result == "PASSED")
                                                        <span class='badge bg-success'>PASSED</span>
                                                    @elseif($reportModel->result == "FAILED")
                                                        <span class='badge bg-danger'>FAILED</span>
                                                    @elseif($reportModel->result == "INCOMPLETE")
                                                        <span class='badge bg-primary'>INCOMPLETE</span>
                                                    @elseif($reportModel->result == "POSTPONED")
                                                        <span class='badge bg-warning'>POSTPONED</span>
                                                    @endif
                                                </div>

                                                @if($reportModel->attachments->count() > 0)
                                                    @foreach($reportModel->attachments as $attachment)
                                                        <div>
                                                            <a href="{{ route('training.object.attachment.show', ['attachment' => $attachment]) }}" target="_blank">
                                                                <i class="fa fa-file"></i>&nbsp;{{ $attachment->file->name }}
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                @endif

                                            </div>
                                        </div>
                                    </div>
                                @endif


                            @endforeach
                        @endif
                    </div>
                @else
                    <div class="card-text text-primary p-3">
                        You don't have access to see the training reports.
                    </div>
                @endcan

            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Training Interest Confirmations
                </h6>
            </div>
            <div class="card-body {{ $trainingInterests->count() == 0 ? '' : 'p-0' }}">

                @if($trainingInterests->count() == 0)
                    <p class="mb-0">No confirmation history</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                            <thead class="table-light">
                                <tr>
                                    <th>Interest sent</th>
                                    <th>Confirmation Deadline</th>
                                    <th>Interest confirmed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trainingInterests as $interest)
                                <tr>
                                    <td>
                                        {{ $interest->created_at->toEuropeanDate() }}
                                    </td>
                                    <td>
                                        {{ $interest->deadline->toEuropeanDate() }}
                                    </td>
                                    <td>
                                        @if($interest->confirmed_at)
                                            <i class="fas fa-check text-success"></i>&nbsp;{{ $interest->confirmed_at->toEuropeanDate() }}
                                        @elseif($interest->expired)
                                            @if($interest->expired == 1)
                                                <i class="fas fa-times text-warning"></i>&nbsp;Invalidated
                                            @else
                                                <i class="fas fa-times text-danger"></i>&nbsp;Not confirmed
                                            @endif
                                        @else
                                            <i class="fas fa-hourglass text-warning"></i>&nbsp;Awaiting confirmation
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

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Related Tasks
                </h6>
            </div>
            <div class="card-body {{ $relatedTasks->count() == 0 ? '' : 'p-0' }}">

                @if($relatedTasks->count() == 0)
                    <p class="mb-0">No related task history</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                            <thead class="table-light">
                                <tr>
                                    <th>Task</th>
                                    <th>Creator</th>
                                    <th>Assignee</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($relatedTasks as $task)
                                <tr>
                                    <td>
                                        <i class="fas {{ $task->type()->getIcon() }}" data-bs-toggle="tooltip" data-bs-placement="top"></i>
                                        {{ $task->type()->getText($task) }}
                                    </td>
                                    <td>
                                        {{ $task->creator->name }}
                                    </td>
                                    <td>
                                        {{ $task->assignee->name }}
                                    </td>
                                    <td>
                                        @if($task->status == \App\Helpers\TaskStatus::COMPLETED)
                                            <i class="fas fa-check text-success"></i>
                                        @elseif($task->status == \App\Helpers\TaskStatus::DECLINED)
                                            <i class="fas fa-times text-danger"></i>
                                        @elseif($task->status == \App\Helpers\TaskStatus::PENDING)
                                            <i class="fas fa-hourglass text-warning"></i>
                                        @endif

                                        @if($task->status == \App\Helpers\TaskStatus::COMPLETED || $task->status == \App\Helpers\TaskStatus::DECLINED)
                                            <span class="text-muted" title="{{ $task->closed_at->toEuropeanDateTime() }}">{{ $task->closed_at->diffForHumans() }}</span>
                                        @else
                                            <span class="text-muted" title="{{ $task->created_at->toEuropeanDateTime() }}">{{ $task->created_at->diffForHumans() }}</span>
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

    </div>
</div>

@foreach($requestTypes as $requestType)
    @if($requestType->allowNonVatsimRatings() == true || ($requestType->allowNonVatsimRatings() == false && $training->hasVatsimRatings() == true))
        @include('training.parts.taskmodal', ['requestType' => $requestType, 'training' => $training])
    @endif
@endforeach


@endsection

@section('js')

    <!-- One Time Links -->
    <script>

        // Generate a one time report link
        var getOneTimeLinkReport = document.getElementById('getOneTimeLinkReport')
        if(getOneTimeLinkReport){
            getOneTimeLinkReport.addEventListener('click', async function (event) {
                event.preventDefault();
                event.target.disabled = true
                let route = await getOneTimeLink('{!! \App\Models\OneTimeLink::TRAINING_REPORT_TYPE !!}');
                event.target.disabled = false

                document.getElementById('otl-alert').style.display = "block";
                document.getElementById('otl-type').innerHTML = "Training Report one-time link";
                document.getElementById('otl-link').href = route
                document.getElementById('otl-link').innerHTML = route
                document.getElementById('otl-link-copy-btn').onclick = function(){navigator.clipboard.writeText(route)}
            });
        }


        // Generate a one time exam report link
        var getOneTimeLinkExam = document.getElementById('getOneTimeLinkExam')
        if(getOneTimeLinkExam){
            getOneTimeLinkExam.addEventListener('click', async function (event) {
                event.preventDefault();
                event.target.disabled = true
                let route = await getOneTimeLink('{!! \App\Models\OneTimeLink::TRAINING_EXAMINATION_TYPE !!}');
                event.target.disabled = false

                document.getElementById('otl-alert').style.display = "block";
                document.getElementById('otl-type').innerHTML = "Examination Report";
                document.getElementById('otl-link').href = route
                document.getElementById('otl-link').innerHTML = route
                document.getElementById('otl-link-copy-btn').onclick = function(){navigator.clipboard.writeText(route)}
            });
        }

        async function getOneTimeLink(type) {
            return '{!! env('APP_URL') !!}' + '/training/onetime/' + await getOneTimeLinkKey(type);
        }

        async function getOneTimeLinkKey(type) {
            let key;

            const response = await fetch('{{ route('training.onetimelink.store', ['training' => $training]) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{!! csrf_token() !!}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ type }),
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                } else {
                    console.error(response);
                    alert('An error occurred while trying to generate the one-time link.');
                }
            })
            .catch(error => {
                console.error(error);
                alert('An error occurred while trying to generate the one-time link.');
            })

            return response.key;
        }

        function updateComment(id, oldText){
            document.getElementById('activity_update_id').value = id
            document.getElementById('activity_comment').value = oldText
            document.getElementById('activity_button').innerHTML = 'Update'

            // flash the activity_comment field yellow for a second
            document.getElementById('activity_comment').style.backgroundColor = '#fff7bd'
            document.getElementById('activity_comment').style.transition = 'background-color 100ms linear'
            setTimeout(function(){
                document.getElementById('activity_comment').style.backgroundColor = '#ffffff'
            }, 750)

        }

    </script>

    <!-- Training report accordian -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Add minus icon for collapse element which is open by default
            var showCollapses = document.querySelectorAll(".collapse.show");
            showCollapses.forEach(function(collapse) {
                var cardHeader = collapse.previousElementSibling;
                var icon = cardHeader.querySelector(".fas");
                if (icon) {
                    icon.classList.add("fa-chevron-down");
                    icon.classList.remove("fa-chevron-right");
                }
            });

            // Toggle plus minus icon on show hide of collapse element
            var collapses = document.querySelectorAll(".collapse");
            collapses.forEach(function(collapse) {
                collapse.addEventListener('show.bs.collapse', function() {
                    var cardHeader = collapse.previousElementSibling;
                    var icon = cardHeader.querySelector(".fas");
                    if (icon) {
                        icon.classList.remove("fa-chevron-right");
                        icon.classList.add("fa-chevron-down");
                    }
                });

                collapse.addEventListener('hide.bs.collapse', function() {
                    var cardHeader = collapse.previousElementSibling;
                    var icon = cardHeader.querySelector(".fas");
                    if (icon) {
                        icon.classList.remove("fa-chevron-down");
                        icon.classList.add("fa-chevron-right");
                    }
                });
            });

            // Closure reason input
            var trainingStateSelect = document.querySelector('#trainingStateSelect');
            if(trainingStateSelect){
                toggleClosureReasonField(document.querySelector('#trainingStateSelect').value);

                var trainingStateSelect = document.querySelector('#trainingStateSelect');
                if (trainingStateSelect) {
                    trainingStateSelect.addEventListener('change', function () {
                        toggleClosureReasonField(trainingStateSelect.value);
                    });
                }

                function toggleClosureReasonField(val) {
                    var closedReasonInput = document.querySelector('#closedReasonInput');
                    if (closedReasonInput) {
                        if (val == -2) {
                            closedReasonInput.style.display = 'block';
                        } else {
                            closedReasonInput.style.display = 'none';
                        }
                    }
                }
            }

            var markdownContentLinks = document.querySelectorAll("#markdown-content p a, #markdown-improve p a");
            markdownContentLinks.forEach(function(link) {
                link.setAttribute('target', '_blank');
            });
        });
    </script>
@endsection
