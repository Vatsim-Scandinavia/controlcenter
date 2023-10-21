@extends('layouts.app')

@section('title', 'Tasks')
@section('title-flex')
    <div>
        <i class="fas fa-filter"></i>&nbsp;Filter:&nbsp;
        <a class="btn btn-sm {{ ($activeFilter != 'sent' && $activeFilter != 'archived') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('tasks') }}">Open</a>
        <a class="btn btn-sm {{ ($activeFilter == 'sent') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('tasks.filtered', 'sent') }}">Sent</a>
        <a class="btn btn-sm {{ ($activeFilter == 'archived') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('tasks.filtered', 'archived') }}">Archived</a>
    </div>
@endsection

@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            @if($tasks->count())
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">Tasks</h6> 
                </div>
            @endif
            <div class="card-body p-0">
                <div class="table-responsive">

                    @if($tasks->count())

                        <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ (in_array($activeFilter, ['archived'])) ? 'Closed' : 'Created' }}</th>
                                    <th>Subject</th>
                                    <th>Request</th>
                                    <th>{{ (!in_array($activeFilter, ['sent'])) ? 'Creator' : 'Assigned to' }}</th>
                                    <th>{{ (!in_array($activeFilter, ['sent', 'archived'])) ? 'Actions' : 'Status' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tasks as $task)
                                    @include('tasks.parts.row', ['task' => $task, 'activeFilter' => $activeFilter])
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    @if($tasks->count() == 0)
                        <div class="text-center pt-4 pb-4">
                            <i class="fas fa-umbrella-beach" style="font-size: 5rem;"></i>
                            <p class="pt-4 fs-5">
                                You have no tasks
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
</div>

@endsection


@section('js')
    @include('scripts.tooltips')
@endsection