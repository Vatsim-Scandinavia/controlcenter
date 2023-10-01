@extends('layouts.app')

@section('title', 'My Students')

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">My Students</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-page-size="15"
                        data-page-list=[10,15,25,50]
                        data-sort-reset="true">
                        <thead class="table-light">
                            <tr>
                                <th data-field="state" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">State</th>
                                <th data-field="id" data-sortable="true" data-filter-control="input" data-visible-search="true">Vatsim ID</th>
                                <th data-field="name" data-sortable="true" data-filter-control="input">Name</th>
                                <th data-field="level" data-sortable="true" data-filter-control="select" data-filter-strict-search="true">Level</th>
                                <th data-field="type" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">Type</th>
                                <th data-field="period" data-sortable="true" data-filter-control="input">Period</th>
                                <th data-field="lastreport" data-sortable="true" data-filter-control="input">Last Report</th>
                                <th data-field="area" data-sortable="true" data-filter-control="select">Area</th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach($trainings as $training)
                            <tr>
                                <td>
                                    <i class="{{ $statuses[$training->status]["icon"] }} text-{{ $statuses[$training->status]["color"] }}"></i>&ensp;<a href="/training/{{ $training->id }}">{{ $statuses[$training->status]["text"] }}</a>{{ isset($training->paused_at) ? ' (PAUSED)' : '' }}
                                </td>
                                @if ($user->isModeratorOrAbove())                            
                                    <td><a href="{{ route('user.show', $training->user->id) }}">{{ $training->user->id }}</a></td>
                                    <td><a href="{{ route('user.show', $training->user->id) }}">{{ $training->user->name }}</a></td>
                                @else 
                                    <td>{{ $training->user->id }}</td>
                                    <td>{{ $training->user->name }}</td>
                                @endif
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
                                <td>
                                    @if($training->reports->count() > 0)
                                        @php
                                            $reportDate = Carbon\Carbon::make($training->reports->last()->report_date);
                                            $trainingIntervalExceeded = $reportDate->diffInDays() >= Setting::get('trainingInterval');
                                        @endphp

                                        <span title="{{ $reportDate->toEuropeanDate() }}">
                                            @if($reportDate->isToday())
                                            <span class="{{ ($trainingIntervalExceeded && $training->status != \App\Helpers\TrainingStatus::AWAITING_EXAM->value && !$training->paused_at) ? 'text-danger' : '' }}">Today</span>
                                            @elseif($reportDate->isYesterday())
                                            <span class="{{ ($trainingIntervalExceeded && $training->status != \App\Helpers\TrainingStatus::AWAITING_EXAM->value && !$training->paused_at) ? 'text-danger' : '' }}">Yesterday</span>
                                            @elseif($reportDate->diffInDays() <= 7)
                                            <span class="{{ ($trainingIntervalExceeded && $training->status != \App\Helpers\TrainingStatus::AWAITING_EXAM->value && !$training->paused_at) ? 'text-danger' : '' }}">{{ $reportDate->diffForHumans(['parts' => 1]) }}</span>
                                            @else
                                            <span class="{{ ($trainingIntervalExceeded && $training->status != \App\Helpers\TrainingStatus::AWAITING_EXAM->value && !$training->paused_at) ? 'text-danger' : '' }}">{{ $reportDate->diffForHumans(['parts' => 2]) }}</span>
                                            @endif
                                            
                                        </span>

                                    @else
                                        No report yet
                                    @endif
                                </td>
                                <td>{{ $training->area->name }}</td>
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