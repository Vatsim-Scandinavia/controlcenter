@extends('layouts.app')

@section('title', 'Training Requests')
@section('title-extension')
    @if (\Auth::user()->isModerator())
        <a href="{{ route('training.create') }}" class="btn btn-sm btn-success">Add new request</a>
    @endif
@endsection
@section('content')

<div class="row">

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">Open training requests</h6>
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
                                <th data-field="state" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml">State</th>
                                <th data-field="id" data-sortable="true" data-filter-control="input" data-visible-search="true">Vatsim ID</th>
                                <th data-field="name" data-sortable="true" data-filter-control="input">Name</th>
                                <th data-field="level" data-sortable="true" data-filter-control="select" data-filter-strict-search="true">Level</th>
                                <th data-field="type" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml">Type</th>
                                <th data-field="period" data-sortable="true" data-filter-control="input">Period</th>
                                <th data-field="country" data-sortable="true" data-filter-control="select">Country</th>
                                <th data-field="applied" data-sortable="true" data-sorter="tableSortDates" data-filter-control="input">Applied</th>
                                <th data-field="mentor" data-sortable="true" data-filter-control="select">Mentor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($openTrainings as $training)
                            <tr>
                                <td>
                                    <i class="{{ $statuses[$training->status]["icon"] }} text-{{ $statuses[$training->status]["color"] }}"></i>&ensp;<a href="/training/{{ $training->id }}">{{ $statuses[$training->status]["text"] }}</a>{{ isset($training->paused_at) ? ' (PAUSED)' : '' }}
                                </td>
                                <td><a href="/user/{{ $training->user->id }}">{{ $training->user->id }}</a></td>
                                <td><a href="/user/{{ $training->user->id }}">{{ $training->user->name }}</a></td>
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
                                <td>
                                    @if ($training->started_at == null && $training->finished_at == null)
                                        Training not started
                                    @elseif ($training->finished_at == null)
                                        {{ $training->started_at->toEuropeanDate() }} -
                                    @else
                                        {{ $training->started_at->toEuropeanDate() }} - {{ $training->finished_at->toEuropeanDate() }}
                                    @endif
                                </td>
                                <td>{{ $training->country->name }}</td>
                                <td>{{ $training->created_at->toEuropeanDate() }}</td>
                                <td>

                                    @if ($training->mentors->count() == 0)
                                        -
                                    @elseif ($training->mentors->count() == 1)
                                        {{ $training->mentors->first()->name }}
                                    @elseif ($training->mentors->count() > 1)
                                        @foreach($training->mentors as $mentor)
                                            {{ $mentor->name }},
                                        @endforeach
                                    @endif

                                </td>
                            </tr>
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
                <h6 class="m-0 font-weight-bold text-white">Closed training requests</h6>
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
                                <th data-field="state" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml">State</th>
                                <th data-field="id" data-sortable="true" data-filter-control="input" data-visible-search="true">Vatsim ID</th>
                                <th data-field="name" data-sortable="true" data-filter-control="input">Name</th>
                                <th data-field="level" data-sortable="true" data-filter-control="select" data-filter-strict-search="true">Level</th>
                                <th data-field="type" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml">Type</th>
                                <th data-field="period" data-sortable="true" data-filter-control="input">Period</th>
                                <th data-field="country" data-sortable="true" data-filter-control="select">Country</th>
                                <th data-field="applied" data-sortable="true" data-filter-control="input">Applied</th>
                                <th data-field="closed" data-sortable="true" data-filter-control="input">Closed</th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach($closedTrainings as $training)
                            <tr>
                                <td>
                                    <i class="{{ $statuses[$training->status]["icon"] }} text-{{ $statuses[$training->status]["color"] }}"></i>&ensp;<a href="/training/{{ $training->id }}">{{ $statuses[$training->status]["text"] }}</a> {{ isset($training->paused_at) ? ' (PAUSED)' : '' }}
                                </td>
                                <td><a href="/user/{{ $training->user->id }}">{{ $training->user->id }}</a></td>
                                <td><a href="/user/{{ $training->user->id }}">{{ $endorsement->user->name }}</a></td>
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
                                <td>
                                    @if ($training->started_at == null && $training->finished_at == null)
                                        Training never started
                                    @elseif ($training->finished_at == null)
                                        {{ $training->started_at->toEuropeanDate() }} -
                                    @else
                                        {{ $training->started_at->toEuropeanDate() }} - {{ $training->finished_at->toEuropeanDate() }}
                                    @endif
                                </td>
                                <td>{{ $training->country->name }}</td>
                                <td>{{ $training->created_at->toEuropeanDate() }}</td>
                                <td>{{ $training->finished_at->toEuropeanDate() }}</td>
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

@section('js')
<script>
    //Activate bootstrap tooltips
    $(document).ready(function() {
        $('div').tooltip();
    })
</script>
@endsection
