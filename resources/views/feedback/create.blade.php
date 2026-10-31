@extends('layouts.app')

@section('title', 'Feedback')
@section('content')

<div class="row">
    <div class="col-xl-6 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Submit Feedback
                </h6> 
            </div>
            <div class="card-body">
                <form action="{!! action('FeedbackController@store') !!}" method="POST">
                    @csrf

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label">VATSIM ID</label>
                            <input class="form-control" type="text" value="{{ Auth::user()->id }}" disabled>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Your name</label>
                            <input class="form-control" type="text" value="{{ Auth::user()->name }}" disabled>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Your email</label>
                            <input class="form-control" type="text" value="{{ Auth::user()->email }}" disabled>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="controllers">Controller <small class="form-text"> (Optional)</small></label>
                            <input 
                                id="controllers"
                                class="form-control"
                                type="text"
                                name="controller"
                                list="controllersList"
                                multiple="multiple"
                                value="{{ old('controller') }}"
                                >

                            <datalist id="controllersList">
                                @foreach($controllers as $controller)
                                    @browser('isFirefox')
                                        <option>{{ $controller->id }}</option>
                                    @else
                                        <option value="{{ $controller->id }}">{{ $controller->name }}</option>
                                    @endbrowser
                                @endforeach
                            </datalist>
                            
                            @error('controller')
                                <span class="text-danger">{{ $errors->first('controller') }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="positions">Controller's position <small class="form-text"> (Optional)</small></label>
                            <input 
                                id="positions"
                                class="form-control"
                                type="text"
                                name="position"
                                list="positionsList"
                                multiple="multiple"
                                value="{{ old('position') }}"
                                >

                            <datalist id="positionsList">
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
                    </div>

                    @if(! empty($flights))
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label class="form-label" for="recentFlight">Your recent flights <small class="form-text"> (Optional)</small></label>
                                <select id="recentFlight" class="form-select">
                                    <option value="">Pick a flight to fill in the fields below</option>
                                    @foreach($flights as $flight)
                                        <option value="{{ $flight['callsign'] }}" data-date="{{ $flight['date'] }}" data-departure="{{ $flight['departure'] }}" data-arrival="{{ $flight['arrival'] }}">
                                            {{ $flight['callsign'] }}@if($flight['departure'] || $flight['arrival']) &middot; {{ $flight['departure'] ?? '?' }} &rarr; {{ $flight['arrival'] ?? '?' }}@endif &middot; {{ \Carbon\Carbon::parse($flight['date'])->toEuropeanDate() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="pilotCallsign">Pilot callsign <small class="form-text"> (Optional)</small></label>
                            <input
                                id="pilotCallsign"
                                class="form-control"
                                type="text"
                                name="pilot_callsign"
                                maxlength="20"
                                value="{{ old('pilot_callsign') }}"
                                >
                            @error('pilot_callsign')
                                <span class="text-danger">{{ $errors->first('pilot_callsign') }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="eventDate">Date of event, UTC <small class="form-text"> (Optional)</small></label>
                            <input
                                id="eventDate"
                                class="form-control"
                                type="date"
                                name="event_date"
                                max="{{ now()->toDateString() }}"
                                value="{{ old('event_date') }}"
                                >
                            @error('event_date')
                                <span class="text-danger">{{ $errors->first('event_date') }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="departureAirport">Departure airport, ICAO <small class="form-text"> (Optional)</small></label>
                            <input
                                id="departureAirport"
                                class="form-control"
                                type="text"
                                name="departure_airport"
                                maxlength="4"
                                value="{{ old('departure_airport') }}"
                                >
                            @error('departure_airport')
                                <span class="text-danger">{{ $errors->first('departure_airport') }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="arrivalAirport">Arrival airport, ICAO <small class="form-text"> (Optional)</small></label>
                            <input
                                id="arrivalAirport"
                                class="form-control"
                                type="text"
                                name="arrival_airport"
                                maxlength="4"
                                value="{{ old('arrival_airport') }}"
                                >
                            @error('arrival_airport')
                                <span class="text-danger">{{ $errors->first('arrival_airport') }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Your feedback (English only)</label>
                            <textarea class="form-control" name="feedback" rows="5">{{ old('feedback') }}</textarea>
                            @error('feedback')
                                <span class="text-danger">{{ $errors->first('feedback') }}</span>
                            @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success" onclick="handleSubmit(event)">Submit Feedback
                        <div class="submit-spinner spinner-border spinner-border-sm" role="status" style="display: none;">&nbsp;</div>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
@vite('resources/js/vue.js')
<script>
    function handleSubmit(event) {
        event.preventDefault();
        document.querySelector('.submit-spinner').style.display = 'inherit';
        event.target.disabled = true;
        event.target.closest('form').submit();
    }

    document.getElementById('recentFlight')?.addEventListener('change', (event) => {
        const flight = event.target.selectedOptions[0];

        if (flight.value) {
            document.getElementById('pilotCallsign').value = flight.value;
            document.getElementById('eventDate').value = flight.dataset.date;
            document.getElementById('departureAirport').value = flight.dataset.departure;
            document.getElementById('arrivalAirport').value = flight.dataset.arrival;
        }
    });
</script>
@endsection