@extends('layouts.app')

@section('title', 'Edit Booking')
@section('content')

<div class="row">
    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Booking
                </h6>
            </div>
            <div class="card-body">
                <form action="{!! action('BookingController@update') !!}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="date">Date</label>
                        <input id="date" class="datepicker form-control" type="text" name="date" value="{{ Carbon\Carbon::parse($booking->time_start)->format('d/m/Y') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="start_at">Start (Zulu)</label>
                        <input id="start_at" class="form-control @error('start_at') is-invalid @enderror" type="time" name="start_at" placeholder="12:00" value="{{ empty(old('start_at')) ? \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $booking->time_start)->format('H:i') : old('start_at') }}" required>
                    </div>

                    <div class="mb-3">
                        <labe class="form-label"l for="end_at">End (Zulu)</label>
                        <input id="end_at" class="form-control @error('end_at') is-invalid @enderror" type="time" name="end_at" placeholder="12:00" value="{{ empty(old('end_at')) ? \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $booking->time_end)->format('H:i') : old('end_at') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="position">Position</label>
                    <input id="position" class="form-control" type="text" name="position" list="positions" value="{{ $booking->position->callsign }}" required/>
                        <datalist id="positions">
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
                            <div class="btn-group input-group-sm w-100" role="group">
                                <input type="radio" class="btn-check" id="normal" name="tag"
                                    value="" checked>
                                <label class="btn btn-outline-secondary" for="normal">
                                    <i class="fa-solid fa-tower-broadcast"></i>
                                    Normal
                            </label>
                            @can('bookTrainingTag', \App\Models\Booking::class)
                                    <input type="radio" class="btn-check" id="training" name="tag" value="1" {{ $booking->training == 1 ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary" for="training">
                                        <i class="fa-solid fa-book-open"></i>
                                        Training
                                    </label>
                            @endcan

                            @can('bookExamTag', \App\Models\Booking::class)
                                <input type="radio" class="btn-check" id="exam" name="tag" value="2" {{ $booking->exam == 1 ? 'checked' : '' }}>
                                <label class="btn btn-outline-danger" for="exam">
                                    <i class="fa-solid fa-graduation-cap"></i>
                                    Exam
                                </label>
                            @endcan

                            @can('bookEventTag', \App\Models\Booking::class)
                                <input id="event" type="radio" class="btn-check" name="tag" value=3 {{ $booking->event == 1 ? 'checked' : '' }}>
                                <label class="btn btn-outline-success" for="event">
                                    <i class="fa-solid fa-calendar-day"></i>
                                    Event
                                </label>
                            @endcan
                    </div>
                @endcan

                    <div class="mb-3">
                        <label class="form-label" for="user">User</label>
                        <input id="user" class="form-control" type="text" name="user" readonly="readonly" value="{{ $booking->user->name }} ({{ $booking->user->id }})">
                    </div>

                    <input type="hidden" name="id" value="{{{ $booking->id }}}">

                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('booking.delete', $booking->id) }}" onclick="return confirm('Are you sure you want to delete this booking?')" class="btn btn-danger">Delete</a>
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
        var defaultDate = "{{ empty(old('date')) ? \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $booking->time_start)->format('d/m/Y') : old('date') }}"
        document.querySelector('.datepicker').flatpickr({ disableMobile: true, minDate: "{!! date('Y-m-d') !!}", dateFormat: "d/m/Y", defaultDate: defaultDate, locale: {firstDayOfWeek: 1 } });
    })
</script>

@endsection
