<div class="modal fade" id="{{ Str::camel($requestType->getName()) }}" tabindex="-1" aria-labelledby="{{ Str::camel($requestType->getName()) }}Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="{{ Str::camel($requestType->getName()) }}Label">Request</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <form action="{{ route('tasks.store') }}" method="POST">

                    @csrf
                
                    <div class="alert alert-primary">
                        @if($requestType->showConnectedRatings())
                            <i class="fas {{ $requestType->getIcon() }}"></i> {{ $requestType->getName() }} for <b>{{ $training->getInlineRatings(true) }}</b> rating
                        @else
                            <i class="fas {{ $requestType->getIcon() }}"></i> {{ $requestType->getName() }}
                        @endif
                    </div>

                    <div class="mt-3">
                        <label class="form-label" for="user">Send request to</label>
                        <input 
                            id="user"
                            class="form-control"
                            type="text"
                            name="recipient_user_id"
                            list="userList"
                            autocomplete="off"
                        >
                        <datalist id="userList">
                            @foreach(\App\Models\User::has('groups')->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </datalist>

                        <input type="hidden" name="type" value="{{ $requestType::class }}">
                        <input type="hidden" name="reference_user_id" value="{{ $training->user->id }}">
                        <input type="hidden" name="reference_training_id" value="{{ $training->id }}">
                    </div>

                    @if($requestType->allowMessage())
                        <div class="mt-3">
                            <label class="form-label" for="{{ Str::camel($requestType->getName()) }}Message">Message</label>
                            <input type="text" class="form-control" id="{{ Str::camel($requestType->getName()) }}Message" name="message" minlength="3" maxlength="255">
                        </div>
                    @endif

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Send request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>