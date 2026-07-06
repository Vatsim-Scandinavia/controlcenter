@extends('layouts.app')

@section('title', 'Training Report')
@section('content')


<div class="row">
    <div class="col-xl-5 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    {{ $report->training->user->first_name }}'s training {{ $report->report_date->toEuropeanDate() }}
                    @if($report->draft)
                        <span class='badge bg-danger'>Draft</span>
                    @endif
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('training.report.update', ['report' => $report->id]) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    @include('training.report.parts.form-fields', [
                        'position' => empty(old('position')) ? $report->position : old('position'),
                        'reportDate' => empty(old('report_date')) ? $report->report_date->toEuropeanDate() : old('report_date'),
                        'content' => empty(old('content')) ? $report->content : old('content'),
                        'contentimprove' => empty(old('contentimprove')) ? $report->contentimprove : old('contentimprove'),
                    ])

                    <div class="mb-3 form-check">
                        <input type="checkbox" value="1" class="form-check-input @error('draft') is-invalid @enderror" name="draft" id="draftCheck" {{ $report->draft ? "checked" : "" }}>
                        <label class="form-check-label" name="draft" for="draftCheck">Save as draft</label>
                        @error('draft')
                            <span class="text-danger">{{ $errors->first('draft') }}</span>
                        @enderror
                    </div>

                    @if (\Illuminate\Support\Facades\Gate::inspect('update', $report)->allowed())
                        <button type="submit" class="btn btn-success">Update report</button>
                    @endif

                    @if (\Illuminate\Support\Facades\Gate::inspect('delete', $report)->allowed())
                        <a href="{{ route('training.report.delete', $report->id) }}" class="btn btn-danger" id="delete-btn" data-report="{{ $report->id }}">Delete report</a>
                    @endif
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-5 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Manage attachments
                </h6>
            </div>
            <div class="card-body">

                <div>
                    @if(count($report->attachments) == 0)
                        <i>This report has no attachments.</i>
                    @endif

                    @foreach($report->attachments as $attachment)
                        <div data-id="{{ $attachment->id }}">
                            <a href="{{ route('training.object.attachment.show', ['attachment' => $attachment]) }}" target="_blank">
                                {{ $attachment->file->name }}
                            </a>
                            <i data-attachment="{{ $attachment->id }}" class="fa fa-lg fa-trash text-danger deleteAttachmentBtn" style="cursor: pointer;"></i>
                        </div>
                    @endforeach
                </div>

                <hr>

                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i>
                    Please save your report before uploading attachments to avoid losing your changes.
                </div>

                <form method="post" id="file-form" action="{{ route('training.object.attachment.store', ['trainingObjectType' => 'report', 'trainingObject' => $report->id]) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="attachments">Attachments</label>
                        <div>
                            <input type="file" name="file" id="add-file" class="@error('file') is-invalid @enderror" accept=".pdf, .xls, .xlsx, .doc, .docx, .txt, .png, .jpg, .jpeg" onchange="uploadFile(this)" multiple>
                        </div>
                        @error('file')
                            <span class="text-danger">{{ $errors->first('file') }}</span>
                        @enderror
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>


@endsection

@section('js')

<!-- Attachment management -->
<script>

    function uploadFile(input) {
        if (input.value != null) {
            document.getElementById('file-form').submit()
        }
    }

    var deleteAttachmentBtn = document.querySelectorAll('.deleteAttachmentBtn');
    deleteAttachmentBtn.forEach(function (btn) {
        btn.addEventListener('click', function () {

            let id = btn.dataset.attachment;

            fetch('/training/attachment/'+id, {
                method: "POST",
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': "{!! csrf_token() !!}"
                },
                body: '_method=DELETE'
            })
            .then(response => {
                if (response.ok) {
                    document.querySelector('div[data-id="' + id + '"]').remove();
                }
            })
            .catch(error => {
                console.error("An error occurred while attempting to delete attachment:", error);
            });
        });
    });
</script>

@include('training.report.parts.scripts', [
    'datepickerDefault' => empty(old('report_date')) ? $report->report_date->toEuropeanDate() : old('report_date'),
])

@endsection
