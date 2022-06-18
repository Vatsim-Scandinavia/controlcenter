@extends('layouts.app')

@section('title', 'Training Endorsements')
@section('title-extension')
    @can('create', \App\Models\Endorsement::class)
        <a href="{{ route('endorsements.create') }}" class="btn btn-sm btn-success">Add new endorsement</a>
    @endcan
@endsection
@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">Training Endorsements</h6> 
            </div>        
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-page-size="25"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true"
                        >
                        <thead class="thead-light">
                            <tr>
                                <th data-field="student" class="w-50" data-sortable="true" data-filter-control="input">Member</th>
                                <th data-field="status" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml">Status</th>
                                <th data-field="type" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml">Type</th>
                                <th data-field="position" data-sortable="true" data-filter-control="input">Position</th>
                                <th data-field="validfrom" data-sortable="true" data-filter-control="select">Created</th>
                                <th data-field="validto" data-sortable="true" data-filter-control="select">Expire</th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach($endorsements as $e)
                                @if(($e->expired || $e->revoked) && $e->type == 'S1' && $e->user->hasActiveEndorsement('S1'))
                                    {{--  Don't show old entries if there's another active one --}}
                                @elseif(($e->expired || $e->revoked) && $e->type == 'SOLO' && $e->user->hasActiveEndorsement('SOLO'))
                                    {{--  Don't show old entries if there's another active one --}}
                                @else
                                    <tr>
                                        <td>
                                            @can('view', $e->user)
                                                <a href="{{ route('user.show', $e->user->id) }}">{{ $e->user->name }} ({{ $e->user->id }})</a>
                                            @else
                                                {{ $e->user->name }} ({{ $e->user->id }})
                                            @endcan
                                        </td>

                                        @if(Carbon\Carbon::now() < $e->valid_to)
                                            <td class="text-center bg-success text-white">
                                                <i class="fas fa-check-circle"></i> Active
                                            </td>
                                        @else
                                            <td class="text-center bg-warning text-white">
                                                <i class="fas fa-exclamation-triangle"></i> Expired
                                            </td>
                                        @endif

                                        @if($e->type == 'SOLO')
                                            <td>
                                                <i class="fas fa-graduation-cap text-warning"></i>
                                                Solo
                                            </td>
                                        @else
                                            <td>
                                                <i class="fas fa-book-open text-info"></i>
                                                S1
                                            </td>
                                        @endif

                                        <td>
                                            @foreach($e->positions as $p)
                                                <span class="badge badge-dark">{{ $p->callsign }}</span>
                                            @endforeach
                                        </td>
                                        <td>{{ Carbon\Carbon::parse($e->valid_from)->toEuropeanDate() }}</td>
                                        <td>{{ Carbon\Carbon::parse($e->valid_to)->toEuropeanDateTime() }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
    
</div>

@endsection