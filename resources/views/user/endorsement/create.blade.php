@extends('layouts.app')

@section('title', 'Create Solo Endorsement')
@section('content')

<div class="row">
    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Create 
                </h6> 
            </div>
            <div class="card-body">
                <form action="{!! action('UserEndorsementController@store') !!}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="student">Student</label>
                        <input 
                            id="student"
                            class="form-control @error('student') is-invalid @enderror"
                            type="text"
                            name="student"
                            list="students"
                            value="{{ old('student') }}"
                            required>

                        <datalist id="students">
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">{{ $student->name }}</option>
                            @endforeach
                        </datalist>

                        @error('student')
                            <span class="text-danger">{{ $errors->first('student') }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="expires">Expires</label>
                        <input
                            id="expires"
                            class="datepicker form-control @error('expires') is-invalid @enderror"
                            type="text"
                            name="expires"
                            value="{{ old('expires') }}"
                            required>

                        @error('expires')
                            <span class="text-danger">{{ $errors->first('expires') }}</span>
                        @enderror
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

                    <button type="submit" class="btn btn-success">Create endorsement</button>
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
        $(".datepicker").flatpickr({ minDate: "{!! date('Y-m-d') !!}", dateFormat: "d/m/Y", defaultDate: defaultDate });

        $('.flatpickr-input:visible').on('focus', function () {
            $(this).blur();
        });
        $('.flatpickr-input:visible').prop('readonly', false);
    })
</script>
@endsection