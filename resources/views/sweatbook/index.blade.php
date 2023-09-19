@extends('layouts.app')

@section('title', 'Sweatbox Calendar')

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">
    <div class="col-xl-8 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Booked Sessions</h6> 
                <span class="m-0 fw-bold text-white zulu-clock">{{ \Carbon\Carbon::now()->format('H:i\z') }}</span>
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
                                <th data-field="date" data-sortable="true" data-sorter="tableSortDates" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">Date</th>
                                <th data-field="start" data-sortable="true" data-filter-control="select">Start (Zulu)</th>
                                <th data-field="end" data-sortable="true" data-filter-control="select">End (Zulu)</th>
                                <th data-field="position" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">Position</th>
                                <th data-field="fir" data-sortable="true" data-filter-control="select">FIR</th>
                                <th data-field="mentor" data-sortable="true" data-filter-control="select">Mentor</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bookings as $booking)
                            <tr>
                                <td>
                                    @can('update', $booking)
                                        <a href="/sweatbook/{{ $booking->id }}">{{ Carbon\Carbon::create($booking->date)->toEuropeanDate(true) }}
                                        &nbsp;&nbsp;<i class="fa fa-pencil-alt w3-tiny" aria-hidden="true"></i></a>
                                    @endcan
                                    @cannot('update', $booking)
                                        {{ Carbon\Carbon::create($booking->date)->toEuropeanDate(true) }}
                                    @endcannot
                                </td>
                                <td>
                                    {{ Carbon\Carbon::create($booking->start_at)->toEuropeanTime() }}
                                </td>
                                <td>
                                    {{ Carbon\Carbon::create($booking->end_at)->toEuropeanTime() }}
                                </td>
                                <td>
                                    {{ $booking->position->callsign }} ({{ $booking->position->name }})
                                </td>
                                <td>
                                    {{ $booking->position->fir }}
                                </td>
                                <td>
                                    {{ $booking->user->name }} ({{ $booking->user->id }})
                                </td>
                                <td>
                                    {{ mb_strimwidth($booking->mentor_notes, 0, 40) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>

    <div class="col-xl-4 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Create Booking 
                </h6> 
            </div>
            <div class="card-body">
                <form action="{!! action('SweatbookController@store') !!}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="date">Date</label>
                        <input id="date" class="datepicker form-control @error('date') is-invalid @enderror" type="text" name="date" required>
                        @error('date')
                            <span class="text-danger">{{ $errors->first('date') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="start_at">Start (Zulu)</label>
                        <input id="start_at" class="form-control @error('start_at') is-invalid @enderror" type="time" name="start_at" placeholder="12:00" value="{{ old('start_at') }}" required>
                        @error('start_at')
                            <span class="text-danger">{{ $errors->first('start_at') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="end_at">End (Zulu)</label>
                        <input id="end_at" class="form-control @error('end_at') is-invalid @enderror" type="time" name="end_at" placeholder="12:00" value="{{ old('end_at') }}" required>
                        @error('end_at')
                            <span class="text-danger">{{ $errors->first('end_at') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="position">Position</label>
                        <input id="position" class="form-control @error('position') is-invalid @enderror" type="text" name="position" list="positions" value="{{ old('position') }}" required/>
                        <datalist id="positions">
                            @foreach($positions as $position)
                                @browser('isFirefox')
                                    <option>{{ $position->callsign }}</option>
                                @else
                                    <option value="{{ $position->callsign }}">{{ $position->name }}</option>
                                @endbrowser
                            @endforeach
                        </datalist>
                        @error('position')
                            <span class="text-danger">{{ $errors->first('position') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="mentor_notes">Mentor notes</label>
                        <textarea class="form-control @error('mentor_notes') is-invalid @enderror" id="mentor_notes" rows="8" placeholder="Write booking notes here" name="mentor_notes">{{ old('mentor_notes') }}</textarea>
                        @error('mentor_notes')
                            <span class="text-danger">{{ $errors->first('mentor_notes') }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-success">Add Booking</button>

                </form>
            </div>
        </div>
    </div>
    
</div>

@endsection

@section('js')

<!-- Flatpickr -->
@vite(['resources/js/flatpickr.js', 'resources/sass/flatpickr.scss'])
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var defaultDate = "{{ old('date') }}"
        document.querySelector('.datepicker').flatpickr({ disableMobile: true, minDate: "{!! date('Y-m-d') !!}", dateFormat: "d/m/Y", defaultDate: defaultDate, locale: {firstDayOfWeek: 1 } });
    })
</script>

@include('scripts.zulutime')

@endsection