@extends('layouts.app')

@section('title', 'Vatbook')
@section('content')

<div class="row">
    @can('create', \App\Models\Vatbook::class)
        <div class="col-xl-8 col-lg-12 col-md-12 mb-12">
    @endcan
    @cannot('create', \App\Models\Vatbook::class)
        <div class="col-lg-12 col-md-12 mb-12">
    @endcannot
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">Booked Sessions<span class="zulu-clock">{{ \Carbon\Carbon::now()->format('H:i\z') }}</span></h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-cookie="true"
                        data-cookie-id-table="vatbook"
                        data-cookie-expire="14d"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-page-size="25"
                        data-page-list=[10,15,25,50]>
                        <thead class="thead-light">
                            <tr>
                                <th data-field="date" data-sortable="true" data-sorter="tableSortDates" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-order-by="desc">Date</th>
                                <th data-field="start" data-sortable="true" data-filter-control="select">Start (Zulu)</th>
                                <th data-field="end" data-sortable="true" data-filter-control="select">End (Zulu)</th>
                                <th data-field="position" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripBadge">Position</th>
                                <th data-field="fir" data-sortable="true" data-filter-control="select">FIR</th>
                                <th data-field="user" data-sortable="true" data-filter-control="select">User</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bookings as $booking)
                            <tr>
                                <td>
                                    @can('update', $booking)
                                        <a href="/vatbook/{{ $booking->id }}">{{ \Carbon\Carbon::create($booking->time_start)->toEuropeanDate() }}   
                                           <i class="fas fa-pencil-alt w3-tiny" aria-hidden="true"></i></a>
                                    @endcan
                                    @cannot('update', $booking)
                                        {{ \Carbon\Carbon::create($booking->time_start)->toEuropeanDate() }}
                                    @endcannot
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
                                        <span class="badge badge-primary">Training</span>
                                    @elseif($booking->event)
                                        <span class="badge badge-success">Event</span>
                                    @elseif($booking->exam)
                                        <span class="badge badge-danger">Exam</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $booking->position->fir }}
                                </td>
                                <td>
                                    @if ($booking->local_id == null)
                                        {{ $booking->name }}
                                    @else
                                        {{ \App\Models\User::find($booking->user_id)->name }}
                                        ({{ $booking->user_id }})
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
    @can('create', \App\Models\Vatbook::class)
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

                    @can('bookTags', \App\Models\Vatbook::class)
                        <div class="form-group">
                            @can('bookTrainingTag', \App\Models\Vatbook::class)
                                <input id="training" type="checkbox" name="tag" value=1 onClick="change(this)">
                                <label for="training">Training</label>
                                &nbsp;&nbsp;&nbsp;
                            @endcan

                            @can('bookExamTag', \App\Models\Vatbook::class)
                                <input id="exam" type="checkbox" name="tag" value=2 onClick="change(this)">
                                <label for="exam">Exam</label>
                                &nbsp;&nbsp;&nbsp;
                            @endcan

                            @can('bookEventTag', \App\Models\Vatbook::class)
                                <input id="event" type="checkbox" name="tag" value=3 onClick="change(this)">
                                <label for="event">Event</label>
                            @endcan
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

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    //Activate bootstrap tooltips
    $(document).ready(function() {
        $('div').tooltip();

        var defaultDate = "{{ old('date') }}"
        $(".datepicker").flatpickr({ disableMobile: true, minDate: "{!! date('Y-m-d') !!}", dateFormat: "d/m/Y", defaultDate: defaultDate, locale: {firstDayOfWeek: 1 } });

        $('.flatpickr-input:visible').on('focus', function () {
            $(this).blur();
        });
        $('.flatpickr-input:visible').prop('readonly', false);

        // Zulu clock
        var currentdate = new Date(); 
        var datetime = ('0'+currentdate.getUTCHours()).substr(-2,2) + ":" + ('0'+currentdate.getUTCMinutes()).substr(-2,2);

        setInterval(function (){
            var currentdate = new Date(); 
            var datetime = ('0'+currentdate.getUTCHours()).substr(-2,2) + ":" + ('0'+currentdate.getUTCMinutes()).substr(-2,2);
            $('.zulu-clock').text(datetime + 'z');
        },1000);
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