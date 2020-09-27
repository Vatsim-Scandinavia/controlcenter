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
                <form action="{!! action('SweatbookController@update') !!}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input id="date" class="datepicker form-control" type="text" name="date" required>
                    </div>

                    <div class="form-group">
                        <label for="start_at">Start (Zulu)</label>
                        <input id="start_at" class="form-control @error('start_at') is-invalid @enderror" type="time" name="start_at" placeholder="12:00" value="{{ old('start_at') }}" required>
                        @error('start_at')
                            <span class="text-danger">{{ $errors->first('start_at') }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="end_at">End (Zulu)</label>
                        <input id="end_at" class="form-control @error('end_at') is-invalid @enderror" type="time" name="end_at" placeholder="12:00" value="{{ old('end_at') }}" required>
                        @error('end_at')
                            <span class="text-danger">{{ $errors->first('end_at') }}</span>
                        @enderror
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
                    <a href="{{ route('sweatbook.delete', $booking->id) }}" onclick="return confirm('Are you sure you want to delete this booking?')" class="btn btn-danger">Delete</a>
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

        var defaultDate = "{{ empty(old('date')) ? \Carbon\Carbon::createFromFormat('Y-m-d', $booking->date)->format('d/m/Y') : old('date') }}"
        
        $(".datepicker").flatpickr({ disableMobile: true, minDate: "{!! date('Y-m-d') !!}", dateFormat: "d/m/Y", defaultDate: defaultDate, locale: {firstDayOfWeek: 1 }});

        $('.flatpickr-input:visible').on('focus', function () {
            $(this).blur();
        });
        $('.flatpickr-input:visible').prop('readonly', false);
    })
</script>
@endsection