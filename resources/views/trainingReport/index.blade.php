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
                <h6 class="m-0 font-weight-bold text-white">
                    Reports for {{ $training->user->firstName }}'s training {{ $report->created_at->toFormattedDateString() }} {{ $report->draft ? "(DRAFT)" : "" }} for
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
                        <p><span class="font-weight-bold">Position: </span>{{ $report->position }}</p>
                        <p><span class="font-weight-bold">Mentor: </span>{{ $report->user->name }}</p>
                        <p><span class="font-weight-bold">Content: </span></p>
                        <p>{{ $report->content }}</p>
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
                <div class="row mt-8">
                    @if (\Illuminate\Support\Facades\Gate::inspect('update', $report)->allowed())
                        <a href="{{ route('training.report.edit', ['report' => $report->id]) }}" class="btn btn-primary ml-2 mx-1">Update report</a>
                        <!--<a href="{{ route('training.report.update', ['report' => $report->id]) }}" class="btn btn-outline-secondary mx-1" id="draft-btn" data-report="{{ $report->id }}" data-draft="{{ $report->draft }}">
                            {{ $report->draft ? "Make report public" : "Turn into draft" }}
                        </a>-->
                    @endif

                    @if (\Illuminate\Support\Facades\Gate::inspect('delete', $report)->allowed())
                    <!--<a href="#" class="btn btn-danger mx-1" id="delete-btn" data-report="{{ $report->id }}">Delete report</a>-->
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach

@endsection

@section('js')
<!--<script>

    $(document).ready(function () {

        $("#draft-btn").click(function (event) {

            event.preventDefault();

            let draft = Number( ! $(this).data("draft"));

            let data = {
                '_method' : 'PATCH',
                'draft' : draft
            };

            $.ajaxSetup({
                headers:{
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                }
            });

            $.ajax($(this).href, {
                type: "POST",
                data: data,
                success: function (data) {
                    window.location.reload(true);
                }
            });

        });


        $("#draft-btn").click(function (event) {

            event.preventDefault();

            let draft = Number( ! $(this).data("draft"));

            let data = {
                '_method' : 'PATCH',
                'draft' : draft
            };

            $.ajaxSetup({
                headers:{
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                }
            });

            $.ajax($(this).href, {
                type: "POST",
                data: data,
                success: function (data) {
                    window.location.reload(true);
                }
            });

        });

    });

</script>-->
@endsection
