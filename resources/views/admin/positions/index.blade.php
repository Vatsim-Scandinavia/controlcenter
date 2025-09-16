@extends('layouts.app')

@section('title', 'Positions')

@can('create-any', App\Models\Position::class)
    @section('title-flex')
        <div>
            <a href="#" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#create-position-modal">
                <i class="fas fa-plus"></i>
                Create Position
            </a>
        </div>
    @endsection
@endcan

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">Positions</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless table-leftpadded table-striped" width="100%"
                            cellspacing="0" data-page-size="25" data-toggle="table" data-pagination="true"
                            data-filter-control="true" data-sort-reset="true">
                            <thead class="table-light">
                                <tr>
                                    <th data-field="callsign" data-sortable="true" data-filter-control="input"
                                        data-visible-search="true">Callsign</th>
                                    <th data-field="name" data-sortable="true" data-filter-control="input">Name</th>
                                    <th data-field="frequency" data-sortable="true" data-filter-control="input">Frequency
                                    </th>
                                    <th data-field="fir" data-sortable="true" data-filter-control="input">FIR</th>
                                    <th data-field="rating_name" data-sortable="true" data-filter-control="select">Rating
                                    </th>
                                    <th data-field="area_name" data-sortable="true" data-filter-control="select">Area</th>
                                    <th data-field="actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($positions as $position)
                                    <tr data-callsign="{{ $position->callsign }}" data-name="{{ $position->name }}"
                                        data-frequency="{{ $position->frequency }}" data-fir="{{ $position->fir }}"
                                        data-rating_name="{{ $position->rating_name }}"
                                        data-area_name="{{ $position->area->name }}">
                                        <td><span class="badge bg-primary">{{ $position->callsign }}</span></td>
                                        <td>{{ $position->name }}</td>
                                        <td><code>{{ $position->frequency }}</code></td>
                                        <td>{{ $position->fir }}</td>
                                        <td>{{ $position->rating_name }}</td>
                                        <td>{{ $position->area->name }}</td>
                                        <td class="text-nowrap">
                                            @can('update', $position)
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#edit-position-modal-{{ $position->id }}">Edit</button>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#delete-position-modal"
                                                    data-bs-position-id="{{ $position->id }}"
                                                    data-bs-position-callsign="{{ $position->callsign }}"
                                                    data-bs-action="{{ route('positions.destroy', $position) }}">
                                                    Delete
                                                </button>
                                             @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="create-position-modal" tabindex="-1" aria-labelledby="create-position-modal-label"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="create-position-modal-label">Create Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('positions.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="callsign" class="form-label">Callsign</label>
                            <input type="text" class="form-control" id="callsign" name="callsign" required>
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="frequency" class="form-label">Frequency</label>
                            <input type="text" class="form-control" id="frequency" name="frequency" required>
                        </div>
                        <div class="mb-3">
                            <label for="fir" class="form-label">FIR</label>
                            <input type="text" class="form-control" id="fir" name="fir" required>
                        </div>
                        <div class="mb-3">
                            <label for="rating" class="form-label">Rating</label>
                            <div class="btn-group input-group-sm w-100" role="group">
                                @foreach ($ratings as $rating)
                                    <input type="radio" class="btn-check" id="create_rating_{{ $rating->name }}"
                                        name="rating" value="{{ $rating->value }}">
                                    <label class="btn btn-outline-secondary"
                                        for="create_rating_{{ $rating->name }}">{{ $rating->name }}</label>
                                @endforeach
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="area_id" class="form-label">Area</label>
                            <select class="form-control" id="area_id" name="area_id" required>
                                <option value="">Select an area...</option>
                                @foreach ($areas as $area)
                                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @foreach ($positions as $position)
        <div class="modal fade" id="edit-position-modal-{{ $position->id }}" tabindex="-1"
            aria-labelledby="edit-position-modal-label-{{ $position->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="edit-position-modal-label-{{ $position->id }}">Edit Position</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('positions.update', $position) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label for="callsign" class="form-label">Callsign</label>
                                <input type="text" class="form-control" id="callsign" name="callsign"
                                    value="{{ $position->callsign }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="{{ $position->name }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="frequency" class="form-label">Frequency</label>
                                <input type="text" class="form-control" id="frequency" name="frequency"
                                    value="{{ $position->frequency }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="fir" class="form-label">FIR</label>
                                <input type="text" class="form-control" id="fir" name="fir"
                                    value="{{ $position->fir }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="rating" class="form-label">Rating</label>
                                <fieldset>
                                    <div class="btn-group input-group-sm w-100" role="group">
                                        @foreach ($ratings as $rating)
                                            <input type="radio" class="btn-check"
                                                id="edit_rating_{{ $position->id }}_{{ $rating->name }}" name="rating"
                                                value="{{ $rating->value }}"
                                                {{ $position->hasBaseRating($rating) ? 'checked' : '' }}>
                                            <label class="btn btn-outline-primary"
                                                for="edit_rating_{{ $position->id }}_{{ $rating->name }}">{{ $rating->name }}</label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            </div>
                            <div class="mb-3">
                                <label for="area_id" class="form-label">Area</label>
                                <select class="form-control" id="area_id" name="area_id" required>
                                    <option value="">Select an area...</option>
                                    @foreach ($areas as $area)
                                        <option value="{{ $area->id }}"
                                            {{ $position->area_id == $area->id ? 'selected' : '' }}>{{ $area->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="modal fade" id="delete-position-modal" tabindex="-1" aria-labelledby="delete-position-modal-label"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="delete-position-modal-label">Delete Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this position?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="delete-position-form" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var deleteModal = document.getElementById('delete-position-modal');
            deleteModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var positionId = button.getAttribute('data-bs-position-id');
                var positionCallsign = button.getAttribute('data-bs-position-callsign');
                var action = button.getAttribute('data-bs-action');

                var modalBody = deleteModal.querySelector('.modal-body');
                modalBody.innerHTML = 'Are you sure you want to delete the position <strong>' +
                    positionCallsign + '</strong>?';

                var deleteForm = deleteModal.querySelector('#delete-position-form');
                deleteForm.action = action;
            });
        });
    </script>
@endsection
