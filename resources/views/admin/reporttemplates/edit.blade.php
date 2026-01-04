@extends('layouts.app')

@section('title', 'Edit Report Template')

@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Edit Training Report Template</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.reporttemplates.update', $template->id) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label" for="name">Template Name</label>
                        <input
                            id="name"
                            class="form-control @error('name') is-invalid @enderror"
                            type="text"
                            name="name"
                            value="{{ old('name', $template->name) }}"
                            required>
                        @error('name')
                            <span class="text-danger">{{ $errors->first('name') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="contentBox">Report</label>
                        <textarea class="form-control @error('content') is-invalid @enderror" name="content" id="contentBox" rows="10" placeholder="Write the report template content here in markdown format.">{{ old('content', $template->content) }}</textarea>
                        @error('content')
                            <span class="text-danger">{{ $errors->first('content') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="contentimproveBox">Areas to improve</label>
                        <textarea class="form-control @error('contentimprove') is-invalid @enderror" name="contentimprove" id="contentimproveBox" rows="6" placeholder="In which areas does the student need to improve?">{{ old('contentimprove', $template->contentimprove) }}</textarea>
                        @error('contentimprove')
                            <span class="text-danger">{{ $errors->first('contentimprove') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="areas">Assign to Areas</label>
                        <select class="form-select @error('areas') is-invalid @enderror" name="areas[]" id="areas" multiple size="5">
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}" {{ $template->areas->contains($area->id) || in_array($area->id, old('areas', [])) ? 'selected' : '' }}>{{ $area->name }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Hold Ctrl (or Cmd on Mac) to select multiple areas. Leave empty to assign to all areas.</small>
                        @error('areas')
                            <span class="text-danger">{{ $errors->first('areas') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" value="1" class="form-check-input @error('draft') is-invalid @enderror" name="draft" id="draftCheck" {{ old('draft', $template->draft) ? 'checked' : '' }}>
                        <label class="form-check-label" for="draftCheck">Save as draft (not available for selection)</label>
                        @error('draft')
                            <span class="text-danger">{{ $errors->first('draft') }}</span>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.reporttemplates') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success">Update Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')

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
            element: document.getElementById("contentimproveBox"), 
            status: false, 
            toolbar: ["bold", "italic", "heading-3", "|", "quote", "unordered-list", "ordered-list", "|", "link", "preview", "side-by-side", "fullscreen", "|", "guide"],
            insertTexts: {
                link: ["[","](link)"],
            }
        });
    });
</script>

@endsection

