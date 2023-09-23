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

                    <div class="mb-3">
                        <label class="form-label" for="position">Position</label>
                        <input
                            id="position"
                            class="form-control @error('position') is-invalid @enderror"
                            type="text"
                            name="position"
                            list="positions"
                            value="{{ old('position') }}"
                            required>

                        <datalist id="positions">
                            @foreach($positions as $position)
                                @browser('isFirefox')
                                    <option>{{ $position->callsign }}</option>
                                @else
                                    <option value="{{ $position->callsign }}">{{ $position->name }}</option>
                                @endbrowser
                            @endforeach
                        </datalist>

                        @error('position')
                            <span class="text-danger">{{ $errors->first('position') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="date">Date</label>
                        <input id="date" class="datepicker form-control @error('report_date') is-invalid @enderror" type="text" name="report_date" value="{{ old('report_date') }}" required>
                        @error('report_date')
                            <span class="text-danger">{{ $errors->first('report_date') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="contentBox">Report</label>
                        <textarea class="form-control @error('content') is-invalid @enderror" name="content" id="contentBox" rows="8" placeholder="Write the report here.">{{ old('content') }}</textarea>
                        @error('content')
                            <span class="text-danger">{{ $errors->first('content') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="contentimprove">Areas to improve</label>
                        <textarea class="form-control @error('contentimprove') is-invalid @enderror" name="contentimprove" id="contentimprove" rows="4" placeholder="In which areas do the student need to improve?">{{ old('contentimprove') }}</textarea>
                        @error('contentimprove')
                            <span class="text-danger">{{ $errors->first('contentimprove') }}</span>
                        @enderror
                    </div>

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

                    @if(session()->get('onetimekey') == null)
                        <div class="mb-3 form-check">
                            <input type="checkbox" value="1" class="form-check-input @error('draft') is-invalid @enderror" name="draft" id="draftCheck">
                            <label class="form-check-label" name="draft" for="draftCheck">Save as draft</label>
                            @error('draft')
                                <span class="text-danger">{{ $errors->first('draft') }}</span>
                            @enderror
                        </div>
                    @endif

                    <button type="submit" id="training-submit-btn" class="btn btn-success">Save report</button>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection

@section('js')

<!-- Flatpickr -->
@vite(['resources/js/flatpickr.js', 'resources/sass/flatpickr.scss'])
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var defaultDate = "{{ old('report_date') }}"
        document.querySelector('.datepicker').flatpickr({ disableMobile: true, minDate: "{!! date('Y-m-d', strtotime('-1 months')) !!}", maxDate: "{!! date('Y-m-d') !!}", dateFormat: "d/m/Y", defaultDate: defaultDate, locale: {firstDayOfWeek: 1 } });  
    });
</script>

<!-- Markdown Editor -->
@vite(['resources/js/easymde.js', 'resources/sass/easymde.scss'])
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var simplemde1 = new EasyMDE({ 
            element: document.getElementById("contentBox"), 
            status: false, 
            toolbar: ["bold", "italic", "heading-3", "|", "quote", "unordered-list", "ordered-list", "|", "link", "preview", "side-by-side", "fullscreen", "|", "guide"],
            insertTexts: {
                link: ["[","](link)"],
            }
        });
        var simplemde2 = new EasyMDE({ 
            element: document.getElementById("contentimprove"), 
            status: false, 
            toolbar: ["bold", "italic", "heading-3", "|", "quote", "unordered-list", "ordered-list", "|", "link", "preview", "side-by-side", "fullscreen", "|", "guide"],
            insertTexts: {
                link: ["[","](link)"],
            }
        });

        var submitClicked = false
        document.addEventListener("submit", function(event) {
            if (event.target.tagName === "FORM") {
                submitClicked = true;
            }
        });

        // Confirm closing window if there are unsaved changes
        window.addEventListener('beforeunload', function (e) {
            if(!submitClicked && (simplemde1.value() != '' || simplemde2.value() != '')){
                e.preventDefault();
                e.returnValue = '';
            }
        });
    })
</script>

@endsection
