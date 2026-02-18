@extends('layouts.app')

@section('title', 'Edit Feedback')
@section('content')

<div class="row">
    <div class="col-xl-6 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Edit Feedback
                </h6> 
            </div>
            <div class="card-body">
                <form action="{{ route('feedback.update', $feedback->id) }}" method="POST">
                    @method('PATCH')
                    @csrf

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Submitter</label>
                            <input class="form-control" type="text" value="{{ $feedback->submitter->name }} ({{ $feedback->submitter_user_id }})" disabled>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Submitted</label>
                            <input class="form-control" type="text" value="{{ $feedback->created_at->toEuropeanDateTime() }}" disabled>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label class="form-label">Feedback Text</label>
                            <textarea class="form-control" rows="5" disabled>{{ $feedback->feedback }}</textarea>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="controllers">Controller <small class="form-text"> (Optional)</small></label>
                            <input 
                                id="controllers"
                                class="form-control @error('controller') is-invalid @enderror"
                                type="text"
                                name="controller"
                                list="controllersList"
                                value="{{ old('controller', $feedback->referenceUser ? $feedback->referenceUser->id : '') }}"
                                >

                            <datalist id="controllersList">
                                @foreach($controllers as $controller)
                                    @browser('isFirefox')
                                        <option>{{ $controller->id }}</option>
                                    @else
                                        <option value="{{ $controller->id }}">{{ $controller->name }}</option>
                                    @endbrowser
                                @endforeach
                            </datalist>
                            
                            @error('controller')
                                <span class="text-danger">{{ $errors->first('controller') }}</span>
                            @enderror
                            @if($feedback->referenceUser)
                                <small class="form-text text-muted">Current: {{ $feedback->referenceUser->name }} ({{ $feedback->referenceUser->id }})</small>
                            @else
                                <small class="form-text text-muted">Current: N/A</small>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="positions">Controller's position <small class="form-text"> (Optional)</small></label>
                            <input 
                                id="positions"
                                class="form-control @error('position') is-invalid @enderror"
                                type="text"
                                name="position"
                                list="positionsList"
                                value="{{ old('position', $feedback->referencePosition ? $feedback->referencePosition->callsign : '') }}"
                                >

                            <datalist id="positionsList">
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
                            @if($feedback->referencePosition)
                                <small class="form-text text-muted">Current: {{ $feedback->referencePosition->callsign }}</small>
                            @else
                                <small class="form-text text-muted">Current: N/A</small>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('reports.feedback') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success">Update Feedback</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
