@extends('layouts.app')

@section('title', 'Create Endorsement')
@section('content')

<div class="row" id="giveEndorsements">
    <div class="col-xl-5 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Create 
                </h6> 
            </div>
            <div class="card-body">
                <form id="endorsementForm" action="{!! action('EndorsementController@store') !!}" method="POST">
                    @csrf

                    {{-- User --}} 
                    <div class="mb-3">
                        <label class="form-label" for="user">User</label>
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
                    <label class="form-label">Endorsement</label>
                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="endorsementType" id="endorsementTypeFacility" value="FACILITY" v-model="endorsementType" v-on:change="updateButtonText">
                            <label class="form-check-label" for="endorsementTypeFacility">
                                Facility
                            </label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="endorsementType" id="endorsementTypeSolo" value="SOLO" v-model="endorsementType" v-on:change="updateButtonText">
                            <label class="form-check-label" for="endorsementTypeSolo">
                                Solo
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

                    {{-- Info for FACILITY --}}
                    <div class="alert alert-info" style="display: none" role="alert" v-show="endorsementType == 'FACILITY'">
                        <i class="fas fa-info-circle"></i>&nbsp;Please note that Airport and Center endorsements are automatically granted when training are marked completed.
                    </div>

                    {{-- Expires --}}
                    <div class="mb-3" style="display: none" v-show="endorsementType == 'SOLO'">
                        <label for="expire">Expires</label>
                        <input
                            id="expire"
                            class="datepicker form-control"
                            type="text"
                            name="expires"
                            v-model="expire"
                            v-bind:class="{'is-invalid': (validationError && expire == null)}">
                        <span v-show="validationError && expire == null" class="text-danger">Fill out a valid expire date</span>
                    </div>

                    {{-- Training Positions --}}
                    <div class="mb-3" style="display: none" v-show="endorsementType == 'SOLO'">
                        <label class="form-label" for="positions">Positions</label>
                        <input 
                            id="positions"
                            class="form-control"
                            type="text"
                            name="position"
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

                    {{-- FACILITY Ratings --}}
                    <div class="mb-3" style="display: none" v-show="endorsementType == 'FACILITY'">
                        <label class="form-label" for="ratingFACILITY">Rating</label>
                        <select class="form-select" name="ratingFACILITY" id="ratingFACILITY" v-model="ratingFACILITY" v-bind:class="{'is-invalid': (validationError && ratingFACILITY == null)}">
                            <option selected disabled>Select rating</option>
                            @foreach($ratingsFACILITY as $rating)
                                <option value="{{ $rating->id }}">{{ $rating->endorsement_type }} {{ $rating->name }}</option>
                            @endforeach
                        </select>
                        <span v-show="validationError && ratingFACILITY == null" class="text-danger">Select at least one airport or center</span>
                    </div>

                    {{-- Visiting Rating --}}
                    <div class="mb-3" style="display: none" v-show="endorsementType == 'VISITING' || endorsementType == 'EXAMINER'">
                        <label class="form-label" for="ratingGRP">Rating</label>
                        <select class="form-select" name="ratingGRP" id="ratingGRP" v-model="ratingGRP" v-bind:class="{'is-invalid': (validationError && ratingGRP == null)}">
                            <option selected disabled>Select rating</option>
                            @foreach($ratingsGRP as $rating)
                                <option value="{{ $rating->id }}">{{ $rating->name }}</option>
                            @endforeach
                        </select>
                        <span v-show="validationError && ratingGRP == null" class="text-danger">Select a rating</span>
                    </div>

                    {{-- Examiner/Visiting Areas --}}
                    <div class="mb-3" style="display: none" v-show="endorsementType == 'EXAMINER' || endorsementType == 'VISITING'">
                        <label class="form-label" for="areas">Areas: <span class="badge bg-secondary">Ctrl/Cmd+Click</span> to select multiple</label>
                        <select multiple class="form-select" name="areas[]" id="areas" v-model="areas" v-bind:class="{'is-invalid': (validationError && !areas.length)}">
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                            @endforeach
                        </select>
                        <span v-show="validationError && !areas.length" class="text-danger">Select one or more areas</span>
                    </div>

                    {{-- Training Checkbox --}}
                    <div class="form-check" class="mt-5" style="display: none" v-show="endorsementType == 'SOLO'">
                        <input class="form-check-input" type="checkbox" id="soloChecked" v-model="soloChecked">
                        <label class="form-check-label" for="soloChecked">
                            {{ Setting::get('trainingSoloRequirement') }}
                        </label>
                        <p v-show="validationError && soloChecked == false" class="text-danger">Confirm that the requirements are filled</p>
                    </div>

                    <button type="submit" id="submit_btn" class="btn btn-success mt-4" v-on:click="submit" v-show="endorsementType != null && (endorsementType != 'TRAINING' || (endorsementType == 'SOLO'))" style="display: none">Create endorsement</button>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection

@section('js')
@vite('resources/js/vue.js')
<script>

    document.addEventListener("DOMContentLoaded", function () {

        const giveEndorsements = createApp({
            data() {
                return {
                    endorsementType: null,
                    user: {{ isset($prefillUserId) ? $prefillUserId : "null" }},
                    expire: null,
                    positions: null,
                    ratingFACILITY: null,
                    ratingGRP: null,
                    ratingExaminate: null,
                    areas: [],
                    soloChecked: false,
                    validationError: false,
                    errSoloPositionCount: false,
                }
            },
            methods:{
                updateButtonText(){

                    var btn = document.getElementById("submit_btn");
                    var end = ""

                    const flatpickr = document.getElementsByClassName('datepicker')[0]._flatpickr

                    if(this.endorsementType == 'FACILITY'){
                        end = "Facility"
                    } else if(this.endorsementType == 'SOLO'){
                        end = "Solo"
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

                        Airport/Center -> Single Facility Rating
                        Training -> Type + Expire + Position (Multiple if S1)
                        Examiner -> GRP Ratings + Areas
                        Visitor -> Areas + Single GRP Rating

                        Specials:
                        Positions: Only 1 for Solo.

                    */

                    var validated = true

                    if(this.user == null) validated = false

                    if(this.endorsementType == 'FACILITY'){

                        if(this.ratingFACILITY == null) validated = false

                    } else if(this.endorsementType == 'SOLO'){

                        if(this.expire == null) validated = false
                        if(this.positions == null) validated = false

                        if(this.soloChecked == false) validated = false
                        if(this.positions && this.positions.includes(',')) { 
                            validated = false
                            this.errSoloPositionCount = true 
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
                        document.getElementById('endorsementForm').submit()
                    } else {
                        this.validationError = true
                    }
                }
            }

        }).mount('#giveEndorsements');
    });

</script>

<!-- Flatpickr --> 
@vite(['resources/js/flatpickr.js', 'resources/sass/flatpickr.scss'])
<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelector('.datepicker').flatpickr({ disableMobile: true, minDate: "{!! date('Y-m-d') !!}", maxDate: "{!! date('Y-m-d', strtotime('30 days')) !!}", dateFormat: "d/m/Y", locale: {firstDayOfWeek: 1 } });
    })
</script>

@endsection
