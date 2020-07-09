@extends('layouts.app')

@section('title', 'Member overview')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Member overview</h1>

<div class="row">

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">VATSCA Members</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>Vatsim ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>ATC Rating</th>
                                <th>Country</th>
                                <th>Last login</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                @if($user->subdivision == "SCA")
                                    <tr>
                                        <td><a href="{{ route('user.show', $user->id) }}">{{ $user->id }}</a></td>
                                        <td>{{ $user->first_name }}</td>
                                        <td>{{ $user->last_name }}</td>
                                        <td>{{ $user->rating_short }}</td>
                                        <td>{{ $user->country }}</td>
                                        <td>{{ $user->last_login }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">Registered Non-VATSCA Users</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>Vatsim ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>ATC Rating</th>
                                <th>Visiting Controller</th>
                                <th>Division</th>
                                <th>Subdivision</th>
                                <th>Country</th>
                                <th>Last login</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                @if($user->subdivision != "SCA")
                                    <tr>
                                        <td><a href="{{ route('user.show', $user->id) }}">{{ $user->id }}</a></td>
                                        <td>{{ $user->first_name }}</td>
                                        <td>{{ $user->last_name }}</td>
                                        <td>{{ $user->rating_short }}</td>
                                        <td><i class="fas fa-{{ $user->visiting_controller ? 'check' : 'times' }}"></i></td>
                                        <td>{{ $user->division }}</td>
                                        <td>{{ $user->subdivision }}</td>
                                        <td>{{ $user->country }}</td>
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