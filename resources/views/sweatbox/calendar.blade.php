@extends('layouts.app')

@section('title', 'Sweatbox Calendar')
@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">
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
                                <th data-sortable="true" data-filter-control="select">Mentor</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bookings as $booking)
                            <tr>
                                <td> 
                                    <span style="display: none">{{ date('Y-m-d', strtotime($booking->date)) }}</span>
                                    @if ($booking->mentor == $user->id || $user->isModerator())
                                        <a href="/sweatbox/{{ $booking->id }}">{{ date('F d, Y', strtotime($booking->date)) }}</a>
                                    @else
                                        {{ date('F d, Y', strtotime($booking->date)) }}
                                    @endif
                                </td>
                                <td>
                                    {{ date('H:i', strtotime($booking->start_at)) }}z
                                </td>
                                <td>
                                    {{ date('H:i', strtotime($booking->end_at)) }}z
                                </td>
                                <td>
                                    {{ $booking->position->callsign }} ({{ $booking->position->name }})
                                </td>
                                <td>
                                    {{ $booking->position->fir }}
                                </td>
                                <td>
                                    {{ $booking->user->name }} ({{ $booking->user->id }})
                                </td>
                                <td>
                                    {{ mb_strimwidth($booking->mentor_notes, 0, 40) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
        <div class="align-items-left">
            <a href="{{ route('sweatbox.create') }}" class="btn btn-success">Add Booking</a>
        </div>
    </div>
    
</div>

@endsection

@section('js')
<script>
    //Activate bootstrap tooltips
    $(document).ready(function() {
        $('div').tooltip();
    })
</script>
@endsection