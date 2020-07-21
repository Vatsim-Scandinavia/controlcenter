@extends('layouts.app')

@section('title', 'Vatbook')
@section('content')

<div class="row">
    <div class="col-xl-8 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">Booked Sessions</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-toggle="table"
                        data-pagination="true"
                        data-strict-search="true"
                        data-filter-control="true"
                        data-page-size="15"
                        data-page-list=[10,15,25,50]>
                        <thead class="thead-light">
                            <tr>
                                <th data-sortable="true" data-filter-control="select">Date</th>
                                <th data-sortable="true" data-filter-control="select">Start (Zulu)</th>
                                <th data-sortable="true" data-filter-control="select">End (Zulu)</th>
                                <th data-sortable="true" data-filter-control="select">Position</th>
                                <th data-sortable="true" data-filter-control="select">FIR</th>
                                <th data-sortable="true" data-filter-control="select">User</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bookings as $booking)
                            <tr>
                                <td> 
                                    <span style="display: none">{{ date('Y-m-d', strtotime($booking->time_start)) }}</span>
                                    @if ($booking->local_id !== null && $booking->cid == $user->id || $user->isModerator() && $booking->local_id !== null)
                                        <a href="/vatbook/{{ $booking->id }}">{{ \Carbon\Carbon::create($booking->time_start)->toEuropeanDate() }}</a>
                                    @else
                                        {{ \Carbon\Carbon::create($booking->time_start)->toEuropeanDate() }}
                                    @endif
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::create($booking->time_start)->toEuropeanTime() }}
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::create($booking->time_end)->toEuropeanTime() }}
                                </td>
                                <td>
                                    {{ $booking->position->callsign }} ({{ $booking->position->name }})
                                </td>
                                <td>
                                    {{ $booking->position->fir }}
                                </td>
                                <td>
                                    {{ $booking->name }}
                                    @if ($booking->cid > 0)
                                        ({{ $booking->cid }})
                                    @endif
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
                <h6 class="m-0 font-weight-bold text-white">
                    Create Booking 
                </h6> 
            </div>
            <div class="card-body">
                <form action="{!! action('VatbookController@store') !!}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input id="date" class="datepicker form-control @error('date') is-invalid @enderror" type="text" name="date" required>
                        @error('date')
                            <span class="text-danger">{{ $errors->first('date') }}</span>
                        @enderror
                    </div>
                    

                    <div class="form-group">
                        <label for="start_at">Start (Zulu)</label>
                        <input id="start_at" class="starttimepicker form-control @error('date') is-invalid @enderror" type="text" name="start_at" required>
                        @error('start_at')
                            <span class="text-danger">{{ $errors->first('start_at') }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="end_at">End (Zulu)</label>
                        <input id="end_at" class="endtimepicker form-control @error('date') is-invalid @enderror" type="text" name="end_at" required>
                        @error('end_at')
                            <span class="text-danger">{{ $errors->first('end_at') }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="position">Position</label>
                        <input id="position" class="form-control @error('position') is-invalid @enderror" type="text" name="position" list="positions" value="{{ old('position') }}" required/>
                        <datalist id="positions">
                            @foreach($positions as $position)
                                <option value="{{ $position->callsign }}">{{ $position->name }}</option>
                            @endforeach
                        </datalist>
                        @error('position')
                            <span class="text-danger">{{ $errors->first('position') }}</span>
                        @enderror
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

                    <button type="submit" class="btn btn-success">Add Booking</button>

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

        var defaultDate = "{{ old('date') }}"
        var startTime = "{{ old('start_at') }}"
        var endTime = "{{ old('end_at') }}"

        $(".datepicker").flatpickr({ minDate: "{!! date('Y-m-d') !!}", dateFormat: "d/m/Y", defaultDate: defaultDate });
        $(".starttimepicker").flatpickr({ enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, defaultDate: startTime});
        $(".endtimepicker").flatpickr({ enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, defaultDate: endTime });

        $('.flatpickr-input:visible').on('focus', function () {
            $(this).blur();
        });
        $('.flatpickr-input:visible').prop('readonly', false);
    })
</script>
@endsection