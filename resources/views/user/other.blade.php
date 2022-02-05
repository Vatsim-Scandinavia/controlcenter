@extends('layouts.app')

@section('title', 'Other Users')
@section('content')

<div class="row">

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">Registered Non-VAT{{ Config::get('app.owner_short') }} Users</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-page-size="25"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true">
                        <thead class="thead-light">
                            <tr>
                                <th data-field="id" data-sortable="true" data-filter-control="input" data-visible-search="true">Vatsim ID</th>
                                <th data-field="firstname" data-sortable="true" data-filter-control="input">First Name</th>
                                <th data-field="lastname" data-sortable="true" data-filter-control="input">Last Name</th>
                                <th data-field="rating" data-sortable="true" data-filter-control="select" data-filter-strict-search="true">ATC Rating</th>                                
                                <th data-field="division" data-sortable="true" data-filter-control="select">Division</th>
                                <th data-field="subdivision" data-sortable="true" data-filter-control="select">Subdivision</th>
                                <th>Last login</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                @if($user->subdivision != Config::get('app.owner_short') && $user->visiting_controller == false)
                                    <tr>
                                        <td><a href="{{ route('user.show', $user->id) }}">{{ $user->id }}</a></td>
                                        <td>{{ $user->first_name }}</td>
                                        <td>{{ $user->last_name }}</td>
                                        <td>{{ $user->rating_short }}</td>
                                        <td>{{ $user->division }}</td>
                                        <td>{{ $user->subdivision }}</td>
                                        <td>{{ Carbon\Carbon::make($user->last_login)->diffForHumans(['parts' => 2]) }}</td>
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