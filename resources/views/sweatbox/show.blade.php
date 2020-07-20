@extends('layouts.app')

@section('title', 'Edit Booking')
@section('content')

<div class="row">
    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Booking 
                </h6> 
            </div>
            <div class="card-body">
                <form action="{!! action('SweatboxController@update') !!}" method="POST">
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
                    <input id="position" class="form-control" type="text" name="position" list="positions" value="{{ $booking->position->callsign }}" required/>
                        <datalist id="positions">
                            @foreach($positions as $position)
                                <option value="{{ $position->callsign }}">{{ $position->name }}</option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label for="mentor_notes">Mentor notes</label>
                        @if (old('mentor_notes'))
                            <textarea class="form-control" id="mentor_notes" rows="8" placeholder="Write booking notes here" name="mentor_notes">{{ old('mentor_notes') }}</textarea>
                        @else
                            <textarea class="form-control" id="mentor_notes" rows="8" placeholder="Write booking notes here" name="mentor_notes">{{ $booking->mentor_notes }}</textarea>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="mentor">Mentor</label>
                        <input id="mentor" class="form-control" type="text" name="mentor" readonly="readonly" value="{{ $booking->user->name }} ({{ $booking->user->id }})">
                    </div>

                    <input type="hidden" name="id" value="{{{ $booking->id }}}"> 

                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('sweatbox.delete', $booking->id) }}" onclick="return confirm('Are you sure you want to delete this booking?')" class="btn btn-danger">Delete</a>
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
            $(".datepicker").flatpickr({ minDate: "{!! date('Y-m-d') !!}", dateFormat: "F d, Y", defaultDate: new Date({!! json_encode($booking->date) !!}) });
            $(".starttimepicker").flatpickr({ enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, defaultDate: {!! json_encode($booking->start_at) !!} });
            $(".endtimepicker").flatpickr({ enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, defaultDate: {!! json_encode($booking->end_at) !!} });
        }
        $('.flatpickr-input:visible').on('focus', function () {
            $(this).blur();
        });
        $('.flatpickr-input:visible').prop('readonly', false);
    })
</script>
@endsection