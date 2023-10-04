@extends('layouts.app')

@section('title', 'Tasks')
@section('title-flex')
    <div>
        <i class="fas fa-filter"></i>&nbsp;Filter:&nbsp;
        <a class="btn btn-sm btn-primary" href="">Open</a>
        <a class="btn btn-sm btn-outline-primary" href="">Sent</a>
        <a class="btn btn-sm btn-outline-primary" href="">Archived</a>
    </div>
@endsection

@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Tasks</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>Task #</th>
                                <th>Received</th>
                                <th>Type</th>
                                <th>Regarding</th>
                                <th>Request</th>
                                <th>From</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach($tasks as $task)

                                <tr>
                                    <td>{{ $task->id }}</td>
                                    <td><span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $task->created_at->toEuropeanDateTime() }}">{{ $task->created_at->diffForHumans() }}</span></td>
                                    <td><i class="fas {{ $taskTypes[$task->type]["icon"] }}"></i> {{ $taskTypes[$task->type]["text"] }}</td>
                                    <td><a href="{{ route('user.show', $task->reference_user_id) }}">{{ \App\Models\User::find($task->reference_user_id)->name }} ({{ $task->reference_user_id }})</a></td>
                                    <td>
                                        @if($task->type == \App\Helpers\TaskType::THEORY_EXAM->value)

                                            @if(\App\Models\User::find($task->reference_user_id)->division == 'EUD')
                                                <i class="fas fa-bolt"></i>
                                                <a href="https://www.atsimtest.com/index.php?cmd=admin&sub=memberdetail&memberid={{ $task->reference_user_id }}" target="_blank" class="link-offset-1 dotted-underline">Grant theoretical exam access</a>
                                            @else
                                                Grant theoretical exam access
                                            @endif

                                        @elseif($task->type == \App\Helpers\TaskType::SOLO_ENDORSEMENT->value)

                                            <i class="fas fa-bolt"></i>
                                            <a href="{{ route('endorsements.create.id', $task->reference_user_id) }}" target="_blank" class="link-offset-1 dotted-underline">Grant solo endorsement</a>

                                        @elseif($task->type == \App\Helpers\TaskType::RATING_UPGRADE->value)

                                            @if(\App\Models\User::find($task->reference_user_id)->division == 'EUD')
                                                <i class="fas fa-bolt"></i>
                                                <a href="https://www.atsimtest.com/index.php?cmd=admin&sub=memberdetail&memberid={{ $task->reference_user_id }}" target="_blank" class="link-offset-1 dotted-underline">Upgrade rating to {{ \App\Models\Training::find($task->reference_training_id)->getInlineRatings() }}</a>
                                            @else
                                                Upgrade rating to {{ \App\Models\Training::find($task->reference_training_id)->getInlineRatings() }}
                                            @endif

                                            @if(\App\Models\Training::find($task->reference_training_id)->status == \App\Helpers\TrainingStatus::COMPLETED->value)
                                                <span class="fw-light">(Examination completed)</span>
                                            @endif
                                            
                                        @elseif($task->type == \App\Helpers\TaskType::MEMO->value)
                                            {{ $task->message }}
                                        @endif
                                    </td>
                                    <td>
                                        @isset($task->sender_user_id)
                                            <a href="{{ route('user.show', $task->sender_user_id) }}">{{ \App\Models\User::find($task->sender_user_id)->name }} ({{ $task->sender_user_id }})</a>
                                        @else
                                            System
                                        @endisset
                                    </td>
                                    
                                    <td>
                                        <a href="" class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i> Complete</a>
                                        <a href="" class="btn btn-sm btn-outline-danger"><i class="fas fa-xmark"></i> Decline</a>
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

@endsection


@section('js')
    @include('scripts.tooltips')
@endsection