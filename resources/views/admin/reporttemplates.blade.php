@extends('layouts.app')

@section('title', 'Report Templates')
@section('title-flex')
    <div>
        @can('create', \App\Models\TrainingReportTemplate::class)
            <a class="btn btn-success btn-sm" href="{{ route('admin.reporttemplates.create') }}">
                <i class="fas fa-plus"></i> New Template
            </a>
        @endcan
    </div>
@endsection

@section('content')

@if(Session::has('success'))
    <div class="alert alert-success" role="alert">
        <i class="fas fa-lg fa-check-circle"></i>&nbsp;{!! Session::pull("success") !!}
    </div>
@endif

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Training Report Templates</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Areas</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $template)
                                <tr>
                                    <td>{{ $template->name }}</td>
                                    <td>
                                        @if($template->areas->count() > 0)
                                            @foreach($template->areas as $area)
                                                <span class="badge bg-secondary">{{ $area->name }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">No areas assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($template->draft)
                                            <span class="badge bg-warning">Draft</span>
                                        @else
                                            <span class="badge bg-success">Published</span>
                                        @endif
                                    </td>
                                    <td>{{ $template->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @can('update', $template)
                                                <a href="{{ route('admin.reporttemplates.edit', $template->id) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            @endcan
                                            @can('delete', $template)
                                                <form action="{{ route('admin.reporttemplates.destroy', $template->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No templates found. <a href="{{ route('admin.reporttemplates.create') }}">Create one?</a></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

