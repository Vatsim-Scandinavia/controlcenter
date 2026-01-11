<div class="modal fade" id="{{ Str::camel($requestType->getName()) }}" tabindex="-1" aria-labelledby="{{ Str::camel($requestType->getName()) }}Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="{{ Str::camel($requestType->getName()) }}Label">Request</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <form action="{{ route('task.store') }}" method="POST">

                    @csrf
                
                    <div class="alert alert-primary">
                        @if($requestType->showConnectedRatings())
                            <i class="fas {{ $requestType->getIcon() }}"></i> {{ $requestType->getName() }} for <b>{{ $training->getInlineRatings(true) }}</b> rating
                        @else
                            <i class="fas {{ $requestType->getIcon() }}"></i> {{ $requestType->getName() }}
                        @endif
                    </div>

                    {{-- Solo Days Tracking for Solo Endorsement requests --}}
                    @if($requestType->getName() == 'Solo Endorsement')
                        <div class="alert alert-info">
                            <h6 class="alert-heading mb-3"><strong>Solo Endorsements</strong></h6>
                            <div class="mb-2">
                                <strong>Solo days remaining: {{ $soloDaysStats['remaining_days'] }} of {{ $soloDaysStats['max_days'] }}</strong>
                            </div>
                            <div class="mb-2">
                                Days granted: {{ $soloDaysStats['total_days'] }}
                            </div>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-{{ $soloDaysStats['remaining_days'] > 20 ? 'primary' : ($soloDaysStats['remaining_days'] > 10 ? 'warning' : 'danger') }}" 
                                     role="progressbar" 
                                     style="width: {{ ($soloDaysStats['total_days'] / $soloDaysStats['max_days']) * 100 }}%;" 
                                     aria-valuenow="{{ $soloDaysStats['total_days'] }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="{{ $soloDaysStats['max_days'] }}">
                                    {{ $soloDaysStats['total_days'] }}/{{ $soloDaysStats['max_days'] }} days
                                </div>
                            </div>
                            @if($soloDaysStats['remaining_days'] <= 10)
                                <div class="mt-2">
                                    <i class="fas fa-exclamation-triangle text-{{ $soloDaysStats['remaining_days'] > 0 ? 'warning' : 'danger' }}"></i>
                                    <small class="text-{{ $soloDaysStats['remaining_days'] > 0 ? 'warning' : 'danger' }}">
                                        {{ $soloDaysStats['remaining_days'] > 0 ? 'Low solo days remaining!' : 'Solo day limit reached!' }}
                                    </small>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="mt-3">
                        <label class="form-label" for="user">Send request to</label>
                        <div class="mt-1">
                            <input 
                                id="{{ Str::camel($requestType->getName()) }}User"
                                class="form-control"
                                type="text"
                                name="assignee_user_id"
                                list="{{ Str::camel($requestType->getName()) }}UserList"
                                autocomplete="off"
                                placeholder="Write name here or quick add below"
                                required
                            >
                            <datalist id="{{ Str::camel($requestType->getName()) }}UserList">
                                @foreach(\App\Models\User::has('groups')->get() as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </datalist>

                            <div>
                                @foreach($requestPopularAssignees as $user)
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="document.getElementById('{{ Str::camel($requestType->getName()) }}User').value = '{{ $user->id }}'">
                                        <i class="fas fa-bolt"></i>
                                        {{ $user->name }}
                                    </button>
                                @endforeach
                            </div>

                            <div class="mt-3">
                                <input type="hidden" name="type" value="{{ $requestType::class }}">
                                <input type="hidden" name="subject_user_id" value="{{ $training->user->id }}">
                                <input type="hidden" name="subject_training_id" value="{{ $training->id }}">
                            </div>
                        </div>
                    </div>

                    @if($requestType->requireCheckboxConfirmation() !== false)
                        <div class="mt-1">
                            <input class="form-check-input" type="checkbox" id="{{ Str::camel($requestType->getName()) }}Checkbox" required>
                            <label class="form-check-label" for="{{ Str::camel($requestType->getName()) }}Checkbox">
                                {{ $requestType->requireCheckboxConfirmation() }}
                            </label>
                        </div>
                    @endif

                    @if($requestType->requireRatingSelection() !== false && $training->ratings->whereNotNull('vatsim_rating')->count() > 1)
                        <div class="mt-3">
                            <label class="form-label" for="{{ Str::camel($requestType->getName()) }}Rating">Choose {{ Str::lower($requestType->getName()) }}</label>
                            <select class="form-select" id="{{ Str::camel($requestType->getName()) }}Rating" name="subject_training_rating_id" required>
                                @foreach($training->ratings->whereNotNull('vatsim_rating') as $rating)
                                    <option value="{{ $rating->id }}">{{ $rating->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($requestType->allowMessage())
                        <div class="mt-3">
                            <label class="form-label" for="{{ Str::camel($requestType->getName()) }}Message">Message</label>
                            <input type="text" class="form-control" id="{{ Str::camel($requestType->getName()) }}Message" name="message" minlength="3" maxlength="255" required>
                        </div>
                    @endif

                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Send request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>