@extends('layouts.app')

@section('title', 'Create Endorsement')
@section('content')

<div class="row" id="application">
    <div class="col-xl-5 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Create 
                </h6> 
            </div>
            <div class="card-body">
                <form id="endorsementForm" action="{!! action('EndorsementController@store') !!}" method="POST">
                    @csrf

                    {{-- User --}} 
                    <div class="form-group">
                        <label for="user">User</label>
                        <input 
                            id="user"
                            class="form-control"
                            type="text"
                            name="user"
                            list="userList"
                            v-model="user"
                            v-bind:class="{'is-invalid': (validationError && user == null)}"
                            value="{{ $prefillUserId }}">

                        <datalist id="userList">
                            @foreach($users as $user)
                                @browser('isFirefox')
                                    <option>{{ $user->id }}</option>
                                @else
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endbrowser
                            @endforeach
                        </datalist>
                        <span v-show="validationError && user == null" style="display: none" class="text-danger">Fill out a user for the endorsement</span>
                    </div>

                    {{-- Endorsement --}}
                    <label>Endorsement</label>
                    <div class="form-group">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="endorsementType" id="endorsementTypeMASC" value="MASC" v-model="endorsementType" v-on:change="updateButtonText">
                            <label class="form-check-label" for="endorsementTypeMASC">
                                Airport/Center
                            </label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="endorsementType" id="endorsementTypeTraining" value="TRAINING" v-model="endorsementType" v-on:change="updateButtonText">
                            <label class="form-check-label" for="endorsementTypeTraining">
                                Training
                            </label>
                        </div>

                        @can('create', [\App\Models\Endorsement::class, 'EXAMINER'])
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="endorsementType" id="endorsementTypeExaminer" value="EXAMINER" v-model="endorsementType" v-on:change="updateButtonText">
                                <label class="form-check-label" for="endorsementTypeExaminer">
                                    Examiner
                                </label>
                            </div>
                        @endcan
                        
                        @can('create', [\App\Models\Endorsement::class, 'VISITING'])
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="endorsementType" id="endorsementTypeVisitor" value="VISITING" v-model="endorsementType" v-on:change="updateButtonText">
                                <label class="form-check-label" for="endorsementTypeVisitor">
                                    Visiting
                                </label>
                            </div>
                        @endcan
                    </div>

                    <div style="display: none" v-show="endorsementType != null">
                        <hr>
                    </div>

                    {{-- Info for MASC --}}
                    <div class="alert alert-info" style="display: none" role="alert" v-show="endorsementType == 'MASC'">
                        <i class="fas fa-info-circle"></i>&nbsp;Please note that Airport and Center endorsements are automatically granted when training is marked completed with an passed examination.
                    </div>

                    {{-- Training Type --}} 
                    <div v-show="endorsementType == 'TRAINING'" style="display: none">
                        <label>Type</label>
                        <div class="form-group">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="trainingType" id="trainingTypeS1" value="S1" v-model="trainingType" v-on:change="updateButtonText">
                                <label class="form-check-label" for="trainingTypeS1">
                                    S1
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="trainingType" id="trainingTypeSolo" value="SOLO" v-model="trainingType" v-on:change="updateButtonText">
                                <label class="form-check-label" for="trainingTypeSolo">
                                    Solo
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Expires --}}
                    <div class="form-group" style="display: none" v-show="endorsementType == 'TRAINING' && trainingType != null">
                        <label for="expire">Expires</label>
                        <input
                            id="expire"
                            class="datepicker form-control"
                            type="text"
                            name="expires"
                            v-model="expire"
                            v-bind:class="{'is-invalid': (validationError && expire == null)}">
                        <span v-show="validationError && expire == null" class="text-danger">Fill out an expire date, max 30 days</span>
                    </div>

                    {{-- Training Positions --}}
                    <div class="form-group" style="display: none" v-show="endorsementType == 'TRAINING' && trainingType != null">
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
                        <span v-show="validationError && positions == null" class="text-danger">Select at least one position</span>
                        <p v-show="validationError && errSoloPositionCount == true" class="text-danger">Solo Endorsement can only have one position.</p>
                    </div>

                    {{-- MASC Ratings --}}
                    <div class="form-group" style="display: none" v-show="endorsementType == 'MASC'">
                        <label for="ratingMASC">Rating</label>
                        <select class="form-control" name="ratingMASC" id="ratingMASC" v-model="ratingMASC" v-bind:class="{'is-invalid': (validationError && ratingMASC == null)}">
                            <option selected disabled>Select rating</option>
                            @foreach($ratingsMASC as $rating)
                                <option value="{{ $rating->id }}">{{ $rating->name }}</option>
                            @endforeach
                        </select>
                        <span v-show="validationError && ratingMASC == null" class="text-danger">Select at least one airport or center</span>
                    </div>

                    {{-- Visiting Rating --}}
                    <div class="form-group" style="display: none" v-show="endorsementType == 'VISITING' || endorsementType == 'EXAMINER'">
                        <label for="ratingGRP">Rating</label>
                        <select class="form-control" name="ratingGRP" id="ratingGRP" v-model="ratingGRP" v-bind:class="{'is-invalid': (validationError && ratingGRP == null)}">
                            <option selected disabled>Select rating</option>
                            @foreach($ratingsGRP as $rating)
                                <option value="{{ $rating->id }}">{{ $rating->name }}</option>
                            @endforeach
                        </select>
                        <span v-show="validationError && ratingGRP == null" class="text-danger">Select a rating</span>
                    </div>

                    {{-- Examiner/Visiting Areas --}}
                    <div class="form-group" style="display: none" v-show="endorsementType == 'EXAMINER' || endorsementType == 'VISITING'">
                        <label for="areas">Areas: <span class="badge badge-dark">Ctrl/Cmd+Click</span> to select multiple</label>
                        <select multiple class="form-control" name="areas[]" id="areas" v-model="areas" v-bind:class="{'is-invalid': (validationError && !areas.length)}">
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                            @endforeach
                        </select>
                        <span v-show="validationError && !areas.length" class="text-danger">Select one or more areas</span>
                    </div>

                    {{-- Training Checkbox --}}
                    <div class="form-check" style="display: none" v-show="endorsementType == 'TRAINING' && trainingType == 'SOLO'">
                        <input class="form-check-input" type="checkbox" id="soloChecked" v-model="soloChecked">
                        <label class="form-check-label" for="soloChecked">
                            {{ Setting::get('trainingSoloRequirement') }}
                        </label>
                        <p v-show="validationError && soloChecked == false" class="text-danger">Confirm that the requirements are filled</p>
                    </div>

                    <button type="submit" id="submit_btn" class="btn btn-success mt-4" v-on:click="submit" v-show="endorsementType != null && (endorsementType != 'TRAINING' || (endorsementType == 'TRAINING' && trainingType != null))" style="display: none">Create endorsement</button>
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
    //Activate bootstrap tooltips and calendar
    $(document).ready(function() {

        $(".datepicker").flatpickr({ disableMobile: true, minDate: "{!! date('Y-m-d') !!}", maxDate: "{!! date('Y-m-d', strtotime('1 months')) !!}", dateFormat: "d/m/Y", locale: {firstDayOfWeek: 1 } });

        $('.flatpickr-input:visible').on('focus', function () {
            $(this).blur();
        });
        $('.flatpickr-input').prop('readonly', false);

    })

    // Vue
    const application = new Vue({
        el: '#application',
        data: {
            endorsementType: null,
            trainingType: null,
            user: {{ isset($prefillUserId) ? $prefillUserId : "null" }},
            expire: null,
            positions: null,
            ratingMASC: null,
            ratingGRP: null,
            ratingExaminate: null,
            areas: [],
            soloChecked: false,
            validationError: false,
            errSoloPositionCount: false,
        },
        methods:{
            updateButtonText(){

                var btn = document.getElementById("submit_btn");
                var end = ""

                if(this.endorsementType == 'MASC'){
                    end = "MA/SC"
                } else if(this.endorsementType == 'TRAINING'){
                    end = "Training"
                    if(this.trainingType == 'S1'){
                        end = "S1"
                    } else if(this.trainingType == 'SOLO') {
                        end = "Solo"
                    }
                } else if(this.endorsementType == 'EXAMINER'){
                    end = "Examiner"
                } else if(this.endorsementType == 'VISITING'){
                    end = "Visiting"
                }

                btn.innerText = "Create " + end + " Endorsement";

            },
            validate(){

                /*
                    All -> User

                    Airport/Center -> Single MA/SC Rating
                    Training -> Type + Expire + Position (Multiple if S1)
                    Examiner -> GRP Ratings + Areas
                    Visitor -> Areas + Single GRP Rating

                    Specials:
                    Positions: Only 1 for Solo.

                */

                var validated = true

                if(this.user == null) validated = false

                if(this.endorsementType == 'MASC'){

                    if(this.ratingMASC == null) validated = false

                } else if(this.endorsementType == 'TRAINING'){

                    if(this.expire == null) validated = false
                    if(this.positions == null) validated = false
                    
                    if(this.trainingType == 'SOLO'){
                        if(this.soloChecked == false) validated = false
                        if(this.positions.includes(',')) { 
                            validated = false
                            this.errSoloPositionCount = true 
                        }
                    }

                } else if(this.endorsementType == 'EXAMINER'){

                    if(this.ratingGRP == null) validated = false
                    if(this.areas.length == 0) validated = false

                } else if(this.endorsementType == 'VISITING'){

                    if(this.ratingGRP == null) validated = false
                    if(this.areas.length == 0) validated = false

                }

                return validated
            },
            submit(event) {
                event.preventDefault()

                if(this.validate()){
                    $('#endorsementForm').submit()
                } else {
                    this.validationError = true
                }
            }
        }

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
