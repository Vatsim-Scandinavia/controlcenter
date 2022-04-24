@extends('layouts.app')

@section('title', 'Vatbook Bulk')
@section('content')

<div class="row">
    <div class="col-xl-4 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Create Bulk Bookings
                </h6> 
            </div>
            <div class="card-body">
                <form action="{!! action('VatbookController@storeBulk') !!}" method="POST">
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
                        <label for="positions">Positions <span class="text-muted">(comma-separated)</span></label>
                        <input 
                            id="positions"
                            class="form-control"
                            type="text"
                            name="positions"
                            list="positionsList"
                            multiple="multiple"
                            v-model="positions"
                            v-bind:class="{'is-invalid': (validationError && positions == null)}">

                        <datalist id="positionsList">
                            @foreach($positions as $position)
                                @browser('isFirefox')
                                    <option>{{ $position->callsign }}</option>
                                @else
                                    <option value="{{ $position->callsign }}">{{ $position->name }}</option>
                                @endbrowser
                            @endforeach
                        </datalist>
                    </div>

                    @can('bookTags', \App\Models\Vatbook::class)
                        <div class="form-group">
                            <input id="exam" type="checkbox" name="tag" value=2 onClick="change(this)">
                            <label for="exam">Exam</label>
                            &nbsp;&nbsp;&nbsp;

                            <input id="event" type="checkbox" name="tag" value=3 onClick="change(this)">
                            <label for="event">Event</label>
                        </div>
                    @endcan

                    <button type="submit" class="btn btn-success">Add Bookings</button>

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
<script>
    //Activate bootstrap tooltips
    $(document).ready(function() {
        $("body").tooltip({ selector: '[data-toggle=tooltip]', delay: {"show": 150, "hide": 0} });
    });
</script>
<!-- Multiple select datalist from https://www.meziantou.net/html-multiple-selections-with-datalist.htm -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const separator = ',';
        for (const input of document.getElementsByTagName("input")) {
            if (!input.multiple) {
                continue;
            }
            if (input.list instanceof HTMLDataListElement) {
                const optionsValues = Array.from(input.list.options).map(opt => opt.value);
                let valueCount = input.value.split(separator).length;
                input.addEventListener("input", () => {
                    const currentValueCount = input.value.split(separator).length;
                    // Do not update list if the user doesn't add/remove a separator
                    // Current value: "a, b, c"; New value: "a, b, cd" => Do not change the list
                    // Current value: "a, b, c"; New value: "a, b, c," => Update the list
                    // Current value: "a, b, c"; New value: "a, b" => Update the list
                    if (valueCount !== currentValueCount) {
                        const lsIndex = input.value.lastIndexOf(separator);
                        const str = lsIndex !== -1 ? input.value.substr(0, lsIndex) + separator : "";
                        filldatalist(input, optionsValues, str);
                        valueCount = currentValueCount;
                    }
                });
            }
        }
        function filldatalist(input, optionValues, optionPrefix) {
            const list = input.list;
            if (list && optionValues.length > 0) {
                list.innerHTML = "";
                const usedOptions = optionPrefix.split(separator).map(value => value.trim());
                for (const optionsValue of optionValues) {
                    if (usedOptions.indexOf(optionsValue) < 0) {
                        const option = document.createElement("option");
                        option.value = optionPrefix + optionsValue;
                        list.append(option);
                    }
                }
            }
        }
    });
</script>
@endsection