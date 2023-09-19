@extends('layouts.app')

@section('title', 'Booking')

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">
    @can('create', \App\Models\Booking::class)
        <div class="col-xl-8 col-lg-12 col-md-12 mb-12">
    @endcan
    @cannot('create', \App\Models\Booking::class)
        <div class="col-lg-12 col-md-12 mb-12">
    @endcannot
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Booked Sessions</h6>
                <span class="m-0 fw-bold text-white zulu-clock">{{ \Carbon\Carbon::now()->format('H:i\z') }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-cookie="true"
                        data-cookie-id-table="booking"
                        data-cookie-expire="90d"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-page-size="25"
                        data-page-list=[10,15,25,50]
                        data-sort-reset="true">
                        <thead class="table-light">
                            <tr>
                                <th data-field="date" data-sortable="true" data-sorter="tableSortDates" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false" data-filter-order-by="desc">Date</th>
                                <th data-field="start" data-sortable="true" data-filter-control="select">Start (Zulu)</th>
                                <th data-field="end" data-sortable="true" data-filter-control="select">End (Zulu)</th>
                                <th data-field="position" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripBadge" data-filter-strict-search="false">Position</th>
                                <th data-field="fir" data-sortable="true" data-filter-control="select">FIR</th>
                                <th data-field="user" data-sortable="true" data-filter-control="select">User</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                            @foreach($bookings as $booking)
                            <tr>
                                <td>
                                    @can('update', $booking)
                                        <a href="/booking/{{ $booking->id }}">{{ \Carbon\Carbon::create($booking->time_start)->toEuropeanDate(true) }}
                                           <i class="fas fa-pencil-alt w3-tiny" aria-hidden="true"></i></a>
                                    @else
                                        {{ \Carbon\Carbon::create($booking->time_start)->toEuropeanDate(true) }}
                                        @if(Auth::user()->id == $booking->user_id || $user->isModeratorOrAbove())

                                            @if($booking->source == "DISCORD")
                                                <i class="fab fa-discord text-primary" data-bs-toggle="tooltip" data-bs-placement="top" aria-hidden="true" title="{{ Gate::inspect('update', $booking, \App\Models\Booking::class)->message() }}"></i>
                                            @else
                                                <i class="fas fa-info-circle text-primary" data-bs-toggle="tooltip" data-bs-placement="top" aria-hidden="true" title="{{ Gate::inspect('update', $booking, \App\Models\Booking::class)->message() }}"></i>
                                            @endif

                                        @endif
                                    @endcan
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::create($booking->time_start)->toEuropeanTime() }}
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::create($booking->time_end)->toEuropeanTime() }}
                                </td>
                                <td>
                                    {{ $booking->position->callsign }} ({{ $booking->position->name }})
                                    @if($booking->training)
                                        <span class="badge bg-primary">Training</span>
                                    @elseif($booking->event)
                                        <span class="badge bg-success">Event</span>
                                    @elseif($booking->exam)
                                        <span class="badge bg-danger">Exam</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $booking->position->fir }}
                                </td>
                                <td>
                                    @can('view', $booking->user)
                                        <a href="{{ route('user.show', $booking->user_id) }}">{{ $booking->user->name }} ({{ $booking->user_id }})</a>
                                    @else
                                        {{ $booking->user->name }}
                                        ({{ $booking->user_id }})
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    @can('create', \App\Models\Booking::class)
    <div class="col-xl-4 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Create Booking
                </h6>
            </div>
            <div class="card-body">
                <form action="{!! action('BookingController@store') !!}" method="POST">
                    @csrf
                    <div class="mb-3 mb-3">
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

                    @can('bookTags', \App\Models\Booking::class)
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <div class="btn-group input-group-sm w-100" role="group">
                                <input type="radio" class="btn-check" id="normal" name="tag" value="" checked>
                                <label class="btn btn-outline-secondary" for="normal">
                                    <i class="fa-solid fa-tower-broadcast"></i>
                                    Normal
                                </label>

                                @can('bookTrainingTag', \App\Models\Booking::class)
                                    <input type="radio" class="btn-check" id="training" name="tag" value="1">
                                    <label class="btn btn-outline-primary" for="training">
                                        <i class="fa-solid fa-book-open"></i>
                                        Training
                                    </label>
                                @endcan

                                @can('bookExamTag', \App\Models\Booking::class)
                                    <input type="radio" class="btn-check" id="exam" name="tag" value="2">
                                    <label class="btn btn-outline-danger" for="exam">
                                        <i class="fa-solid fa-graduation-cap"></i>
                                        Exam
                                    </label>
                                @endcan

                                @can('bookEventTag', \App\Models\Booking::class)
                                    <input type="radio" class="btn-check" id="event" name="tag" value="3">
                                    <label class="btn btn-outline-success" for="event">
                                        <i class="fa-solid fa-calendar-day"></i>
                                        Event
                                    </label>
                                @endcan
                            </div>
                        </div>
                    @endcan

                    <button type="submit" class="btn btn-success">Add Booking</button>

                </form>
            </div>
        </div>
    </div>
    @endcan
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
    @include('scripts.tooltips')

@endsection
