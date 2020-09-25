@extends('layouts.app')

@section('title', 'Create Booking')
@section('content')

<div class="row">
    

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

        $(".datepicker").flatpickr({ minDate: "{!! date('Y-m-d') !!}", dateFormat: "d/m/Y", defaultDate: defaultDate, locale: {firstDayOfWeek: 1 } });
        $(".starttimepicker").flatpickr({ enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, defaultDate: startTime});
        $(".endtimepicker").flatpickr({ enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, defaultDate: endTime });

        $('.flatpickr-input:visible').on('focus', function () {
            $(this).blur();
        });
        $('.flatpickr-input:visible').prop('readonly', false);
    })
</script>
@endsection