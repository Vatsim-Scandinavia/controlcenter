@extends('layouts.app')

@section('title', 'Training Report ' . $report->created_at->toFormattedDateString())

@section('content')
<h1 class="h3 mb-4 text-gray-800">
    Training Report {{ $report->created_at->toFormattedDateString() }} for {{ $report->training->user->handover->first_name }}'s training for
    @foreach($report->training->ratings as $rating)
        @if ($loop->last)
            {{ $rating->name }}
        @else
            {{ $rating->name . " + " }}
        @endif
    @endforeach
</h1>

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Training report: {{ $report->created_at->toFormattedDateString() }} {{ $report->draft ? "(DRAFT)" : "" }}
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-8 col-md-12 mb-12">
                        <form action="{{ route('training.report.update', ['report' => $report->id]) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="row flex-row">
                                <label for="position"><span class="font-weight-bold">Position: </span></label>
                                <input name="position" id="position" type="text" placeholder="Position" value="{{ $report->position }}" class="ml-2">
                            </div>

                            <div class="row">
                                <label for="content"><span class="font-weight-bold col-12 px-2 mx-2">Content: </span></label>
                                <textarea rows="8" placeholder="Content..." name="content" class="mt-2 col-10 px-2 mx-2">{{ $report->content }}</textarea>
                            </div>
                            <div class="row">
                                <label for="draft"><span class="font-weight-bold">Draft?</span></label>
                                <input type="checkbox" name="draft" id="draft" {{ $report->draft ? "checked" : "" }}>
                            </div>
                            <div class="row mt-8">
                                @if (\Illuminate\Support\Facades\Gate::inspect('update', $report)->allowed())
                                    <button type="submit" class="btn btn-primary ml-2 mx-1">Update report</button>
                                @endif

                                @if (\Illuminate\Support\Facades\Gate::inspect('delete', $report)->allowed())
                                    <a href="#" class="btn btn-danger mx-1" id="delete-btn" data-report="{{ $report->id }}">Delete report</a>
                                @endif
                            </div>
                        </form>
                    </div>
                    <div class="col-xl-4 col-md-12 border-left-primary">
                        <p><span class="font-weight-bold">Attachments:</span></p>
                        <p>
                            @if(count($report->attachments) == 0)
                                <span class="font-italic">No attachments to this report.</span>
                            @endif
                            @foreach($report->attachments as $attachment)
                                <a href="#">{{ $attachment->url }}</a>
                            @endforeach
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection
