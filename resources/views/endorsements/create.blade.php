@extends('layouts.app')

@section('title', 'Create Endorsement')
@section('content')

<div class="row" id="application">
    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Create 
                </h6> 
            </div>
            <div class="card-body">
                <form action="{!! action('SoloEndorsementController@store') !!}" method="POST">
                    @csrf

                    <label>Endorsement</label>
                    <div class="form-group">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="endorsementType" id="endorsementTypeMASC" value="MASC" v-model="type">
                            <label class="form-check-label" for="endorsementTypeMASC">
                                Airport/Center
                            </label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="endorsementType" id="endorsementTypeTraining" value="TRAINING" v-model="type">
                            <label class="form-check-label" for="endorsementTypeTraining">
                                Training/Solo
                            </label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="endorsementType" id="endorsementTypeExaminer" value="EXAMINER" v-model="type">
                            <label class="form-check-label" for="endorsementTypeExaminer">
                                Examiner
                            </label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="endorsementType" id="endorsementTypeVisitor" value="VISITING" v-model="type">
                            <label class="form-check-label" for="endorsementTypeVisitor">
                                Visitor
                            </label>
                        </div>
                    </div>
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
                                @browser('isFirefox')
                                    <option>{{ $student->id }}</option>
                                @else
                                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                                @endbrowser
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

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="requirement_check">
                        <label class="form-check-label" for="requirement_check">
                            {{ Setting::get('trainingSoloRequirement') }}
                        </label>
                    </div>

                    <button type="submit" id="submit_btn" class="btn btn-success mt-4" disabled>Create endorsement</button>
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
    
        // Check if the requirement checkbox is checked.
        var checker = document.getElementById('requirement_check');
        var sendbtn = document.getElementById('submit_btn');
        checker.onchange = function() {
            sendbtn.disabled = !this.checked;
        };

    })
</script>
<script>

    const application = new Vue({
        el: '#application',
        data: {
            type: null,
        },
        methods:{
            validate(page){
                var validated = true

                if(page == 1){
                    let trainingArea = $('#areaSelect').val();
                    let trainingLevel = $('#ratingSelect').val();

                    if (trainingArea == null){
                        $('#areaSelect').addClass('is-invalid');
                        this.errArea = true;
                        validated = false;
                    }

                    if (trainingLevel == null) {
                        $('#ratingSelect').addClass('is-invalid');
                        this.errRating = true;
                        validated = false;
                    } 
                } else if(page == 2){
                    validated = true;
                }

                return validated
            },
            submit(event) {
                event.preventDefault();

                // Reset errors
                this.errExperience = false;
                this.errLOM = false;
                $('#experience').removeClass('is-invalid');
                $('#motivationTextarea').removeClass('is-invalid');

                // Validate
                let trainingExperience = $('#experience').val();
                let trainingLOM = $('#motivationTextarea').val();
                var errored = false;

                if(trainingExperience == null){
                    $('#experience').addClass('is-invalid');
                    this.errExperience = true;
                }

                if(trainingLOM.length < 250 && this.motivationRequired){
                    $('#motivationTextarea').addClass('is-invalid');
                    this.errLOM = true;
                }

                // Submit form if validation is successful
                if(!this.errExperience && !this.errLOM){
                    $('#training-submit-btn').prop('disabled', true);
                    $('.submit-spinner').css('display', 'inherit');
                    $('#training-form').submit();
                }
    
            },
            areaSelectChange(event) {
                this.ratingSelectUpdate(event.srcElement.value);
                $('#areaSelect').removeClass('is-invalid');
                $('#ratingSelect').removeClass('is-invalid');
                this.errArea = false;
                this.errRating = false;
            },
            ratingSelectChange(event){
                $('#ratingSelect').removeClass('is-invalid');
            },
            ratingSelectUpdate(areaId){
                this.ratings = payload[areaId].data
            }
        }

    });

</script>
@endsection
