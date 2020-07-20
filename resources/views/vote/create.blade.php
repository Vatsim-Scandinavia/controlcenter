@extends('layouts.app')

@section('title', 'Create Vote')
@section('content')

<div class="row">
    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Vote 
                </h6> 
            </div>
            <div class="card-body">
                <form action="{!! action('VoteController@store') !!}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="date">End Date</label>
                        <input id="date" class="datepicker form-control" type="text" name="expire_date" value="{{ old('expire_date') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="end_at">End Time (Zulu)</label>
                        <input id="end_at" class="endtimepicker form-control" type="text" name="expire_time" value="{{ old('expire_time') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="question">Question</label>
                        <input id="question" class="form-control" type="text" name="question" value="{{ old('question') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="vote_alternatives">Answer Options</label>
                        <textarea class="form-control" id="vote_alternatives" rows="8" placeholder="Write options here, separated by new line" name="vote_options">{{ old('vote_options') }}</textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input @error('require_active') is-invalid @enderror" type="checkbox" id="check1" name="require_active" {{ old('require_active') ? "checked" : "" }}>
                        <label class="form-check-label" for="check1">
                            Only ATC active members can vote
                        </label>
                    </div>

                    <br>

                    <button type="submit" class="btn btn-primary">Create</button>

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