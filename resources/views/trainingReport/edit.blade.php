@extends('layouts.app')

@section('title', 'Training Report ' . $report->created_at->toFormattedDateString())

@section('content')
<h1 class="h3 mb-4 text-gray-800">
    Training Report {{ $report->created_at->toFormattedDateString() }} for {{ $report->training->user->firstName }}'s training for
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
                        <div>
                            <p><span class="font-weight-bold">Attachments:</span></p>
                            <div>
                                @if(count($report->attachments) == 0)
                                    <span class="font-italic">No attachments to this report.</span>
                                @endif
                                @foreach($report->attachments as $attachment)
                                    <div data-id="{{ $attachment->id }}">
                                        <a href="{{ route('training.report.attachment.show', ['attachment' => $attachment]) }}" target="_blank">
                                            {{ $attachment->file->name }}
                                        </a>
                                        <i data-attachment="{{ $attachment->id }}" onclick="deleteAttachment(this)" class="fa fa-lg fa-times"></i>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <form method="post" id="file-form" action="{{ route('training.report.attachment.store', ['report' => $report->id]) }}" enctype="multipart/form-data">
                                @csrf
                                <label for="hidden" class="mt-3">Hidden?</label>
                                <input type="checkbox" name="hidden" id="hidden"><br>
                                <input type="file" name="file" id="add-file" onchange="uploadFile(this)" class="btn btn-primary">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection

@section('js')
<script type="text/javascript">

    function uploadFile(input) {
        if (input.value != null) {
            $("#file-form").submit();
        }
    }

    function deleteAttachment(input) {

        input = $(input);
        let id = input.data('attachment');

        $.ajax('/training/report/attachment/' + id, {
            'method' : "post",
            'data' : {
                '_token': "{!! csrf_token() !!}",
                '_method': 'delete'
            },
            success: function (data, id) {
                if (data.message === 'Attachment successfully deleted') {
                    $("div").find('[data-id="' + id + '"').remove();
                }
            },
            error: function (data) {
                console.log("An error occurred");
            }
        });

    }

</script>
@endsection
