@extends('layouts.app')

@section('title', 'Training')
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

                    <div class="form-check">
                    @foreach($groups as $group)
                        <label class="form-check-label @error('access') is-invalid @enderror">
                            <input type="radio" class="form-check-input" name="access" value="{{ $group->id }}" {{ $user->group == $group->id ? "checked" : "" }}>{{ $group->name }}
                            <div class="text-muted">{{ $group->description }}</div>
                            <br>
                        </label>
                    @endforeach
                    <label class="form-check-label @error('access') is-invalid @enderror">
                        <input type="radio" class="form-check-input" name="access" value="0" {{ !$user->group ? "checked" : "" }}>None
                        <div class="text-muted">No specific access, usually a student.</div>
                        <br>
                    </label>
                    @error('access')
                        <span class="text-danger">{{ $errors->first('access') }}</span>
                    @enderror
                    </div>

                    <div class="form-group">
                        <label class="@error('countries') is-invalid @enderror" for="assignCountries">Mentoring countries: <span class="badge badge-dark">Ctrl/Cmd+Click</span> to select multiple</label>
                        <select multiple class="form-control" name="countries[]" id="assignCountries" size="5">
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ ($user->mentor_countries->contains($country->id)) ? "selected" : "" }}>{{ $country->name }}</option>
                            @endforeach
                        </select>
                        @error('countries')
                            <span class="text-danger">{{ $errors->first('countries') }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Save access</button>

                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Trainings
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th data-sortable="true" data-filter-control="select">State</th>
                                <th data-sortable="true" data-filter-control="select" data-filter-strict-search="true">Level</th>
                                <th data-sortable="true" data-filter-control="select">Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trainings as $training)
                            <tr>
                                <td>
                                    <i class="{{ $statuses[$training->status]["icon"] }} text-{{ $statuses[$training->status]["color"] }}"></i>&ensp;<a href="/training/{{ $training->id }}">{{ $statuses[$training->status]["text"] }}</a>
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
                                    <i class="{{ $types[$training->type]["icon"] }}"></i>&ensp;{{ $types[$training->type]["text"] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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
            <div class="card-body p-0">
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
            </div>
        </div>
    </div>

@endsection
