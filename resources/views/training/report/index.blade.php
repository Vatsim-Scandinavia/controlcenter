@extends('layouts.app')

@section('title', 'Training Reports')
@section('content')

    @empty($reports)
        <div class="row">
            <div class="col-xl-12 col-md-12 mb-12">
                There's no reports to show.
            </div>
        </div>
    @endempty

    @foreach($reports as $report)
    <div class="row">
        <div class="col-xl-12 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">
                        Reports for {{ $training->user->first_name }}'s training {{ $report->report_date->toEuropeanDate() }} {{ $report->draft ? "(DRAFT)" : "" }} for
                        @foreach($training->ratings as $rating)
                            @if ($loop->last)
                                {{ $rating->name }}
                            @else
                                {{ $rating->name . " + " }}
                            @endif
                        @endforeach
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-8 col-md-12 mb-12">
                            <p><span class="fw-bold">Position: </span>{{ $report->position }}</p>
                            <p><span class="fw-bold">Mentor: </span>{{ $report->user->name }}</p>
                            <p><span class="fw-bold">Content: </span></p>
                            <p>{{ $report->content }}</p>
                        </div>
                        <div class="col-xl-4 col-md-12 border-left-primary">
                            <p><span class="fw-bold">Attachments:</span></p>
                            <p>
                                @if(count($report->attachments) == 0)
                                    <span class="fst-italic">No attachments to this report.</span>
                                @endif
                                @foreach($report->attachments as $attachment)
                                    <a href="#">{{ $attachment->url }}</a>
                                @endforeach
                            </p>
                        </div>
                    </div>
                    <div class="row mt-8">
                        @if (\Illuminate\Support\Facades\Gate::inspect('update', $report)->allowed())
                            <a href="{{ route('training.report.edit', ['report' => $report->id]) }}" class="btn btn-primary ms-2 mx-1">Update report</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

@endsection