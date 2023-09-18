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
                        <label class="form-label">Type</label>
                        <div class="mb-3">
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
                    <div class="mb-3" style="display: none" v-show="endorsementType == 'TRAINING' && trainingType != null">
                        <label for="expire">Expires</label>
                        <input
                            id="expire"
                            class="datepicker form-control"
                            type="text"
                            name="expires"
                            v-model="expire"
                            :disabled="expireInf"
                            :placeholder="expireInf && 'Never expires'" 
                            v-bind:class="{'is-invalid': (validationError && expire == null)}">
                        <span v-show="validationError && expire == null" class="text-danger">Fill out a valid expire date</span>
                        <div class="form-check">
                        <input class="form-check-input" type="checkbox" v-model="expireInf" name="expireInf" id="expireinf" value="true">
                            <label class="form-check-label" for="expireinf">
                                Infinte duration
                            </label>
                        </div>
                    </div>

                    {{-- Training Positions --}}
                    <div class="mb-3" style="display: none" v-show="endorsementType == 'TRAINING' && trainingType != null">
                        <label class="form-label" for="positions">Positions <span class="text-muted">(comma-separated)</span></label>
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
                        <div class="dropdown float-end">
                            <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Template
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                @foreach($areas as $a)
                                    @if($a->template_s1_positions)
                                        <a class="dropdown-item" v-on:click="positions = '{{ $a->template_s1_positions }}'">{{ $a->name }}</a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <span v-show="validationError && positions == null" class="text-danger">Select at least one position</span>
                        <p v-show="validationError && errSoloPositionCount == true" class="text-danger">Solo Endorsement can only have one position.</p>
                    </div>

                    {{-- MASC Ratings --}}
                    <div class="mb-3" style="display: none" v-show="endorsementType == 'MASC'">
                        <label class="form-label" for="ratingMASC">Rating</label>
                        <select class="form-select" name="ratingMASC" id="ratingMASC" v-model="ratingMASC" v-bind:class="{'is-invalid': (validationError && ratingMASC == null)}">
                            <option selected disabled>Select rating</option>
                            @foreach($ratingsMASC as $rating)
                                <option value="{{ $rating->id }}">{{ $rating->name }}</option>
                            @endforeach
                        </select>
                        <span v-show="validationError && ratingMASC == null" class="text-danger">Select at least one airport or center</span>
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
@vite('resources/js/vue.js')
<script>

    document.addEventListener("DOMContentLoaded", function () {

        const giveEndorsements = createApp({
            data() {
                return {
                    endorsementType: null,
                    trainingType: null,
                    user: {{ isset($prefillUserId) ? $prefillUserId : "null" }},
                    expire: null,
                    expireInf: null,
                    positions: null,
                    ratingMASC: null,
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

                    if(this.endorsementType == 'MASC'){
                        end = "MA/SC"
                    } else if(this.endorsementType == 'TRAINING'){
                        end = "Training"
                        if(this.trainingType == 'S1'){
                            end = "S1"
                            // While at it, let's set calendar max date as well
                            flatpickr.config.maxDate = moment().add(3, 'M').toDate()
                            flatpickr.jumpToDate(moment().toDate())
                        } else if(this.trainingType == 'SOLO') {
                            end = "Solo"
                            // While at it, let's set calendar max date as well
                            flatpickr.config.maxDate = moment().add(1, 'M').toDate()
                            flatpickr.jumpToDate(moment().toDate())
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

                        if(this.expire == null && this.expireInf != true) validated = false
                        if(this.positions == null) validated = false
                        
                        if(this.trainingType == 'SOLO'){
                            if(this.soloChecked == false) validated = false
                            if(this.positions && this.positions.includes(',')) { 
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
@include('scripts.flatpickr')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelector('.datepicker').flatpickr({ disableMobile: true, minDate: "{!! date('Y-m-d') !!}", maxDate: "{!! date('Y-m-d', strtotime('1 months')) !!}", dateFormat: "d/m/Y", locale: {firstDayOfWeek: 1 } });
    })
</script>

@include('scripts.multipledatalist')

@endsection
