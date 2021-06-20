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
                <form action="{!! action('VatbookController@update') !!}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input id="date" class="datepicker form-control" type="text" name="date" value="{{ Carbon\Carbon::parse($booking->time_start)->format('d/m/Y') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="start_at">Start (Zulu)</label>
                        <input id="start_at" class="form-control @error('start_at') is-invalid @enderror" type="time" name="start_at" placeholder="12:00" value="{{ empty(old('start_at')) ? \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $booking->time_start)->format('H:i') : old('start_at') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="end_at">End (Zulu)</label>
                        <input id="end_at" class="form-control @error('end_at') is-invalid @enderror" type="time" name="end_at" placeholder="12:00" value="{{ empty(old('end_at')) ? \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $booking->time_end)->format('H:i') : old('end_at') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="position">Position</label>
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

                    @can('bookTags', \App\Models\Vatbook::class)
                        <div class="form-group">
                            @can('bookTrainingTag', \App\Models\Vatbook::class)
                                <input id="training" type="checkbox" name="tag" value=1 {{ $booking->training == 1 ? 'checked' : '' }} onClick="change(this)">
                                <label for="training">Training</label>
                                &nbsp;&nbsp;&nbsp;
                            @endcan

                            @can('bookExamTag', \App\Models\Vatbook::class)
                                <input id="exam" type="checkbox" name="tag" value=2 {{ $booking->exam == 1 ? 'checked' : '' }} onClick="change(this)">
                                <label for="exam">Exam</label>
                                &nbsp;&nbsp;&nbsp;
                            @endcan

                            @can('bookEventTag', \App\Models\Vatbook::class)
                                <input id="event" type="checkbox" name="tag" value=3 {{ $booking->event == 1 ? 'checked' : '' }} onClick="change(this)">
                                <label for="event">Event</label>
                            @endcan
                        </div>
                    @endcan

                    <div class="form-group">
                        <label for="user">User</label>
                        <input id="user" class="form-control" type="text" name="user" readonly="readonly" value="{{ $booking->user->name }} ({{ $booking->user->id }})">
                    </div>

                    <input type="hidden" name="id" value="{{{ $booking->id }}}"> 

                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('vatbook.delete', $booking->id) }}" onclick="return confirm('Are you sure you want to delete this booking?')" class="btn btn-danger">Delete</a>
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
        let checkboxes = document.querySelectorAll('input[type=checkbox]'); 
        let checked = [].filter.call(checkboxes, el => el.checked);
        checked.forEach(checkbox => change(checkbox));

        $('div').tooltip();

        var defaultDate = "{{ empty(old('date')) ? \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $booking->time_start)->format('d/m/Y') : old('date') }}"

        $(".datepicker").flatpickr({ disableMobile: true, minDate: "{!! date('Y-m-d') !!}", dateFormat: "d/m/Y", defaultDate: defaultDate, locale: {firstDayOfWeek: 1 } });
        
        $('.flatpickr-input:visible').on('focus', function () {
            $(this).blur();
        });
        $('.flatpickr-input:visible').prop('readonly', false);
    })

    change = (type) => {
        let name = document.getElementsByName(type.name);
        let checked = document.getElementById(type.id);

        if (checked.checked) {
            for(let i = 0; i < name.length; i++) {
                if(!name[i].checked) {
                    name[i].disabled = true;
                } else {
                    name[i].disabled = false;
                }
            }
        } else {
            for(let i = 0; i < name.length; i++) {
                name[i].disabled = false;
            }
        }
    }
</script>
@endsection