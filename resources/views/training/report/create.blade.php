@extends('layouts.app')

@section('title', 'New Training Report')
@section('content')

<div class="row">
    <div class="col-xl-5 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    New Training Report for {{ $training->user->first_name }}'s training for
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
                <form action="{{ route('training.report.store', ['training' => $training->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    @include('training.report.parts.form-fields', [
                        'position' => old('position'),
                        'reportDate' => old('report_date'),
                        'content' => old('content'),
                        'contentimprove' => old('contentimprove'),
                    ])

                    <div class="mb-3">
                        <label class="form-label" for="attachments">Attachments</label>
                        <div>
                            <input type="file" name="files[]" id="add-file" class="@error('file') is-invalid @enderror" accept=".pdf, .xls, .xlsx, .doc, .docx, .txt, .png, .jpg, .jpeg" multiple>
                        </div>
                        @error('files')
                            <span class="text-danger">{{ $errors->first('files') }}</span>
                        @enderror
                    </div>

                    <hr>

                    @can('create-draft', [\App\Models\TrainingReport::class, $training])
                        <div class="mb-3 form-check">
                            <input type="checkbox" value="1" class="form-check-input @error('draft') is-invalid @enderror" name="draft" id="draftCheck">
                            <label class="form-check-label" name="draft" for="draftCheck">Save as draft</label>
                            @error('draft')
                                <span class="text-danger">{{ $errors->first('draft') }}</span>
                            @enderror
                        </div>
                    @else
                        <div class="mb-3">
                            <span class="text-secondary">You cannot create a draft of this training report.</span>
                        </div>
                    @endcan

                    <button type="submit" id="training-submit-btn" class="btn btn-success">Save report</button>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection

@section('js')

@include('training.report.parts.scripts', [
    'datepickerDefault' => old('report_date'),
    'datepickerMaxDate' => date('Y-m-d'),
])

@endsection
