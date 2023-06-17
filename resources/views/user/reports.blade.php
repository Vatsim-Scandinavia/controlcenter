@extends('layouts.app')

@section('title', 'Reports Archive')
@section('content')

<div class="row">

    <div class="col-xl-5 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">{{ $user->first_name }}'s Reports</h6> 
            </div>
            <div class="card-body p-0">
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
                                                    <i class="fas fa-fw fa-chevron-right me-2"></i>
                                                    {{ $reportModel->report_date->toEuropeanDate() }}
                                                    | {{ isset(\App\Models\User::find($reportModel->training->user->id)->first_name) ? \App\Models\User::find($reportModel->training->user->id)->first_name : "Unknown"  }}'s
                                                    @foreach($reportModel->training->ratings as $rating)
                                                        @if ($loop->last)
                                                            {{ $rating->name }}
                                                        @else
                                                            {{ $rating->name . " + " }}
                                                        @endif
                                                    @endforeach
                                                    Training
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
                                                    @can('view', [\App\Models\Training::class, $reportModel->training])
                                                        <a class="float-end" href="{{ route('training.show', $reportModel->training->id) }}"><i class="fa fa-eye"></i> View training</a>
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
                                                <i class="fas fa-fw fa-chevron-right me-2"></i>
                                                {{ $reportModel->examination_date->toEuropeanDate() }}
                                                | {{ isset(\App\Models\User::find($reportModel->training->user->id)->first_name) ? \App\Models\User::find($reportModel->training->user->id)->first_name : "Unknown"  }}'s
                                                @foreach($reportModel->training->ratings as $rating)
                                                    @if ($loop->last)
                                                        {{ $rating->name }}
                                                    @else
                                                        {{ $rating->name . " + " }}
                                                    @endif
                                                @endforeach
                                                Exam
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
                                                @can('view', [\App\Models\Training::class, $reportModel->training])
                                                    <a class="float-end" href="{{ route('training.show', $reportModel->training->id) }}"><i class="fa fa-eye"></i> View training</a>
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
            </div>
        </div>
    </div>

</div>
@endsection