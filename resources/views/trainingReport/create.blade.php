@extends('layouts.app')

@section('title', 'New Training Report')
@section('content')

<div class="row">
    <div class="col-xl-5 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    New Training Report for {{ $training->user->firstName }}'s training for
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
                <form action="{{ route('training.report.store', ['training' => $training->id]) }}" method="POST">
                    @csrf
                    
                    <div class="form-group">
                        <label for="position">Position</label>
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
                                <option value="{{ $position->callsign }}">{{ $position->name }}</option>
                            @endforeach
                        </datalist>

                        @error('position')
                            <span class="text-danger">{{ $errors->first('position') }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="contentBox">Report</label>
                        <textarea class="form-control @error('content') is-invalid @enderror" name="content" id="contentBox" rows="8" placeholder="Write the report here."></textarea>
                        @error('content')
                            <span class="text-danger">{{ $errors->first('content') }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="attachments">Attachments</label>
                        <div class="custom-file" id="attachments">
                            <input type="file" class="custom-file-input @error('file') is-invalid @enderror" id="customFile" name="file">
                            <label class="custom-file-label" for="customFile">Choose file</label>
                        </div>
                        @error('file')
                            <span class="text-danger">{{ $errors->first('file') }}</span>
                        @enderror
                    </div>

                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input @error('draft') is-invalid @enderror" id="draftCheck">
                        <label class="form-check-label" name="draft" for="draftCheck">Draft</label>
                        @error('draft')
                            <span class="text-danger">{{ $errors->first('draft') }}</span>
                        @enderror
                    </div>

                    <button type="submit" id="training-submit-btn" class="btn btn-success">Save report</button>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection
