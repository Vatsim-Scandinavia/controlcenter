@extends('layouts.app')

@section('title', 'Create Booking')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Create Booking</h1>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Booking 
                </h6> 
            </div>
            <div class="card-body">
                <form action="{!! action('VatbookController@store') !!}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input id="date" class="datepicker form-control" type="text" name="date" required>
                    </div>

                    <div class="form-group">
                        <label for="start_at">Start (Zulu)</label>
                        <input id="start_at" class="starttimepicker form-control" type="text" name="start_at" required>
                    </div>

                    <div class="form-group">
                        <label for="end_at">End (Zulu)</label>
                        <input id="end_at" class="endtimepicker form-control" type="text" name="end_at" required>
                    </div>

                    <div class="form-group">
                        <label for="position">Position</label>
                        <input id="position" class="form-control" type="text" name="position" list="positions" value="{{ old('position') }}" required/>
                        <datalist id="positions">
                            @foreach($positions as $position)
                                <option value="{{ $position->callsign }}">{{ $position->name }}</option>
                            @endforeach
                        </datalist>
                    </div>

                    @if ($user->isMentor())
                        <div class="form-group">
                            <input id="training" type="checkbox" name="training" value=1>
                            <label for="training">Training</label>
                        </div>
                    @endif

                    @if ($user->isModerator())
                        <div class="form-group">
                            <input id="event" type="checkbox" name="event" value=1>
                            <label for="event">Event</label>
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary">Save</button>

                </form>
            </div>
        </div>
    </div>

</div>

@endsection

@section('js')

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    //Activate bootstrap tooltips
    $(document).ready(function() {
        $('div').tooltip();
        if({!! json_encode(old('date')) !!}) { 
            $(".datepicker").flatpickr({ minDate: "{!! date('Y-m-d') !!}", dateFormat: "F d, Y", defaultDate: new Date({!! json_encode(old('date')) !!}) });
            $(".starttimepicker").flatpickr({ enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, defaultDate: {!! json_encode(old('start_at')) !!} });
            $(".endtimepicker").flatpickr({ enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, defaultDate: {!! json_encode(old('end_at')) !!} });
        } else {
            $(".datepicker").flatpickr({ minDate: "{!! date('Y-m-d') !!}", dateFormat: "F d, Y" });
            $(".starttimepicker").flatpickr({ enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true });
            $(".endtimepicker").flatpickr({ enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true });
        }
        $('.flatpickr-input:visible').on('focus', function () {
            $(this).blur();
        });
        $('.flatpickr-input:visible').prop('readonly', false);
    })
</script>
@endsection