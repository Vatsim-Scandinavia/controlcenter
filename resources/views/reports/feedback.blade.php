@extends('layouts.app')

@section('title', 'Feedback')

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div x-data="{ current: { submitter: '', submitted: '', feedback: '', controller: '', position: '', controllerLabel: '', positionLabel: '', updateUrl: '' } }">

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
                                    <th data-field="controller" data-sortable="true" data-filter-control="input">Controller</th>
                                    <th data-field="position" data-sortable="true" data-filter-control="select">Position</th>
                                    <th data-field="area" data-sortable="true" data-filter-control="select">Area</th>
                                    <th data-field="feedback" data-sortable="false" data-filter-control="input">Feedback</th>
                                    @can('update', \App\Models\Feedback::class)
                                        <th data-field="actions" data-sortable="false">Actions</th>
                                    @endcan
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
                                        <td>{{ $f->referencePosition?->area?->name ?? 'N/A' }}</td>
                                        <td>
                                            {!! nl2br(e($f->feedback)) !!}
                                        </td>
                                        @can('update', $f)
                                            <td>
                                                <button type="button"
                                                    class="btn btn-sm btn-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#feedback-edit-modal"
                                                    @click="current = @js([
                                                        'submitter' => $f->submitter->name.' ('.$f->submitter_user_id.')',
                                                        'submitted' => $f->created_at->toEuropeanDateTime(),
                                                        'feedback' => $f->feedback,
                                                        'controller' => $f->referenceUser?->id ?? '',
                                                        'position' => $f->referencePosition?->callsign ?? '',
                                                        'controllerLabel' => $f->referenceUser ? $f->referenceUser->name.' ('.$f->referenceUser->id.')' : 'N/A',
                                                        'positionLabel' => $f->referencePosition?->callsign ?? 'N/A',
                                                        'updateUrl' => route('feedback.update', $f->id),
                                                    ])">
                                                    Edit
                                                </button>
                                            </td>
                                        @endcan
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('update', \App\Models\Feedback::class)
        <div class="modal fade" id="feedback-edit-modal" tabindex="-1"
            aria-labelledby="feedback-edit-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="feedback-edit-modal-label">Edit Feedback</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form :action="current.updateUrl" method="POST">
                            @method('PATCH')
                            @csrf

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Submitter</label>
                                    <input class="form-control" type="text" x-model="current.submitter" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Submitted</label>
                                    <input class="form-control" type="text" x-model="current.submitted" disabled>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label class="form-label">Feedback Text</label>
                                    <textarea class="form-control" rows="5" disabled :value="current.feedback"></textarea>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label" for="feedback-edit-controller">Controller
                                        <small class="form-text"> (Optional)</small></label>
                                    <input
                                        id="feedback-edit-controller"
                                        class="form-control @error('controller') is-invalid @enderror"
                                        type="text"
                                        name="controller"
                                        list="feedback-controllers-list"
                                        x-model="current.controller"
                                    >
                                    @error('controller')
                                        <span class="text-danger">{{ $errors->first('controller') }}</span>
                                    @enderror
                                    <small class="form-text text-muted">Current: <span x-text="current.controllerLabel"></span></small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="feedback-edit-position">Controller's position
                                        <small class="form-text"> (Optional)</small></label>
                                    <input
                                        id="feedback-edit-position"
                                        class="form-control @error('position') is-invalid @enderror"
                                        type="text"
                                        name="position"
                                        list="feedback-positions-list"
                                        x-model="current.position"
                                    >
                                    @error('position')
                                        <span class="text-danger">{{ $errors->first('position') }}</span>
                                    @enderror
                                    <small class="form-text text-muted">Current: <span x-text="current.positionLabel"></span></small>
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

        <datalist id="feedback-controllers-list">
            @foreach($controllers as $controller)
                @browser('isFirefox')
                    <option>{{ $controller->id }}</option>
                @else
                    <option value="{{ $controller->id }}">{{ $controller->name }}</option>
                @endbrowser
            @endforeach
        </datalist>

        <datalist id="feedback-positions-list">
            @foreach($positions as $position)
                @browser('isFirefox')
                    <option>{{ $position->callsign }}</option>
                @else
                    <option value="{{ $position->callsign }}">{{ $position->name }}</option>
                @endbrowser
            @endforeach
        </datalist>
    @endcan

</div>

@endsection
