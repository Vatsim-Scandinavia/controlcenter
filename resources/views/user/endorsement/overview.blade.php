@extends('layouts.app')

@section('title', 'Solo Endorsements')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Solo Endorsements</h1>

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
                                <th data-sortable="true" data-filter-control="input">Student</th>
                                <th data-sortable="true" data-filter-control="select">Position</th>
                                <th data-filter-control="select">Starts</th>
                                <th data-filter-control="select">Expires</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($endorsements as $endorsement)
                            <tr>
                                <td>
                                    @if($user->isModerator())
                                        <a href="/user/endorsement/edit/{{ $endorsement->user_id }}">{{ $endorsement->user->name }} ({{ $endorsement->user->id }})</a>
                                    @else
                                        {{ $endorsement->user->name }}
                                    @endif
                                </td>
                                <td>
                                    {{ $endorsement->position }}    
                                </td>
                                <td> 
                                    {{ $endorsement->created_at->toFormattedDateString() }}
                                </td>
                                <td> 
                                    {{ $endorsement->expires_at->toFormattedDateString() }}                                
                                </td>
                            </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>

        @if($user->isModerator())
            <div class="align-items-left">
                <a href="{{ route('users.endorsements.create') }}" class="btn btn-success">Add Endorsement</a>
            </div>
        @endif
    </div>
    
</div>

@endsection