@extends('layouts.app')

@section('title', 'Positions')

@section('content')
    <div class="row">
        <div class="col-md-12">


            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">Positions</h6>
                    @if ($canCreate)
                        <a href="#" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#create-position-modal">Create Position</a>
                    @endif
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Callsign</th>
                                <th>Name</th>
                                <th>Frequency</th>
                                <th>FIR</th>
                                <th>Rating</th>
                                <th>Area</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($positions as $position)
                                <tr>
                                    <td>{{ $position->callsign }}</td>
                                    <td>{{ $position->name }}</td>
                                    <td>{{ $position->frequency }}</td>
                                    <td>{{ $position->fir }}</td>
                                    <td>{{ $position->rating }}</td>
                                    <td>{{ $position->area->name }}</td>
                                    <td>
                                        @if ($position->editable)
                                            <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#edit-position-modal-{{ $position->id }}">Edit</a>
                                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#delete-position-modal" data-bs-position-id="{{ $position->id }}" data-bs-position-callsign="{{ $position->callsign }}" data-bs-action="{{ route('positions.destroy', $position) }}">
                                                Delete
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Position Modal -->
    <div class="modal fade" id="create-position-modal" tabindex="-1" aria-labelledby="create-position-modal-label" aria-hidden="true">
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
                                    <input type="radio" class="btn-check" id="create_rating_{{ $rating->name }}" name="rating" value="{{ $rating->value }}">
                                    <label class="btn btn-outline-secondary" for="create_rating_{{ $rating->name }}">{{ $rating->name }}</label>
                                @endforeach
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="area_id" class="form-label">Area ID</label>
                            <input type="number" class="form-control" id="area_id" name="area_id" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Position Modals -->
    @foreach ($positions as $position)
        <div class="modal fade" id="edit-position-modal-{{ $position->id }}" tabindex="-1" aria-labelledby="edit-position-modal-label-{{ $position->id }}" aria-hidden="true">
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
                                <input type="text" class="form-control" id="callsign" name="callsign" value="{{ $position->callsign }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ $position->name }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="frequency" class="form-label">Frequency</label>
                                <input type="text" class="form-control" id="frequency" name="frequency" value="{{ $position->frequency }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="fir" class="form-label">FIR</label>
                                <input type="text" class="form-control" id="fir" name="fir" value="{{ $position->fir }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="rating" class="form-label">Rating</label>
                                <div class="btn-group input-group-sm w-100" role="group">
                                    @foreach ($ratings as $rating)
                                        <input type="radio" class="btn-check" id="edit_rating_{{ $position->id }}_{{ $rating->name }}" name="rating" value="{{ $rating->value }}" {{ $position->rating == $rating->value ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary" for="edit_rating_{{ $position->id }}_{{ $rating->name }}">{{ $rating->name }}</label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="area_id" class="form-label">Area ID</label>
                                <input type="number" class="form-control" id="area_id" name="area_id" value="{{ $position->area_id }}" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Delete Position Modal -->
    <div class="modal fade" id="delete-position-modal" tabindex="-1" aria-labelledby="delete-position-modal-label" aria-hidden="true">
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
        document.addEventListener('DOMContentLoaded', function () {
            var deleteModal = document.getElementById('delete-position-modal');
            deleteModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var positionId = button.getAttribute('data-bs-position-id');
                var positionCallsign = button.getAttribute('data-bs-position-callsign');
                var action = button.getAttribute('data-bs-action');

                var modalBody = deleteModal.querySelector('.modal-body');
                modalBody.innerHTML = 'Are you sure you want to delete the position <strong>' + positionCallsign + '</strong>?';

                var deleteForm = deleteModal.querySelector('#delete-position-form');
                deleteForm.action = action;
            });
        });
    </script>
@endsection
