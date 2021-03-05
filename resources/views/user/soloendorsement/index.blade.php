@extends('layouts.app')

@section('title', 'Solo Endorsements')
@section('content')

<div class="row">
    <div class="col-xl-6 col-md-12 mb-12">
        
        <div class="alert alert-info text-sm" style="font-size: 12px" role="alert">
            <i class="fas fa-info-circle"></i>&nbsp;All endorsements expire at 12:00z on the given day</a>.
        </div>

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">Active Endorsements</h6> 
            </div>        
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-toggle="table"
                        data-pagination="true"
                        data-strict-search="true"
                        data-filter-control="true">
                        <thead class="thead-light">
                            <tr>
                                <th data-field="student" data-sortable="true" data-filter-control="input">Student</th>
                                <th data-field="position" data-sortable="true" data-filter-control="select">Position</th>
                                <th data-field="starts" data-sorter="tableSortDates" data-filter-control="select">Starts</th>
                                <th data-field="expires" data-sorter="tableSortDates" data-filter-control="select">Expires</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($endorsements as $endorsement)
                            <tr>
                                <td>
                                    {{ $endorsement->user->name }}
                                </td>
                                <td>
                                    {{ $endorsement->position }}    
                                </td>
                                <td> 
                                    {{ $endorsement->created_at->toEuropeanDate() }}
                                </td>
                                <td> 
                                    {{ $endorsement->expires_at->toEuropeanDate() }}                                
                                </td>
                                <td>
                                    @if($user->isModeratorOrAbove())
                                        <a href="/users/endorsements/{{ $endorsement->id }}/delete"><i class="fas fa-times"></i> Remove</a>
                                    @else
                                        No access
                                    @endif
                                </td>
                            </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>

        @if($user->isModeratorOrAbove())
            <div class="align-items-left">
                <a href="{{ route('users.soloendorsements.create') }}" class="btn btn-success">Add Endorsement</a>
            </div>
        @endif
    </div>
    
</div>

@endsection