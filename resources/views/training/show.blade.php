@extends('layouts.app')

@section('title', 'Training')
@section('content')

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
                                <th>Country</th>
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
                                    @if ($training->started_at == null && $training->finished_at == null)
                                        Training not started
                                    @elseif ($training->finished_at == null)
                                        {{ $training->started_at->toEuropeanDate() }} -
                                    @else
                                        {{ $training->started_at->toEuropeanDate() }} - {{ $training->finished_at->toEuropeanDate() }}
                                    @endif
                                </td>
                                <td>{{ $training->country->name }}</td>
                                <td>{{ $training->created_at->toEuropeanDate() }}</td>
                                <td>
                                    @if ($training->finished_at != null)
                                        {{ $training->finished_at->toEuropeanDate() }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if (sizeof($training->mentors) == 0)
                                        -
                                    @else
                                        @foreach($training->mentors as $mentor)
                                            @if($loop->last)
                                                {{ $mentor->name }}
                                            @else
                                                {{ $mentor->name . " + " }}
                                            @endif
                                        @endforeach
                                    @endif
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

    @can('update', $training)
    <div class="col-xl col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Options
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('training.update', ['training' => $training->id]) }}" method="POST">
                    @method('PATCH')
                    @csrf

                    <div class="form-group">
                        <label for="trainingStateSelect">Select training state</label>
                        <select class="form-control" name="status" id="trainingStateSelect">
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

                    <div class="form-group">
                        <label for="trainingStateSelect">Select training type</label>
                        <select class="form-control" name="type" id="trainingStateSelect">
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
                        <input class="form-check-input" type="checkbox" id="check1" name="paused_at" {{ $training->paused_at ? "checked" : "" }}>
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

                    @if (\Auth::user()->isModerator())
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

    <div class="col-xl col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Application
                </h6>
            </div>
            <div class="report-overflow-scroll">
                <div class="card-body">
                    <div class="card bg-light mb-3">
                        <div class="card-header text-primary">Language</div>
                        <div class="card-body">
                            @if($training->english_only_training)
                                <p class="card-text text-warning">
                                    The student wishes to receive training in English.
                                </p>
                            @else
                                <p class="card-text">
                                    The student is able to receive training in local and English language.
                                </p>
                            @endif

                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card bg-light mb-3">
                        <div class="card-header text-primary">Letter of motivation</div>
                        <div class="card-body">
                        <p class="card-text">
                            {{ $training->motivation }}
                        </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="col-xl col-md-12 mb-12">
        <div class="card shadow mb-4 ">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Reports
                </h6>
            </div>
            <div class="card-body">

                @can('viewReports', $training)
                    @foreach($examinations as $examination)
                            <div class="card bg-light mb-3">
                                <div class="card-header text-danger">Examination report {{ $examination->examination_date->toEuropeanDate() }}</div>
                                <div class="card-body">
                                    <p class="card-text">
                                        @if($examination->result == "PASSED")
                                            <span class='badge badge-success'>PASSED</span>
                                        @elseif($examination->result == "FAILED")
                                            <span class='badge badge-danger'>FAILED</span>
                                        @elseif($examination->result == "INCOMPLETE")
                                            <span class='badge badge-primary'>INCOMPLETE</span>
                                        @elseif($examination->result == "POSTPONED")
                                            <span class='badge badge-warning'>POSTPONED</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                    @endforeach

                    @if (sizeof($training->reports) == 0)
                            <div class="card-text text-primary">
                                No training reports yet.
                            </div>
                    @else
                        @foreach($training->reports as $report)
                                <div class="card bg-light mb-3">
                                    <div class="card-header text-primary"><a href="{{ route('training.report.edit', $report->id) }}">Training report {{ $report->created_at->toEuropeanDate() }}</a> by <a href="{{ route('user.show', $report->written_by_id) }}">{{ \App\User::find($report->written_by_id)->name }}</a>
                                        @if($report->draft)
                                            <span class='badge badge-danger'>Draft</span>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            {{ $report->content }}
                                        </p>
                                    </div>
                                    @if ($report->mentor_notes != null)
                                    <div class="card-header text-primary">Mentor notes</div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            {{ $report->mentor_notes }}
                                        </p>
                                    </div>
                                    @endif

                                    @if($report->attachments->count() > 0)
                                        <div class="card-body">
                                            @foreach($report->attachments as $attachment)
                                                <div>
                                                    <a href="{{ route('training.report.attachment.show', ['attachment' => $attachment]) }}" target="_blank">
                                                        <i class="fa fa-file"></i>&nbsp;{{ $attachment->file->name }}
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                </div>
                        @endforeach
                    @endif
                @else
                    <div class="card-text text-primary">
                        You don't have access to see this training reports.
                    </div>
                @endcan

                @if($training->status == 1)
                    @can('createReport', $training)
                        <a href="{{ route('training.report.create', ['training' => $training->id]) }}" class="btn mt-4 mr-2 btn-primary">Create report</a>
                    @else
                        <a href="#" class="btn mt-4 mr-2 btn-primary disabled">Create report</a>
                    @endcan
                @endif

                @if($training->status == 2)
                    @can('createExamination', $training)
                        <a href="{{ route('training.examination.create', ['training' => $training->id]) }}" class="btn mt-4 mr-2 btn-danger">Create examination report</a>
                    @else
                        <a href="#" class="btn mt-4 mr-2 btn-success disabled">Create examination report</a>
                    @endcan
                @endif
            </div>
        </div>
    </div>
@endsection
