@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Logs</h6>

                <form method="GET" action="{{ route('admin.logs') }}" class="d-flex gap-2 align-items-center mb-0">
                    <select name="log_name" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" @selected(request('log_name') === $category)>{{ ucfirst($category) }}</option>
                        @endforeach
                    </select>

                    <select name="level" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All levels</option>
                        @foreach($levels as $level)
                            <option value="{{ $level }}" @selected(request('level') === $level)>{{ ucfirst($level) }}</option>
                        @endforeach
                    </select>

                    @if(request('log_name') || request('level'))
                        <a href="{{ route('admin.logs') }}" class="btn btn-sm btn-light">Clear</a>
                    @endif
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Level</th>
                                <th>Category</th>
                                <th>Who</th>
                                <th>Description</th>
                                <th>Subject</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($log->created_at)->toEuropeanDateTime() }}</td>
                                <td><span class="text-{{ $log->level->value }}">{{ strtoupper($log->level->value) }}</span></td>
                                <td>{{ ucfirst($log->log_name) }}</td>
                                <td>
                                    @if($log->causer)
                                        <a href="{{ route('user.show', $log->causer_id) }}">{{ $log->causer->name }} ({{ $log->causer_id }})</a>
                                    @else
                                        SYSTEM
                                    @endif
                                </td>
                                <td>{{ $log->description }}</td>
                                <td>
                                    @if($log->subject_id)
                                        @if($log->subject_route)
                                            <a href="{{ $log->subject_route }}">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</a>
                                        @else
                                            {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                        @endif
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    @php
                                        $new = data_get($log->attribute_changes, 'attributes', []);
                                        $old = data_get($log->attribute_changes, 'old', []);
                                    @endphp
                                    @foreach($new as $key => $value)
                                        <div>{{ $key }}: @isset($old[$key]){{ $old[$key] }} → @endisset{{ is_scalar($value) ? $value : json_encode($value) }}</div>
                                    @endforeach
                                    @foreach($log->properties ?? [] as $key => $value)
                                        <div>{{ $key }}: {{ is_scalar($value) ? $value : json_encode($value) }}</div>
                                    @endforeach
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">No log entries found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($logs->hasPages())
                <div class="card-footer">
                    {{ $logs->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
