@extends('layouts.app')

@section('title', 'Booking Bulk')
@section('content')

<div class="row">
    <div class="col-xl-4 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Create Bulk Bookings
                </h6> 
                <span class="m-0 fw-bold text-white zulu-clock">{{ \Carbon\Carbon::now()->format('H:i\z') }}</span>
            </div>
            <div class="card-body">
                <form action="{!! action('BookingController@storeBulk') !!}" method="POST">
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
                        <label class="form-label" for="positions">Positions <span class="text-muted">(comma-separated)</span></label>
                        <input 
                            id="positions"
                            class="form-control"
                            type="text"
                            name="positions"
                            list="positionsList"
                            multiple="multiple"
                            v-model="positions"
                            v-bind:class="{'is-invalid': (validationError && positions == null)}">

                        <datalist id="positionsList">
                            @foreach($positions as $position)
                                @browser('isFirefox')
                                    <option>{{ $position->callsign }}</option>
                                @else
                                    <option value="{{ $position->callsign }}">{{ $position->name }}</option>
                                @endbrowser
                            @endforeach
                        </datalist>
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

                            <input type="radio" class="btn-check" id="exam" name="tag" value="2">
                            <label class="btn btn-outline-danger" for="exam">
                                <i class="fa-solid fa-graduation-cap"></i>
                                Exam
                            </label>

                            <input type="radio" class="btn-check" id="event" name="tag" value="3">
                            <label class="btn btn-outline-success" for="event">
                                <i class="fa-solid fa-calendar-day"></i>
                                Event
                            </label>
                        </div>
                    </div>
                    @endcan

                    <button type="submit" class="btn btn-success">Add Bookings</button>

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
@include('scripts.multipledatalist')

@endsection