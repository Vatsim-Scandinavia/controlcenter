@extends('layouts.app')

@section('title', 'MA/SC Endorsements')
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
                <h6 class="m-0 font-weight-bold text-white">Major Airport and Special Center Endorsements</h6> 
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
                                <th data-field="member" class="w-50" data-sortable="true" data-filter-control="input">Member</th>
                                <th data-field="active" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml">ATC Active</th>
                                @foreach($areas as $a)
                                    <th data-field="{{ $a->id }}" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml">{{ $a->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($endorsements as $e)
                                <tr>
                                    <td>
                                        @can('view', $e->user)
                                            <a href="{{ route('user.show', $e->user->id) }}">{{ $e->user->name }} ({{ $e->user->id }})</a>
                                        @else
                                            {{ $e->user->name }} ({{ $e->user->id }})
                                        @endcan
                                    </td>
                                    <td class="text-center text-white {{ $e->user->active ? 'bg-success' : 'bg-danger' }}">
                                        @if($e->user->active)
                                            <i class="fas fa-check-circle"></i><span class="d-none">Yes</span>
                                        @else
                                            <i class="fas fa-times-circle"></i><span class="d-none">No</span>
                                        @endif
                                    </td>

                                    @foreach($areas as $a)
                                        @if($e->ratings->first()->id == $a->id)
                                            <td class="text-center bg-success text-white">
                                                <i class="fas fa-check-circle"></i><span class="d-none">Approved</span>
                                            </td>
                                        @else
                                            <td></td>
                                        @endif
                                    @endforeach
                                    
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