@extends('layouts.app')

@section('title', 'New Exam Report')
@section('content')

    <div class="row">
        <div class="col-xl-5 col-lg-12 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">
                        {{ $training->user->name }}
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                            <thead class="table-light">
                                <tr>
                                    <th>Vatsim ID</th>
                                    <th>Current Rating</th>
                                    <th>Training Rating</th>
                                    <th>Subdivision</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $training->user->id }}</td>
                                    <td>{{ $training->user->rating_short }}</td>
                                    <td>
                                        @foreach($training->ratings as $rating)
                                            @if ($loop->last)
                                                {{ $rating->name }}
                                            @else
                                                {{ $rating->name . " + " }}
                                            @endif
                                        @endforeach
                                    </td>
                                    <td>{{ $training->user->subdivision }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">
                        New Examination Report
                    </h6>
                    @if(Setting::get('trainingExamTemplate') != "")
                        <a class="btn btn-sm btn-light" href="{{ Setting::get('trainingExamTemplate') }}" target="_blank"><i class="fas fa-download"></i>&nbsp;Download Exam Template</a>
                    @endif
                </div>
                <div class="card-body">
                    <form action="{{ route('training.examination.store', ['training' => $training->id]) }}" method="POST" enctype="multipart/form-data">
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

                        <div class="form-group">
                            <label for="result">Result</label>
                            <select class="form-control" name="result" id="result" required>
                                <option disabled selected>Choose a result</option>
                                <option value="FAILED">Failed</option>
                                <option value="PASSED">Passed</option>
                                <option value="INCOMPLETE">Incomplete</option>
                                <option value="POSTPONED">Postponed</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="attachments">Attachments</label>
                            <div>
                                <input type="file" name="files[]" id="add-file" class="@error('file') is-invalid @enderror" accept=".pdf" multiple>
                            </div>
                            @error('files')
                                <span class="text-danger">{{ $errors->first('files') }}</span>
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

        var defaultDate = "{{ old('date') }}"
        $(".datepicker").flatpickr({ disableMobile: true, minDate: "{!! date('Y-m-d', strtotime('-1 months')) !!}", maxDate: "{!! date('Y-m-d') !!}", dateFormat: "d/m/Y", defaultDate: defaultDate, locale: {firstDayOfWeek: 1 } });

        $('.flatpickr-input:visible').on('focus', function () {
            $(this).blur();
        });
        $('.flatpickr-input:visible').prop('readonly', false);
    })
</script>
@endsection
