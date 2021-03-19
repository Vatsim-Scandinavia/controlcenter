@extends('layouts.app')

@section('title', 'Training')
@section('title-extension')
    @if(\Auth::user()->can('create', [\App\Models\OneTimeLink::class, $training, \App\Models\OneTimeLink::TRAINING_REPORT_TYPE]) || \Auth::user()->can('create', [\App\Models\OneTimeLink::class, $training, \App\Models\OneTimeLink::TRAINING_EXAMINATION_TYPE]))
        <div class="dropdown" style="display: inline;">
            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Generate
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                @can('create', [\App\Models\OneTimeLink::class, $training, \App\Models\OneTimeLink::TRAINING_REPORT_TYPE])
                    <button class="dropdown-item" id="getOneTimeLinkReport">Report one-time link</button>
                @endif
                @can('create', [\App\Models\OneTimeLink::class, $training, \App\Models\OneTimeLink::TRAINING_EXAMINATION_TYPE])
                    <button class="dropdown-item" id="getOneTimeLinkExam">Examination one-time link</button>
                @endif
            </div>
        </div>
    @endif

    @can('close', $training)
        <a href="{{ route('training.close', $training->id) }}" onclick="return confirm('Are you sure you want to close your training?')" class="btn btn-danger btn-sm">Close my training</a>
    @endcan

@endsection
@section('content')

@if($training->status < -1 && $training->status != -3)
    <div class="alert alert-warning" role="alert">
        <b>Training is closed with reason: </b>
        @if(isset($training->closed_reason))
            {{ $training->closed_reason }}
        @else
            No reason given
        @endif
    </div>
@endif

@if($training->status == -3)
    <div class="alert alert-warning" role="alert">
        <b>Training closed by student</b>
    </div>
@endif

