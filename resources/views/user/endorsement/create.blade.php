@extends('layouts.app')

@section('title', 'Create Solo Endorsement')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Create Solo Endorsement</h1>

<div class="row">
    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Booking 
                </h6> 
            </div>
            <div class="card-body">
                <form action="{!! action('SweatboxController@store') !!}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="student">Student</label>
                        <textarea class="form-control" id="student" rows="8" placeholder="Write booking notes here" name="student"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="date">Expires</label>
                        <input id="date" class="datepicker form-control" type="text" name="date">
                    </div>

                    <div class="form-group">
                        <label for="position">Position</label>
                        <input id="position" class="form-control" type="text" name="position">
                    </div>

                    <button type="submit" class="btn btn-success">Create</button>
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
        $(".datepicker").flatpickr({ dateFormat: "F d, Y" });
        $(".starttimepicker").flatpickr({ enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true });
        $(".endtimepicker").flatpickr({ enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true });
    })
</script>
@endsection