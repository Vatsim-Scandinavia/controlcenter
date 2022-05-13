@extends('layouts.app')

@section('title', 'User Details')
@section('title-extension')
    @can('create', \App\Models\Training::class)
        <a href="{{ route('training.create.id', $user->id) }}" class="btn btn-sm btn-success">Add training request</a>
    @endcan
    @can('create', \App\Models\Endorsement::class)
        <a href="{{ route('endorsements.create.id', $user->id) }}" class="btn btn-sm btn-primary">Add endorsement</a>
    @endcan
@endsection
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
                                <th>Division</th>
                                <th>Subdivision</th>
                                <th>ATC Active</th>
                                <th>ATC Hours</th>
                                <th>Last login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->first_name }}</td>
                                <td>{{ $user->last_name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->rating_short }}</td>
                                <td>{{ $user->division }}</td>
                                <td>{{ $user->subdivision }}</td>
                                <td><i class="fas fa-{{ $user->active ? 'check' : 'times' }}"></i></td>
                                <td>{{ isset($userHours) ? $userHours : 'N/A' }}</td>
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

    @if (\Illuminate\Support\Facades\Gate::inspect('viewAccess', $user)->allowed())
        <div class="col-xl-6 col-lg-12 col-md-12 mb-12">
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

                                            @if (\Illuminate\Support\Facades\Gate::inspect('updateGroup', [$user, $group, $area])->allowed() && $group->id != 1)
                                                <td class="text-center"><input type="checkbox" name="{{ $area->id }}_{{ $group->name }}" {{ $user->groups()->where('group_id', $group->id)->where('area_id', $area->id)->count() ? "checked" : "" }}></td>
                                            @else
                                                <td class="text-center"><input type="checkbox" {{ $user->groups()->where('group_id', $group->id)->where('area_id', $area->id)->count() ? "checked" : "" }} disabled></td>
                                            @endif
                                            
                                        @endforeach

                                    </tr>
                                @endforeach

                            </tbody>
                        </table>

                        @if (\Illuminate\Support\Facades\Gate::inspect('update', $user)->allowed())
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Save access</button>
                            </div>
                        @endif

                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="col-xl-6 col-lg-12 col-md-12 mb-12">
        <div class="col-xl-12 col-lg-12 col-md-12 p-0">
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
                                        <th>Area</th>
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
                                            {{ $training->area->name }}
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
        </div>

        <div class="row">
            <div class="col-xl-8 col-lg-12 col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-white">
                            Endorsements
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
                                            <th>Type</th>
                                            <th>Details</th>
                                            <th>Status</th>
                                            @if(\Auth::user()->isModeratorOrAbove())
                                                <th>Actions</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($endorsements as $endorsement)
                                        @php
                                            $tooltip = 'Valid from: '.$endorsement['from'].'<br>Valid to: '.$endorsement['to'].'<br>Issued by: '.$endorsement['issuedBy'].'<br>Revoked by: '.$endorsement['revokedBy'].'<br><br>Areas: '.$endorsement['areas'].'<br>Ratings: '.$endorsement['ratings'].'<br>Positions: '.$endorsement['positions'];
                                        @endphp
                                        <tr>
                                            <td>
                                                {{ $endorsement['type'] }}
                                            </td>
                                            <td>
                                                <span class="link-tooltip" 
                                                title="{!! $tooltip !!}" 
                                                data-toggle="tooltip" 
                                                data-html="true" 
                                                data-placement="right">
                                                    @if($endorsement['type'] == "MA/SC")
                                                        {{ $endorsement['ratings'] }}
                                                    @elseif($endorsement['type'] == "S1" || $endorsement['type'] == "SOLO")
                                                        {{ $endorsement['positions'] }}
                                                    @elseif($endorsement['type'] == "VISITING")
                                                        {{ $endorsement['areas'] }}
                                                    @elseif($endorsement['type'] == "EXAMINER")
                                                        {{ $endorsement['ratings'] }}
                                                    @endif
                                                </span>
                                            </td>
                                            <td>
                                                {{ $endorsement['status'] }}
                                            </td>
                                            @if(\Auth::user()->isModeratorOrAbove())
                                                <td>
                                                    <a href="/endorsements/{{ $endorsement['id'] }}/delete"><i class="fas fa-times"></i> Revoke</a>
                                                </td>
                                            @endif
                                            
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
        
                    </div>
                </div>
            </div>
    
            <div class="col-xl-4 col-lg-12 col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-white">
                            Mentoring
                        </h6>
                    </div>
                    <div class="card-body {{ $user->teaches->count() == 0 ? '' : 'p-0' }}">
        
                        @if($user->teaches->count() == 0)
                            <p class="mb-0">No registered students</p>
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
        </div>
    </div>
    
</div>
@endsection

@section('js')
<script>
    //Activate bootstrap tooltips
    $(document).ready(function() {
        $("body").tooltip({ selector: '[data-toggle=tooltip]', delay: {"show": 150, "hide": 0} });
    });
</script>
@endsection