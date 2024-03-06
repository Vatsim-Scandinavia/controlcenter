@extends('layouts.app')

@section('title', 'Create Vote')
@section('content')

<div class="row">
    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Vote
                </h6>
            </div>
            <div class="card-body">
                <form action="{!! action('VoteController@store') !!}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="date">End Date</label>
                        <input id="date" class="datepicker form-control @error('expire_date') is-invalid @enderror" type="text" name="expire_date" value="{{ old('expire_date') }}" required>
                        @error('expire_date')
                            <span class="text-danger">{{ $errors->first('expire_date') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="end_at">End Time (Zulu)</label>
                        <input id="end_at" class="form-control @error('expire_time') is-invalid @enderror" type="time" name="expire_time" placeholder="12:00" value="{{ old('expire_time') }}" required>
                        @error('expire_time')
                            <span class="text-danger">{{ $errors->first('expire_time') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="question">Question</label>
                        <input id="question" class="form-control @error('question') is-invalid @enderror" type="text" name="question" value="{{ old('question') }}" required>
                        @error('question')
                            <span class="text-danger">{{ $errors->first('question') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="vote_alternatives">Answer Options</label>
                        <textarea class="form-control @error('vote_options') is-invalid @enderror" id="vote_alternatives" rows="8" placeholder="Write options here, separated by new line" name="vote_options">{{ old('vote_options') }}</textarea>
                        @error('vote_options')
                            <span class="text-danger">{{ $errors->first('vote_options') }}</span>
                        @enderror
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="check1" name="require_active" {{ old('require_active') ? "checked" : "" }}>
                        <label class="form-check-label" for="check1">
                            Only ATC active members can vote
                        </label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="check2" name="require_our_member" {{ old('require_our_member') ? "checked" : "" }}>
                        <label class="form-check-label" for="check2">
                            Only {{ config('app.owner_name_short') }} members can vote
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">Create</button>

                </form>
            </div>
        </div>
    </div>

</div>

@endsection

@section('js')

<!-- Flatpickr -->
@vite(['resources/js/flatpickr.js', 'resources/sass/flatpickr.scss'])
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var defaultDate = "{{ old('date') }}"
        document.querySelector('.datepicker').flatpickr({ disableMobile: true, minDate: "{!! date('Y-m-d') !!}", dateFormat: "d/m/Y", defaultDate: defaultDate });
    })
</script>

@endsection