<div class="row">

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    {{ $training->user->firstName }}'s training for
                    @foreach($training->ratings as $rating)
                        @if ($loop->last)
                            {{ $rating->name }}
                        @else
                            {{ $rating->name . " + " }}
                        @endif
                    @endforeach
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>State</th>
                                <th>Vatsim ID</th>
                                <th>Name</th>
                                <th>Level</th>
                                <th>Type</th>
                                <th>Period</th>
                                <th>Area</th>
                                <th>Applied</th>
                                <th>Closed</th>
                                <th>Mentor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <i class="{{ $statuses[$training->status]["icon"] }} text-{{ $statuses[$training->status]["color"] }}"></i>&ensp;<a href="/training/{{ $training->id }}">{{ $statuses[$training->status]["text"] }}</a>{{ isset($training->paused_at) ? ' (PAUSED)' : '' }}
                                </td>
                                <td><a href="/user/{{ $training->user->id }}">{{ $training->user->id }}</a></td>
                                <td><a href="/user/{{ $training->user->id }}">{{ $training->user->name }}</a></td>
                                <td>
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
                                </td>
                                <td>
                                    <i class="{{ $types[$training->type]["icon"] }}"></i>&ensp;{{ $types[$training->type]["text"] }}
                                </td>
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
                                <td>{{ $training->area->name }}</td>
                                <td>{{ $training->created_at->toEuropeanDate() }}</td>
                                <td>
                                    @if ($training->closed_at != null)
                                        {{ $training->closed_at->toEuropeanDate() }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    {{ $training->getInlineMentors() }}
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="row">

    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4 ">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">

                @if($training->status >= 1 && $training->status <= 3)
                    <h6 class="m-0 font-weight-bold text-white">
                @else
                    <h6 class="m-0 mt-1 mb-2 font-weight-bold text-white">
                @endif
                    Training Reports
                </h6>

                @if($training->status >= 1 && $training->status <= 3)
                    <div class="dropdown" style="display: inline;">
                        <button class="btn btn-success btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Create
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @can('create', [\App\Models\TrainingReport::class, $training])
                                @if($training->status >= 1)
                                    <a class="dropdown-item" href="{{ route('training.report.create', ['training' => $training->id]) }}">Training Report</a>
                                @endif
                            @else
                                <a class="dropdown-item disabled" href="#"><i class="fas fa-lock"></i>&nbsp;Training Report</a>
                            @endcan

                            @can('create', [\App\Models\TrainingExamination::class, $training])
                                @if($training->status == 3)
                                    <a class="dropdown-item" href="{{ route('training.examination.create', ['training' => $training->id]) }}">Exam Report</a>
                                @endif
                            @else
                                <a class="dropdown-item disabled" href="#"><i class="fas fa-lock"></i>&nbsp;Exam Report</a>
                            @endcan
                        </div>
                    </div>
                @endif
            </div>
            <div class="card-body p-0">

                @can('viewAny', [\App\Models\TrainingReport::class, $training])
                    <div class="accordion" id="reportAccordion">
                        @if ($reports->count() == 0 && $examinations->count() == 0)
                            <div class="card-text text-primary p-3">
                                No training reports yet.
                            </div>
                        @else

                            @foreach($examinations as $examination)

                                @php
                                    $uuid = "instance-".Ramsey\Uuid\Uuid::uuid4();
                                @endphp

                                <div class="card">
                                    <div class="card-header p-0">
                                        <h5 class="mb-0 bg-lightorange">
                                            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#{{ $uuid }}" aria-expanded="true">
                                                <i class="fas fa-fw fa-chevron-right mr-2"></i>{{ $examination->examination_date->toEuropeanDate() }}
                                            </button>
                                        </h5>
                                    </div>

                                    <div id="{{ $uuid }}" class="collapse" data-parent="#reportAccordion">
                                        <div class="card-body">

                                            <small class="text-muted">
                                                @if(isset($examination->position))
                                                    <i class="fas fa-map-marker-alt"></i> {{ \App\Models\Position::find($examination->position_id)->callsign }}&emsp;
                                                @endif
                                                <i class="fas fa-user-edit"></i> {{ isset(\App\Models\User::find($examination->examiner_id)->name) ? \App\Models\User::find($examination->examiner_id)->name : "Unknown" }}

                                            </small>

                                            <div class="mt-2">
                                                @if($examination->result == "PASSED")
                                                    <span class='badge badge-success'>PASSED</span>
                                                @elseif($examination->result == "FAILED")
                                                    <span class='badge badge-danger'>FAILED</span>
                                                @elseif($examination->result == "INCOMPLETE")
                                                    <span class='badge badge-primary'>INCOMPLETE</span>
                                                @elseif($examination->result == "POSTPONED")
                                                    <span class='badge badge-warning'>POSTPONED</span>
                                                @endif
                                            </div>

                                            @if($examination->attachments->count() > 0)
                                                @foreach($examination->attachments as $attachment)
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


                            @endforeach

                            @foreach($reports as $report)
                                @if(!$report->draft || $report->draft && \Auth::user()->isMentorOrAbove())

                                    @php
                                        $uuid = "instance-".Ramsey\Uuid\Uuid::uuid4();
                                    @endphp

                                    <div class="card">
                                        <div class="card-header p-0">
                                            <h5 class="mb-0">
                                                <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#{{ $uuid }}" aria-expanded="true">
                                                    <i class="fas fa-fw fa-chevron-right mr-2"></i>{{ $report->report_date->toEuropeanDate() }}
                                                    @if($report->draft)
                                                        <span class='badge badge-danger'>Draft</span>
                                                    @endif
                                                </button>
                                            </h5>
                                        </div>

                                        <div id="{{ $uuid }}" class="collapse" data-parent="#reportAccordion">
                                            <div class="card-body">

                                                <small class="text-muted">
                                                    @if(isset($report->position))
                                                        <i class="fas fa-map-marker-alt"></i> {{ $report->position }}&emsp;
                                                    @endif
                                                    <i class="fas fa-user-edit"></i> {{ isset(\App\Models\User::find($report->written_by_id)->name) ? \App\Models\User::find($report->written_by_id)->name : "Unknown"  }}
                                                    @can('update', $report)
                                                        <a class="float-right" href="{{ route('training.report.edit', $report->id) }}"><i class="fa fa-pen-square"></i> Edit</a>
                                                    @endcan
                                                </small>

                                                <div class="mt-2">
                                                    @markdown($report->content)
                                                </div>

                                                @if(isset($report->contentimprove) && !empty($report->contentimprove))
                                                    <hr>
                                                    <p class="font-weight-bold text-primary">
                                                        <i class="fas fa-clipboard-list-check"></i>&nbsp;Areas to improve
                                                    </p>
                                                    @markdown($report->contentimprove)
                                                @endif

                                                @if($report->attachments->count() > 0)
                                                    <hr>
                                                    @foreach($report->attachments as $attachment)
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
    </div>

    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 mt-1 mb-2 font-weight-bold text-white">
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
                <p class="font-weight-bold text-primary">
                    <i class="fas fa-envelope-open-text"></i>&nbsp;Letter of motivation
                </p>

                @if(empty($training->motivation))
                    <p><i>Not provided</i></p>
                @else
                    <p>{{ $training->motivation }}</p>
                @endif
            </div>
        </div>
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Training Interest Confirmations
                </h6>
            </div>
            <div class="card-body {{ $trainingInterests->count() == 0 ? '' : 'p-0' }}">

                @if($trainingInterests->count() == 0)
                    <p class="mb-0">No confirmation history</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                            <thead class="thead-light">
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
    </div>


    @can('update', $training)
    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 m-0 mt-1 mb-2 font-weight-bold text-white">
                    Options
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('training.update', ['training' => $training->id]) }}" method="POST">
                    @method('PATCH')
                    @csrf

                    <div class="form-group">

                        @if($activeTrainingInterest)
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-exclamation-triangle"></i>&nbsp;This training has an active interest request pending.
                            </div>
                        @endif

                        <label for="trainingStateSelect">Select training state</label>
                        <select class="form-control" name="status" id="trainingStateSelect" @if(!Auth::user()->isModeratorOrAbove()) disabled @endif>
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

                    <div class="form-group" id="closedReasonInput" style="display: none">
                        <label for="trainingCloseReason">Closed reason</label>
                        <input type="text" id="trainingCloseReason" class="form-control" name="closed_reason" placeholder="{{ $training->closed_reason }}" maxlength="50">
                    </div>

                    <div class="form-group">
                        <label for="trainingStateSelect">Select training type</label>
                        <select class="form-control" name="type" id="trainingStateSelect" @if(!Auth::user()->isModeratorOrAbove()) disabled @endif>
                            @foreach($types as $id => $data)
                                @if($id == $training->type)
                                    <option value="{{ $id }}" selected>{{ $data["text"] }}</option>
                                @else
                                    <option value="{{ $id }}">{{ $data["text"] }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="check1" name="paused_at" {{ $training->paused_at ? "checked" : "" }} @if(!Auth::user()->isModeratorOrAbove()) disabled @endif>
                        <label class="form-check-label" for="check1">
                            Paused
                            @if(isset($training->paused_at))
                                <span class='badge badge-danger'>{{ \Carbon\Carbon::create($training->paused_at)->diffForHumans(['parts' => 2]) }}</span>
                            @endif
                        </label>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label for="internalTrainingComments">Internal training comments</label>
                        <textarea class="form-control" name="notes" id="internalTrainingComments" rows="8" placeholder="Write internal training notes here">{{ $training->notes }}</textarea>
                    </div>

                    @if (\Auth::user()->isModeratorOrAbove())
                    <div class="form-group">
                        <label for="assignMentors">Assigned mentors: <span class="badge badge-dark">Ctrl/Cmd+Click</span> to select multiple</label>
                        <select multiple class="form-control" name="mentors[]" id="assignMentors">
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
    </div>
    @endcan

@endsection

@section('js')

    <!-- One Time Links -->
    <script type="text/javascript">

        // Generate a one time report link
        $('#getOneTimeLinkReport').click(async function (event) {
            event.preventDefault();
            $(this).prop('disabled', true);
            let route = await getOneTimeLink('{!! \App\Models\OneTimeLink::TRAINING_REPORT_TYPE !!}');
            $(this).prop('disabled', false);

            // Anything below this point can be changed
            alert("Link generated, click OK and copy the link displayed in the next prompt. Valid for 7 days.");
            alert(route);
        });

        // Generate a one time exam report link
        $('#getOneTimeLinkExam').click(async function (event) {
            event.preventDefault();
            $(this).prop('disabled', true);
            let route = await getOneTimeLink('{!! \App\Models\OneTimeLink::TRAINING_EXAMINATION_TYPE !!}');
            $(this).prop('disabled', false);

            // Anything below this point can be changed
            alert("Link generated, click OK and copy the link displayed in the next prompt. Valid for 7 days.");
            alert(route);
        });

        async function getOneTimeLink(type) {
            return '{!! env('APP_URL') !!}' + '/training/onetime/' + await getOneTimeLinkKey(type);
        }

        async function getOneTimeLinkKey(type) {
            let key, result;
            result = await $.ajax('{!! route('training.onetimelink.store', ['training' => $training]) !!}', {
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{!! csrf_token() !!}"
                },
                data: {
                    'type': type
                },
                success: function (response) {
                    return response;
                },
                error: function (response) {
                    console.error(response);
                    alert("An error occured while trying to generate the one-time link.");
                }
            });

            try {
                key = JSON.parse(result).key
            } catch (error) {
                console.error(error);
            }

            return key;
        }

    </script>

    <!-- Training report accordian -->
    <script>
        $(document).ready(function(){
            // Add minus icon for collapse element which is open by default
            $(".collapse.show").each(function(){
                $(this).prev(".card-header").find(".fas").addClass("fa-chevron-down").removeClass("fa-chevron-right");
            });

            // Toggle plus minus icon on show hide of collapse element
            $(".collapse").on('show.bs.collapse', function(){
                $(this).prev(".card-header").find(".fas").removeClass("fa-chevron-right").addClass("fa-chevron-down");
            }).on('hide.bs.collapse', function(){
                $(this).prev(".card-header").find(".fas").removeClass("fa-chevron-down").addClass("fa-chevron-right");
            });

            // Closure reason input
            toggleClosureReasonField($('#trainingStateSelect').val())

            $('#trainingStateSelect').on('change', function () {
                toggleClosureReasonField($('#trainingStateSelect').val())
            });

            function toggleClosureReasonField(val){
                if(val == -2){
                    $('#closedReasonInput').slideDown(100)
                } else {
                    $('#closedReasonInput').hide()
                }
            }

        });
    </script>
@endsection
