@extends('layouts.app')

@section('title', 'Feedback')

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Feedback
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-cookie="true"
                        data-cookie-id-table="mentors"
                        data-cookie-expire="90d"
                        data-page-size="25"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true">
                        <thead class="table-light">
                            <tr>
                                <th data-field="received" data-sortable="true">Received</th>
                                <th data-field="submitter" data-sortable="true" data-filter-control="input">Submitter</th>
                                <th data-field="controller" data-sortable="true" data-filter-control="select">Controller</th>
                                <th data-field="position" data-sortable="true" data-filter-control="select">Position</th>
                                <th data-field="feedback" data-sortable="false" data-filter-control="input">Feedback</th>
                                @if(Auth::user()->isModeratorOrAbove())
                                    <th data-field="actions" data-sortable="false">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($feedback as $f)
                                <tr>
                                    <td>{{ $f->created_at->toEuropeanDateTime() }}</td>
                                    <td><a href="{{ route('user.show', $f->submitter->id) }}">{{ $f->submitter->name }} ({{ $f->submitter_user_id }})</a></td>
                                    <td>
                                        @isset($f->referenceUser)
                                            <a href="{{ route('user.show', $f->referenceUser) }}">{{ $f->referenceUser->name }} ({{ $f->referenceUser->id }})</a>
                                        @else
                                            N/A
                                        @endisset
                                    </td>
                                    <td>
                                        @isset($f->referencePosition)
                                            {{ $f->referencePosition->callsign }}
                                        @else
                                            N/A
                                        @endisset
                                    </td>
                                    <td>
                                        {!! nl2br($f->feedback) !!}
                                    </td>
                                    @if(Auth::user()->isModeratorOrAbove())
                                        <td>
                                            <button type="button"
                                                class="btn btn-sm btn-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#feedback-edit-modal-{{ $f->id }}">
                                                Edit
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

@if(Auth::user()->isModeratorOrAbove())
    @foreach($feedback as $f)
        <div class="modal fade" id="feedback-edit-modal-{{ $f->id }}" tabindex="-1"
            aria-labelledby="feedback-edit-modal-label-{{ $f->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="feedback-edit-modal-label-{{ $f->id }}">Edit Feedback</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('feedback.update', $f->id) }}" method="POST">
                            @method('PATCH')
                            @csrf

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Submitter</label>
                                    <input class="form-control" type="text"
                                        value="{{ $f->submitter->name }} ({{ $f->submitter_user_id }})" disabled>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Submitted</label>
                                    <input class="form-control" type="text"
                                        value="{{ $f->created_at->toEuropeanDateTime() }}" disabled>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label class="form-label">Feedback Text</label>
                                    <textarea class="form-control" rows="5" disabled>{{ $f->feedback }}</textarea>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label" for="controllers-{{ $f->id }}">Controller
                                        <small class="form-text"> (Optional)</small></label>
                                    <input
                                        id="controllers-{{ $f->id }}"
                                        class="form-control @error('controller') is-invalid @enderror"
                                        type="text"
                                        name="controller"
                                        list="controllersList-{{ $f->id }}"
                                        value="{{ old('controller', $f->referenceUser ? $f->referenceUser->id : '') }}"
                                    >

                                    <datalist id="controllersList-{{ $f->id }}">
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
                                    @if($f->referenceUser)
                                        <small class="form-text text-muted">Current:
                                            {{ $f->referenceUser->name }} ({{ $f->referenceUser->id }})
                                        </small>
                                    @else
                                        <small class="form-text text-muted">Current: N/A</small>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="positions-{{ $f->id }}">Controller's position
                                        <small class="form-text"> (Optional)</small></label>
                                    <input
                                        id="positions-{{ $f->id }}"
                                        class="form-control @error('position') is-invalid @enderror"
                                        type="text"
                                        name="position"
                                        list="positionsList-{{ $f->id }}"
                                        value="{{ old('position', $f->referencePosition ? $f->referencePosition->callsign : '') }}"
                                    >

                                    <datalist id="positionsList-{{ $f->id }}">
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
                                    @if($f->referencePosition)
                                        <small class="form-text text-muted">Current:
                                            {{ $f->referencePosition->callsign }}
                                        </small>
                                    @else
                                        <small class="form-text text-muted">Current: N/A</small>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success">Update Feedback</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif

@endsection