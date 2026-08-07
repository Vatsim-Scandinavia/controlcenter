<div x-data="{ current: { submitter: '', submitted: '', feedback: '', controller: '', position: '', controllerLabel: '', positionLabel: '', updateUrl: '' } }">

    <div class="card shadow mb-4">
        <div class="card-header bg-primary py-3">
            <h6 class="m-0 fw-bold text-white">Feedback</h6>
        </div>
        <div class="card-body p-0">
            <div class="p-3 border-bottom">
                <div class="row g-2">
                    <div class="col-md-2">
                        <livewire:combobox
                            wire:model.live="controller"
                            :provider="\App\Support\Comboboxes\FeedbackControllerOptions::class"
                            :min-chars="2"
                            placeholder="Controller…"
                            key="combo-controller" />
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" wire:model.live="area">
                            <option value="">All areas</option>
                            @foreach($areas as $a)
                                <option value="{{ $a->id }}">{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <livewire:combobox
                            wire:model.live="position"
                            :provider="\App\Support\Comboboxes\FeedbackPositionOptions::class"
                            :context="['area' => $area]"
                            :min-chars="1"
                            placeholder="Position…"
                            key="combo-position" />
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control form-control-sm"
                            placeholder="Submitter…"
                            wire:model.live.debounce.300ms="submitter">
                    </div>
                    <div class="col">
                        <input type="text" class="form-control form-control-sm"
                            placeholder="Search feedback text…"
                            wire:model.live.debounce.300ms="search">
                    </div>
                    @if($this->hasActiveFilters())
                        <div class="col-auto">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                wire:click="clearFilters" title="Clear filters">
                                <i class="fas fa-xmark"></i> Clear
                            </button>
                        </div>
                    @endif
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-leftpadded mb-0" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th role="button" wire:click="sortByReceived">
                                Received
                                <span aria-hidden="true">@if($sortDirection === 'desc') ↓ @else ↑ @endif</span>
                                <span class="visually-hidden">
                                    @if($sortDirection === 'desc') Sorted newest first @else Sorted oldest first @endif
                                </span>
                            </th>
                            <th>Submitter</th>
                            <th>Controller</th>
                            <th>Position</th>
                            <th>Area</th>
                            <th>Feedback</th>
                            @can('update', \App\Models\Feedback::class)
                                <th>Actions</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($feedbacks as $f)
                            <tr wire:key="feedback-{{ $f->id }}">
                                <td>{{ $f->created_at->toEuropeanDateTime() }}</td>
                                <td><a href="{{ route('user.show', $f->submitter->id) }}">{{ $f->submitter->name }} ({{ $f->submitter_user_id }})</a></td>
                                <td>
                                    @isset($f->referenceUser)
                                        <a href="{{ route('user.show', $f->referenceUser) }}">{{ $f->referenceUser->name }} ({{ $f->referenceUser->id }})</a>
                                    @else
                                        N/A
                                    @endisset
                                </td>
                                <td>{{ $f->referencePosition?->callsign ?? 'N/A' }}</td>
                                <td>{{ $f->referencePosition?->area?->name ?? 'N/A' }}</td>
                                <td>{!! nl2br(e($f->feedback)) !!}</td>
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
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->can('update', \App\Models\Feedback::class) ? 7 : 6 }}" class="text-center text-muted py-4">No feedback found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <label class="me-2 mb-0 small">Per page</label>
                <select class="form-select form-select-sm w-auto" wire:model.live="perPage">
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            @if($feedbacks->hasPages())
                <div class="flex-grow-1 ms-3">{{ $feedbacks->links() }}</div>
            @endif
        </div>
    </div>

    @can('update', \App\Models\Feedback::class)
        {{-- Bootstrap owns this element's open/close state; wire:ignore keeps
             Livewire re-renders (the one that loads the datalists on open) from
             morphing away the `show` class mid-animation. The open/close events
             fire on this element, so the flag is toggled from here directly. --}}
        <div wire:ignore class="modal fade" id="feedback-edit-modal" tabindex="-1"
            aria-labelledby="feedback-edit-modal-label" aria-hidden="true"
            x-init="
                $el.addEventListener('show.bs.modal', () => $wire.set('showReferenceOptions', true));
                $el.addEventListener('hidden.bs.modal', () => $wire.set('showReferenceOptions', false));
            ">
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

        {{-- Options are populated lazily once the modal opens; see the
             show.bs.modal / hidden.bs.modal handlers on the modal element. --}}
        <datalist id="feedback-controllers-list">
            @foreach($editControllers as $controller)
                @browser('isFirefox')
                    <option>{{ $controller->id }}</option>
                @else
                    <option value="{{ $controller->id }}">{{ $controller->name }}</option>
                @endbrowser
            @endforeach
        </datalist>

        <datalist id="feedback-positions-list">
            @foreach($editPositions as $position)
                @browser('isFirefox')
                    <option>{{ $position->callsign }}</option>
                @else
                    <option value="{{ $position->callsign }}">{{ $position->name }}</option>
                @endbrowser
            @endforeach
        </datalist>
    @endcan
</div>
