@extends('layouts.app')

@section('title', 'Modules')

@section('content')

<div class="row">
    <div class="col-12">

        @if(Session::has('success'))
            <div class="alert alert-success" role="alert">
                <i class="fas fa-lg fa-check-circle"></i>&nbsp;{{ Session::pull('success') }}
            </div>
        @endif

        @if(Session::has('error'))
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-lg fa-exclamation-circle"></i>&nbsp;{{ Session::pull('error') }}
            </div>
        @endif

        @if($safeMode)
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-lg fa-shield-halved"></i>&nbsp;Safe mode is enabled (<code>MODULES_SAFE_MODE</code>). No modules are booted regardless of the state shown below.
            </div>
        @endif

        @foreach($moduleErrors as $moduleError)
            <div class="alert alert-danger" role="alert">{{ $moduleError }}</div>
        @endforeach

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Installed Modules</h6>
            </div>
            <div class="card-body">
                <p class="text-muted fs-sm">
                    Modules are trusted, in-process extensions installed into the image. The declared permissions and
                    data handling below are for transparency only &mdash; an enabled module runs with the full
                    privileges of the application.
                </p>

                @if(count($modules) === 0)
                    <p class="mb-0">No modules are installed.</p>
                @else
                    @php
                        $enabledCount = collect($modules)->filter(fn ($m) => $m->enabled)->count();
                        $incompatibleCount = collect($modules)->filter(fn ($m) => ! $m->compatible)->count();
                    @endphp
                    <p class="fs-sm mb-3" data-testid="module-health-summary">
                        <span class="badge bg-success">{{ $enabledCount }} enabled</span>
                        <span class="badge bg-secondary">{{ count($modules) }} installed</span>
                        @if($incompatibleCount > 0)
                            <span class="badge bg-danger">{{ $incompatibleCount }} incompatible</span>
                        @endif
                        @if(count($moduleErrors) > 0)
                            <span class="badge bg-warning text-dark">{{ count($moduleErrors) }} with errors</span>
                        @endif
                    </p>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <th>Version</th>
                                    <th>Status</th>
                                    <th>Data handled</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modules as $module)
                                    <tr>
                                        <td>
                                            <strong>{{ $module->manifest->name }}</strong>
                                            <div class="text-muted fs-sm">{{ $module->manifest->description }}</div>
                                            <div class="text-muted fs-sm"><code>{{ $module->slug() }}</code> &middot; {{ $module->source }}</div>
                                            @if($module->manifest->permissions)
                                                <div class="fs-sm mt-1">
                                                    @foreach($module->manifest->permissions as $permission => $permissionDescription)
                                                        <span class="badge bg-secondary" title="{{ $permissionDescription }}">{{ $permission }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $module->manifest->version }}</td>
                                        <td>
                                            @if(! $module->compatible)
                                                <span class="badge bg-danger">Incompatible</span>
                                                <div class="text-muted fs-sm">requires {{ $module->manifest->ccVersion }}</div>
                                            @elseif($module->enabled)
                                                <span class="badge bg-success">Enabled</span>
                                            @else
                                                <span class="badge bg-secondary">Disabled</span>
                                            @endif
                                        </td>
                                        <td class="fs-sm">
                                            @forelse($module->manifest->dataHandling as $note)
                                                <div>{{ $note }}</div>
                                            @empty
                                                <span class="text-muted">None declared</span>
                                            @endforelse
                                        </td>
                                        <td class="text-end">
                                            @if($module->enabled && in_array($module->slug(), $configurable, true))
                                                <a href="{{ route('admin.modules.settings', $module->slug()) }}" class="btn btn-sm btn-outline-primary mb-1">Settings</a>
                                            @endif
                                            @if($module->enabled)
                                                <form action="{{ route('admin.modules.disable', $module->slug()) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Disable</button>
                                                </form>
                                            @elseif($module->compatible)
                                                <form action="{{ route('admin.modules.enable', $module->slug()) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success">Enable</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>

@endsection
