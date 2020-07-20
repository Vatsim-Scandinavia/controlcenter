@extends('layouts.app')

@section('title', 'New Exam Report')
@section('content')

    <div class="row">
        <div class="col-xl-5 col-lg-12 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-white">
                        New Examination Report for {{ $training->user->firstName }}'s
                        @foreach($training->ratings as $rating)
                            @if ($loop->last)
                                {{ $rating->name }}
                            @else
                                {{ $rating->name . " + " }}
                            @endif
                        @endforeach
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('training.examination.store', ['training' => $training->id]) }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="examination_date">Date</label>
                            <input id="examination_date" class="datepicker form-control" type="text" name="examination_date" required>
                        </div>

                        <div class="form-group">
                            <label for="position">Position</label>
                            <input
                                id="position"
                                class="form-control @error('position') is-invalid @enderror"
                                type="text"
                                name="position"
                                list="positions"
                                value="{{ old('position') }}"
                                required>

                            <datalist id="positions">
                                @foreach($positions as $position)
                                    <option value="{{ $position->callsign }}">{{ $position->name }}</option>
                                @endforeach
                            </datalist>

                            @error('position')
                                <span class="text-danger">{{ $errors->first('position') }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="result">Result</label>
                            <select class="form-control" name="result" id="result">
                                <option value="FAILED">Failed</option>
                                <option value="PASSED">Passed</option>
                                <option value="INCOMPLETE">Incomplete</option>
                                <option value="POSTPONED">Postponed</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="attachments">Attachments</label>
                            <div class="custom-file" id="attachments">
                                <input type="file" class="custom-file-input @error('file') is-invalid @enderror" id="customFile" name="file">
                                <label class="custom-file-label" for="customFile">Choose file</label>
                            </div>
                            @error('file')
                                <span class="text-danger">{{ $errors->first('file') }}</span>
                            @enderror
                        </div>

                        <button type="submit" id="training-submit-btn" class="btn btn-success">Publish examination report</button>
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
            $(".datepicker").flatpickr({ dateFormat: "F d, Y", defaultDate: new Date({!! json_encode(old('date')) !!}) });
            $(".starttimepicker").flatpickr({ enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, defaultDate: {!! json_encode(old('start_at')) !!} });
            $(".endtimepicker").flatpickr({ enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true, defaultDate: {!! json_encode(old('end_at')) !!} });
        } else {
            $(".datepicker").flatpickr({ dateFormat: "F d, Y" });
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
