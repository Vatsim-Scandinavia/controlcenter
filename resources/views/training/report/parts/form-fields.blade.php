{{--
    Shared training report form fields (position, date, report, areas to improve).

    Expects:
    - $position       string  Current position callsign value.
    - $reportDate     string  Current report date value (European format).
    - $content        string  Current report content.
    - $contentimprove string  Current "areas to improve" content.
    - $positions      Collection  Available positions for the datalist.
--}}

<div class="mb-3">
    <label class="form-label" for="position">Position</label>
    <input
        id="position"
        class="form-control @error('position') is-invalid @enderror"
        type="text"
        name="position"
        list="positions"
        value="{{ $position }}"
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

<div class="mb-3">
    <label class="form-label" for="date">Date</label>
    <input id="date" class="datepicker form-control @error('report_date') is-invalid @enderror" type="text" name="report_date" value="{{ $reportDate }}" required>
    @error('report_date')
        <span class="text-danger">{{ $errors->first('report_date') }}</span>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="contentBox">Report</label>
    <textarea class="form-control @error('content') is-invalid @enderror" name="content" id="contentBox" rows="8" placeholder="Write the report here.">{{ $content }}</textarea>
    @error('content')
        <span class="text-danger">{{ $errors->first('content') }}</span>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="contentimprove">Areas to improve</label>
    <textarea class="form-control @error('contentimprove') is-invalid @enderror" name="contentimprove" id="contentimprove" rows="4" placeholder="In which areas do the student need to improve?">{{ $contentimprove }}</textarea>
    @error('contentimprove')
        <span class="text-danger">{{ $errors->first('contentimprove') }}</span>
    @enderror
</div>
