@extends('layouts.app')

@section('title', 'User Details')
@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    {{ $user->name }}'s details
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>Vatsim ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>ATC Rating</th>
                                <th>Visiting Controller</th>
                                <th>Division</th>
                                <th>Subdivision</th>
                                <th>Country</th>
                                <th>ATC Active</th>
                                <th>Last login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><a href="{{ route('user.show', $user->id) }}">{{ $user->id }}</a></td>
                                <td>{{ $user->first_name }}</td>
                                <td>{{ $user->last_name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->rating_short }}</td>
                                <td><i class="fas fa-{{ $user->visiting_controller ? 'check' : 'times' }}"></i></td>
                                <td>{{ $user->division }}</td>
                                <td>{{ $user->subdivision }}</td>
                                <td>{{ $user->country }}</td>
                                <td><i class="fas fa-{{ $user->active ? 'check' : 'times' }}"></i></td>
                                <td>{{ $user->last_login }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    @can('update', $user)
    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Access
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('user.update', $user->id) }}" method="POST">
                    @method('PATCH')
                    @csrf

                    <p>Select none, one or multiple permissions for the user.</p>

                    <table class="table table-bordered table-hover table-responsive w-100 d-block d-md-table">
                        <thead>
                            <tr>
                                <th>Area</th>
                                @foreach($groups as $group)
                                    <th class="text-center">{{ $group->name }} <i class="fas fa-question-circle text-gray-400" title="{{ $group->description }}"></i></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>

                            @foreach($areas as $area)
                                <tr>
                                    <td>{{ $area->name }}</td>

                                    @foreach($groups as $group)
                                        @if($group->id == 1)
                                            <td class="text-center"><input type="checkbox" {{ $user->permissions->where('country_id', $area->id)->where('group_id', $group->id)->count() ? "checked" : "" }} disabled></td>
                                        @else
                                            <td class="text-center"><input type="checkbox" name="{{ $area->name }}_{{ $group->name }}" {{ $user->permissions->where('country_id', $area->id)->where('group_id', $group->id)->count() ? "checked" : "" }}></td>
                                        @endif
                                        
                                    @endforeach

                                </tr>
                            @endforeach

                        </tbody>
                    </table>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Save access</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
    @endcan

    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Trainings
                </h6>
            </div>
            <div class="card-body {{ $trainings->count() == 0 ? '' : 'p-0' }}">

                @if($trainings->count() == 0)
                    <p class="mb-0">No registered trainings</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th>State</th>
                                    <th>Level</th>
                                    <th>Country</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trainings as $training)
                                <tr>
                                    <td>
                                        <i class="{{ $statuses[$training->status]["icon"] }} text-{{ $statuses[$training->status]["color"] }}"></i>&ensp;<a href="/training/{{ $training->id }}">{{ $statuses[$training->status]["text"] }}</a>{{ isset($training->paused_at) ? ' (PAUSED)' : '' }}
                                    </td>
                                    <td>
                                        @if ( is_iterable($ratings = $training->ratings->toArray()) )
                                            @for( $i = 0; $i < sizeof($ratings); $i++ )
                                                @if ( $i == (sizeof($ratings) - 1) )
                                                    {{ $ratings[$i]["name"] }}
                                                @else
                                                    {{ $ratings[$i]["name"] . " + " }}
                                                @endif
                                            @endfor
                                        @else
                                            {{ $ratings["name"] }}
                                        @endif
                                    </td>
                                    <td>
                                        {{ $training->country->name }}
                                    </td>
                                    <td>
                                        <i class="{{ $types[$training->type]["icon"] }}"></i>&ensp;{{ $types[$training->type]["text"] }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Active Endorsements
                </h6>
            </div>
            <div class="card-body {{ $endorsements->count() == 0 ? '' : 'p-0' }}">

                @if($endorsements->count() == 0)
                    <p class="mb-0">No registered endrosements</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Endorsement</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($endorsements as $endorsement)
                                <tr>
                                    <td>
                                        {{ $endorsement->name }}
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


    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Mentoring
                </h6>
            </div>
            <div class="card-body {{ $user->teaches->count() == 0 ? '' : 'p-0' }}">

                @if($user->teaches->count() == 0)
                    <p class="mb-0">No registered mentors</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th data-sortable="true" data-filter-control="select">Teaches</th>
                                    <th data-sortable="true" data-filter-control="input">Expires</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->teaches as $training)
                                <tr>
                                    <td><a href="{{ route('user.show', $training->user->id) }}">{{ $training->user->name }}</a></td>
                                    <td>{{ Carbon\Carbon::parse($user->teaches->find($training->id)->pivot->expire_at)->toEuropeanDate() }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>

@endsection
