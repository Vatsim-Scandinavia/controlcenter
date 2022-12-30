@extends('layouts.app')

@section('title', 'Training Requests')
@section('title-extension')
    @if (\Auth::user()->isModeratorOrAbove())
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
                        data-cookie="true"
                        data-cookie-id-table="trainings"
                        data-cookie-expire="90d"
                        data-page-size="100"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true">
                        <thead class="thead-light">
                            <tr>
                                <th data-field="state" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">State</th>
                                <th data-field="id" data-sortable="true" data-filter-control="input" data-visible-search="true">Vatsim ID</th>
                                <th data-field="name" data-sortable="true" data-filter-control="input">Name</th>
                                <th data-field="level" data-sortable="true" data-filter-control="select" data-filter-strict-search="false">Level</th>
                                <th data-field="type" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">Type</th>
                                <th data-field="atchours" data-sortable="true">ATC Hours</th>
                                <th data-field="period" data-sortable="true" data-filter-control="input">Period</th>
                                <th data-field="area" data-sortable="true" data-filter-control="select">Area</th>
                                <th data-field="applied" data-sortable="true" data-sorter="tableSortDates" data-filter-control="input">Applied</th>
                                <th data-field="mentor" data-sortable="true" data-filter-control="input">Mentor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($openTrainings as $training)
                            <tr>
                                <td>
                                    <i class="{{ $statuses[$training->status]["icon"] }} text-{{ $statuses[$training->status]["color"] }}"></i>&ensp;

                                    @if($training->activities->where('type', 'COMMENT')->count())

                                        @php
                                            $notes = "";
                                            foreach($training->activities->where('type', 'COMMENT') as $a){
                                                $notes .= $a->created_at->toEuropeanDate().': '.$a->comment.'&#013;';
                                            }
                                        @endphp

                                        <a 
                                            href="/training/{{ $training->id }}"
                                            class="link-tooltip" 
                                            data-toggle="tooltip" 
                                            data-html="true" 
                                            data-placement="right" 
                                            title="{{ str_replace(["\r\n", "\r", "\n"], '&#013;', $notes) }}"
                                            >
                                            {{ $statuses[$training->status]["text"] }}
                                        </a>
                                    @else
                                        <a href="/training/{{ $training->id }}">
                                            {{ $statuses[$training->status]["text"] }}
                                        </a>
                                    @endif
                                    
                                    {{ isset($training->paused_at) ? ' (PAUSED)' : '' }}
                                </td>
                                <td><a href="{{ route('user.show', $training->user->id) }}">{{ $training->user->id }}</a></td>
                                <td><a href="{{ route('user.show', $training->user->id) }}">{{ $training->user->name }}</a></td>
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
                                    {{ $training->user->atchours() ? round($training->user->atchours()) : "0" }}h
                                </td>
                                <td>
                                    @if ($training->started_at == null && $training->closed_at == null)
                                        Training not started
                                    @elseif ($training->closed_at == null)
                                        {{ $training->started_at->toEuropeanDate() }} -
                                    @elseif ($training->started_at != null)
                                        {{ $training->started_at->toEuropeanDate() }} - {{ $training->closed_at->toEuropeanDate() }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $training->area->name }}</td>
                                <td>{{ $training->created_at->toEuropeanDate() }}</td>
                                <td>

                                    {{ $training->getInlineMentors() }}

                                </td>
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
        $("body").tooltip({ selector: '[data-toggle=tooltip]', delay: {"show": 150, "hide": 0} });
    });
</script>
@endsection
