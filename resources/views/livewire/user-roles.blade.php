<div class="card shadow mb-4">
    <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 fw-bold text-white">
            Access
        </h6>
        @if (count($this->grantableRoles()) > 0)
            <button type="button" class="btn btn-icon btn-light" wire:click="openAddModal"><i class="fas fa-plus"></i> Add role</button>
        @endif
    </div>
    <div class="card-body">
    @if ($status)
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ $status }}
            <button type="button" class="btn-close" wire:click="$set('status', null)"></button>
        </div>
    @endif
    @if ($error)
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $error }}
            <button type="button" class="btn-close" wire:click="$set('error', null)"></button>
        </div>
    @endif

    @php($displayName = fn ($role) => $roles[$role]['name'] ?? $role)

    {{-- Global roles: always shown, one per line, with an empty-state note --}}
    <div class="mb-3">
        <strong>Global</strong>
        @forelse ($globalAssignments as $a)
            @php($manageable = $this->canManage($a->role, null))
            <span class="badge {{ $manageable ? 'bg-primary' : 'bg-secondary' }} d-flex justify-content-between align-items-center w-100 mt-1"
                  wire:key="g-{{ $a->role }}">
                <span>{{ $displayName($a->role) }}</span>
                @if ($manageable)
                    <button type="button" class="btn-close btn-close-white"
                            style="font-size:.6rem"
                            aria-label="Remove role"
                            title="Remove {{ $displayName($a->role) }}"
                            wire:click="confirmRemoval('{{ $a->role }}', null)"></button>
                @endif
            </span>
        @empty
            <div class="text-muted mt-1">No global roles assigned</div>
        @endforelse
    </div>

    {{-- Area roles: one section per area, one role per line --}}
    @forelse ($areaGroups as $areaName => $assignments)
        <div class="mb-3" wire:key="area-{{ $areaName }}">
            <strong>{{ $areaName }}</strong>
            @foreach ($assignments as $a)
                @php($manageable = $this->canManage($a->role, $a->area_id))
                <span class="badge {{ $manageable ? 'bg-primary' : 'bg-secondary' }} d-flex justify-content-between align-items-center w-100 mt-1"
                      wire:key="a-{{ $a->role }}-{{ $a->area_id }}">
                    <span>
                        {{ $displayName($a->role) }}
                        @if ($a->role === 'mentor')<em class="ms-1 text-white-50">via Division API</em>@endif
                    </span>
                    @if ($manageable)
                        <button type="button" class="btn-close btn-close-white"
                                style="font-size:.6rem"
                                aria-label="Remove role"
                                title="Remove {{ $displayName($a->role) }}"
                                wire:click="confirmRemoval('{{ $a->role }}', {{ $a->area_id }})"></button>
                    @endif
                </span>
            @endforeach
        </div>
    @empty
        <div class="mb-3">
            <strong>Areas</strong>
            <div class="text-muted mt-1">No area roles assigned</div>
        </div>
    @endforelse

    </div>{{-- /card-body --}}

    @if ($showAddModal)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add role</h5>
                        <button type="button" class="btn-close" wire:click="closeAddModal"></button>
                    </div>
                    <div class="modal-body">
                      <div class="row g-4 gx-lg-5">
                        <div class="col-12 col-lg-6">
                        {{-- Step 1: choose the role --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold mb-0">1. Select a role</label>
                            <p class="text-muted small mb-2">Choose the role you want to grant.</p>
                            @foreach ($this->grantableRoles() as $key => $name)
                                <div class="form-check" wire:key="role-{{ $key }}">
                                    <input class="form-check-input" type="radio" name="selectedRole"
                                           id="role-{{ $key }}" value="{{ $key }}"
                                           wire:model.live="selectedRole">
                                    <label class="form-check-label" for="role-{{ $key }}">
                                        {{ $name }}
                                        @if (! empty($roles[$key]['description']))
                                            <span class="d-block text-muted small">{{ $roles[$key]['description'] }}</span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        </div>{{-- /col step 1 --}}

                        <div class="col-12 col-lg-6">
                        {{-- Step 2: choose where it applies --}}
                        <div>
                            <label class="form-label fw-semibold mb-0">2. Select the area(s) of responsibility</label>
                            <p class="text-muted small mb-2">Grant organisation-wide access and/or one or more areas.</p>

                            @if ($selectedRole)
                                @php($globalOption = $this->globalOptionFor($selectedRole))
                                {{-- Global access: kept visually separate from the area list --}}
                                <div class="border rounded bg-body-tertiary p-2 mb-3">
                                    <div class="text-uppercase text-muted fw-semibold small mb-1">Organisation-wide</div>
                                    <div class="form-check mb-0" wire:key="opt-global">
                                        <input class="form-check-input" type="checkbox"
                                               wire:model.live="selectedGlobal"
                                               id="option-global"
                                               @disabled(! $globalOption['enabled'])>
                                        <label class="form-check-label" for="option-global">
                                            Global
                                            @if (! $globalOption['enabled'])
                                                <small class="text-muted ms-1">({{ $globalOption['reason'] }})</small>
                                            @endif
                                        </label>
                                    </div>
                                </div>

                                {{-- Areas of responsibility --}}
                                <div class="text-uppercase text-muted fw-semibold small mb-1">Areas of responsibility</div>
                                @foreach ($this->areaOptionsFor($selectedRole) as $opt)
                                    <div class="form-check" wire:key="opt-{{ $opt['area']->id }}">
                                        <input class="form-check-input" type="checkbox"
                                               value="{{ $opt['area']->id }}" wire:model.live="selectedAreaIds"
                                               id="area-{{ $opt['area']->id }}"
                                               @disabled(! $opt['enabled'])>
                                        <label class="form-check-label" for="area-{{ $opt['area']->id }}">
                                            {{ $opt['area']->name }}
                                            @if (! $opt['enabled'])
                                                <small class="text-muted ms-1">({{ $opt['reason'] }})</small>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted mb-0">Select a role first.</p>
                            @endif
                        </div>
                        </div>{{-- /col step 2 --}}
                      </div>{{-- /row --}}
                    </div>
                    @php($targetCount = ($selectedGlobal ? 1 : 0) + count($selectedAreaIds))
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeAddModal">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="grant" @disabled(! $selectedRole || (! $selectedGlobal && count($selectedAreaIds) === 0))>
                            Grant{{ $targetCount >= 2 ? ' ×' . $targetCount : '' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($pendingRemoval)
        @php($isMentor = $pendingRemoval['role'] === 'mentor')
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Remove {{ $roles[$pendingRemoval['role']]['name'] ?? $pendingRemoval['role'] }}{{ $pendingRemoval['area_id'] !== null ? ' in ' . $this->pendingRemovalAreaName() : '' }}?</h5>
                        <button type="button" class="btn-close" wire:click="cancelRemoval"></button>
                    </div>
                    <div class="modal-body">
                        @if ($isMentor)
                            <div class="alert alert-danger mb-0">
                                This will notify the Division API.
                                @if ($this->removalWillDetach() && $this->removalTrainingCount() > 0)
                                    This will also detach this user's {{ $this->removalTrainingCount() }} training(s) in this area.
                                @endif
                            </div>
                        @else
                            <p class="mb-0">This will revoke the role immediately.</p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cancelRemoval">Cancel</button>
                        <button type="button" class="btn {{ $isMentor ? 'btn-danger' : 'btn-primary' }}" wire:click="remove">Remove</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
