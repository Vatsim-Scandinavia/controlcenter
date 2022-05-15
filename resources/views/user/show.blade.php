@extends('layouts.app')

@section('title', 'User Details')
@section('content')

<div class="row">
    <div class="col-xl-3 col-md-4 col-sm-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Details
                </h6>
            </div>
            <div class="card-body">

                <dl class="copyable">
                    <dt>VATSIM ID</dt>
                    <dd>{{ $user->id }}<button type="button" onclick="navigator.clipboard.writeText('{{ $user->id }}')"><i class="fas fa-copy"></i></button></dd>

                    <dt>Name</dt>
                    <dd>{{ $user->first_name.' '.$user->last_name }}<button type="button" onclick="navigator.clipboard.writeText('{{ $user->first_name.' '.$user->last_name }}')"><i class="fas fa-copy"></i></button></dd>

                    <dt>Email</dt>
                    <dd class="separator pb-3">{{ $user->email }}<button type="button" onclick="navigator.clipboard.writeText('{{ $user->email }}')"><i class="fas fa-copy"></i></button></dd>

                    <dt class="pt-2">ATC Rating</dt>
                    <dd>{{ $user->rating_short }}</dd>

                    <dt>Sub/Division</dt>
                    <dd class="separator pb-3">{{ $user->subdivision }} / {{ $user->division }}</dd>

                    <dt class="pt-2">ATC Active</dt>
                    <dd><i class="fas fa-circle-{{ $user->active ? 'check' : 'xmark' }} text-{{ $user->active ? 'success' : 'danger' }}"></i></dd>

                    <dt>ATC Hours</dt>
                    <dd class="separator pb-3">{{ isset($userHours) ? $userHours : 'N/A' }}</dd>

                    <dt class="pt-2">Last login</dt>
                    <dd>{{ $user->last_login }}</dd>

                </dl>
            </div>
        </div>
    </div>

    <div class="col-xl-9 col-md-8 col-sm-12 mb-12">
        <div class="row">
            <div class="col-xl-8 col-lg-12 col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-white">
                            Trainings
                        </h6>
                        @can('create', \App\Models\Training::class)
                            <a href="{{ route('training.create.id', $user->id) }}" class="btn btn-icon btn-light" data-toggle="tooltip" data-placement="left" title="Add new training"><i class="fas fa-plus"></i></a>
                        @endcan
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

        <div class="col-xl-12 col-lg-12 col-md-12 p-0">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-white">
                        Endorsements
                    </h6>
                    @can('create', \App\Models\Endorsement::class)
                        <a href="{{ route('endorsements.create.id', $user->id) }}" class="btn btn-icon btn-light" data-toggle="tooltip" data-placement="left" title="Add new endorsement"><i class="fas fa-plus"></i></a>
                    @endcan
                </div>
                <div class="card-body d-flex flex-wrap gap-1">

                    @if($endorsements->count() == 0)
                        <p class="mb-0">No registered endrosements</p>
                    @endif

                    @foreach($endorsements as $endorsement)
                        <div class="card bg-light mb-3 endorsement-card" data-endorsement-id="{{ $endorsement['id'] }}">
                            <div class="card-header font-weight-bold">

                                @if($endorsement->revoked)
                                    <i class="fas fa-circle-xmark text-danger" data-toggle="tooltip" data-placement="top" title="Revoked"></i>
                                @elseif($endorsement->expired)
                                    <i class="fas fa-circle-minus text-danger" data-toggle="tooltip" data-placement="top" title="Expired"></i>
                                @else
                                    <i class="fas fa-circle-check text-success" data-toggle="tooltip" data-placement="top" title="Active"></i>
                                @endif

                                {{ ($endorsement->type == "MASC") ? 'MA/SC' : ucfirst(strtolower($endorsement->type)) }} Endorsement

                                @can('delete', [\App\Models\Endorsement::class, \App\Models\Endorsement::find($endorsement['id'])])
                                    <a href="/endorsements/{{ $endorsement['id'] }}/delete" class="text-muted float-right hover-red" onclick="return confirm('Are you sure you want to revoke this endorsement?')"><i class="fas fa-trash"></i></a>
                                @endcan
                            </div>
                            <div class="card-body">
                                <table class="table-card">
                                    @if($endorsement->type == "MASC")
                                        <tr class="spacing">
                                            <th>Position</th>
                                            <td>{{ $endorsement->ratings->first()->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Issued</th>
                                            <td>{{ Carbon\Carbon::create($endorsement->valid_from)->toEuropeanDate() }}</td>
                                        </tr>
                                        <tr class="spacing">
                                            <th>Expire</th>
                                            <td>{{ isset($endorsement->valid_to) ? Carbon\Carbon::create($endorsement->valid_to)->toEuropeanDateTime() : 'Never' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Issued by</th>
                                            <td>{{ isset($endorsement->issued_by) ? \App\Models\User::find($endorsement->issued_by)->name : 'System' }}</td>
                                        </tr>
                                        @if($endorsement->revoked)
                                            <tr>
                                                <th>Revoked by</th>
                                                <td>{{ isset($endorsement->revoked_by) ? \App\Models\User::find($endorsement->revoked_by)->name : 'System' }}</td>
                                            </tr>
                                        @endif                    
                                    @elseif($endorsement->type == "S1" || $endorsement->type == "SOLO")
                                        <tr class="spacing">
                                            <th>Rating</th>
                                            <td>{{ implode(', ', $endorsement->positions->pluck('callsign')->toArray()) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Issued</th>
                                            <td>{{ Carbon\Carbon::create($endorsement->valid_from)->toEuropeanDate() }}</td>
                                        </tr>
                                        <tr class="spacing">
                                            <th>Expire</th>
                                            <td>{{ isset($endorsement->valid_to) ? Carbon\Carbon::create($endorsement->valid_to)->toEuropeanDateTime() : 'Never' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Issued by</th>
                                            <td>{{ isset($endorsement->issued_by) ? \App\Models\User::find($endorsement->issued_by)->name : 'System' }}</td>
                                        </tr>
                                        @if($endorsement->revoked)
                                            <tr>
                                                <th>Revoked by</th>
                                                <td>{{ isset($endorsement->revoked_by) ? \App\Models\User::find($endorsement->revoked_by)->name : 'System' }}</td>
                                            </tr>
                                        @endif
                                    @elseif($endorsement->type == "VISITING")
                                        <tr>
                                            <th>Rating</th>
                                            <td>{{ $endorsement->ratings->first()->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Areas</th>
                                            <td>{{ implode(', ', $endorsement->areas->pluck('name')->toArray()) }}</td>
                                        </tr>
                                        <tr class="spacing">
                                            <th>Extras</th>
                                            <td>{{ implode(', ', $endorsement->ratings->pluck('name')->toArray()) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Issued</th>
                                            <td>{{ Carbon\Carbon::create($endorsement->valid_from)->toEuropeanDate() }}</td>
                                        </tr>
                                        <tr class="spacing">
                                            <th>Expire</th>
                                            <td>{{ isset($endorsement->valid_to) ? Carbon\Carbon::create($endorsement->valid_to)->toEuropeanDateTime() : 'Never' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Issued by</th>
                                            <td>{{ isset($endorsement->issued_by) ? \App\Models\User::find($endorsement->issued_by)->name : 'System' }}</td>
                                        </tr>
                                        @if($endorsement->revoked)
                                            <tr>
                                                <th>Revoked by</th>
                                                <td>{{ isset($endorsement->revoked_by) ? \App\Models\User::find($endorsement->revoked_by)->name : 'System' }}</td>
                                            </tr>
                                        @endif
                                    @elseif($endorsement->type == "EXAMINER")
                                        <tr>
                                            <th>Examining</th>
                                            <td>{{ $endorsement->ratings->first()->name }}</td>
                                        </tr>
                                        <tr class="spacing">
                                            <th>Areas</th>
                                            <td>{{ implode(', ', $endorsement->areas->pluck('name')->toArray()) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Issued</th>
                                            <td>{{ Carbon\Carbon::create($endorsement->valid_from)->toEuropeanDate() }}</td>
                                        </tr>
                                        <tr class="spacing">
                                            <th>Expire</th>
                                            <td>{{ isset($endorsement->valid_to) ? Carbon\Carbon::create($endorsement->valid_to)->toEuropeanDateTime() : 'Never' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Issued by</th>
                                            <td>{{ isset($endorsement->issued_by) ? \App\Models\User::find($endorsement->issued_by)->name : 'System' }}</td>
                                        </tr>
                                        @if($endorsement->revoked)
                                            <tr>
                                                <th>Revoked by</th>
                                                <td>{{ isset($endorsement->revoked_by) ? \App\Models\User::find($endorsement->revoked_by)->name : 'System' }}</td>
                                            </tr>
                                        @endif
                                    @endif
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @if (\Illuminate\Support\Facades\Gate::inspect('viewAccess', $user)->allowed())
            <div class="col-xl-12 col-lg-12 col-md-12 mb-12 p-0">
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