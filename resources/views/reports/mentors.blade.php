@extends('layouts.app')

@section('title', 'Mentor Report')

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Mentor Report
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-cookie="true"
                        data-cookie-id-table="mentors"
                        data-cookie-expire="90d"
                        data-page-size="25"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true">
                        <thead class="table-light">
                            <tr>
                                <th data-field="id" data-sortable="true" data-filter-control="input" data-visible-search="true">Mentor ID</th>
                                <th data-field="mentor" data-sortable="true" data-filter-control="input">Mentor</th>
                                <th data-field="level" data-sortable="true" data-filter-control="select" data-filter-strict-search="false">Area</th>
                                <th data-field="applied" data-sortable="false">Last training</th>
                                <th data-field="teaching" data-sortable="true" data-filter-control="input">Teaching</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mentors as $mentor)
                                <tr>
                                    <td><a href="{{ route('user.show', $mentor->id) }}">{{ $mentor->id }}</a></td>
                                    <td>{{ $mentor->name }}</td>
                                    <td>
                                        {{ $mentor->getInlineMentoringAreas() }}
                                    </td>
                                    <td>
                                        @if($mentor->trainingReports->count() > 0)
                                            @php
                                                $reportDate = Carbon\Carbon::make($mentor->trainingReports->sortBy('report_date')->last()->report_date);
                                            @endphp
                                            <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $reportDate->toEuropeanDate() }}">
                                                @if($reportDate->isToday())
                                                    Today
                                                @elseif($reportDate->isYesterday())
                                                    Yesterday
                                                @elseif($reportDate->diffInDays() <= 7)
                                                    {{ $reportDate->diffForHumans(['parts' => 1])}}
                                                @else
                                                    {{ $reportDate->diffForHumans(['parts' => 2])}}
                                                @endif                                            
                                            </span>
                                        @else
                                            No registered training yet
                                        @endif
                                    </td>
                                    
                                    <td class="table-link-newline">
                                        @foreach($mentor->teaches as $training)
                                            <div>

                                                <i class="{{ $statuses[$training->status]["icon"] }} text-{{  $statuses[$training->status]["color"] }}"></i>
                                                @isset($training->paused_at)
                                                    <i class="fas fa-pause"></i>
                                                @endisset
                
                                                <a href="{{ route('training.show', $training->id) }}">{{ $training->user->name }}</a> / Last training: 
                                                
                                                @if($training->reports->count() > 0)
                                                    @php
                                                        $reportDate = Carbon\Carbon::make($training->reports->sortBy('report_date')->last()->report_date);
                                                        $trainingIntervalExceeded = $reportDate->diffInDays() >= Setting::get('trainingInterval');
                                                    @endphp
                                                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $reportDate->toEuropeanDate() }}">
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
                                                    <span class="text-danger">No registered training yet</span>
                                                @endif
                                            </div>
                                        @endforeach
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
    @include('scripts.tooltips')
@endsection