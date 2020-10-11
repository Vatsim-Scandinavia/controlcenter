@extends('layouts.app')

@section('title', 'Mentor Report')
@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Mentor Report
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-page-size="25"
                        data-toggle="table"
                        data-pagination="true"
                        data-strict-search="true"
                        data-filter-control="true">
                        <thead class="thead-light">
                            <tr>
                                <th data-field="id" data-sortable="true" data-filter-control="input" data-visible-search="true">Mentor ID</th>
                                <th data-field="mentor" data-sortable="true" data-filter-control="input">Mentor</th>
                                <th data-field="level" data-sortable="true" data-filter-control="select">FIR</th>
                                <th data-field="applied" data-sortable="true">Last training</th>
                                <th data-field="teaching" data-sortable="true" data-filter-control="input">Teaching</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mentors as $mentor)
                                <tr>
                                    <td><a href="{{ route('user.show', $mentor->id) }}">{{ $mentor->id }}</a></td>
                                    <td>{{ $mentor->name }}</td>
                                    <td>
                                        {{ $mentor->getInlineMentoringCountries() }}
                                    </td>
                                    <td>
                                        @if(\App\TrainingReport::where('written_by_id', $mentor->id)->count() > 0)
                                            {{ Carbon\Carbon::make(\App\TrainingReport::where('written_by_id', $mentor->id)->latest()->get()->first()->report_date)->diffForHumans(['parts' => 2])}}
                                        @else
                                            No registered training yet
                                        @endif
                                    </td>
                                    
                                    <td class="table-link-newline">
                                        @foreach($mentor->teaches as $training)
                                            <div><a href="{{ route('user.show', $training->user->id) }}">{{ $training->user->name }}</a> / Last training: 
                                                @if(\App\TrainingReport::where('written_by_id', $mentor->id)->count() > 0)
                                                    @if(\App\TrainingReport::where('written_by_id', $mentor->id)->where('training_id', $training->id)->latest()->get()->count() > 0)
                                                        {{ Carbon\Carbon::make(\App\TrainingReport::where('written_by_id', $mentor->id)->where('training_id', $training->id)->latest()->get()->first()->report_date)->diffForHumans(['parts' => 2])}}
                                                    @else
                                                        N/A
                                                    @endif
                                                @else
                                                    No registered training yet
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
