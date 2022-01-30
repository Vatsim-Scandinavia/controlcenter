@extends('layouts.app')

@section('title', 'Application')
@section('content')

<div id="application">

    <form id="training-form">
        @csrf

        <!-- Information about training -->
        <div class="row" v-show="step === 1">
            <div class="col-xl-6 col-lg-12 col-md-12 mb-12">
                <div class="card shadow mb-4 border-left-warning">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Important information</h6>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-graduation-cap"></i>&nbsp;What is ATC training?</h5>
                        <p class="card-text">Welcome to the ATC Training Department of {{ Config::get('app.owner') }}. In order to be able to control on our network you will need to complete our training course. To achieve an ATC rating you have to go through both theoretical and practical training and exams. You will be given all the necessary training documentation and will receive guidance by a mentor throughout the course. You will learn everything you need to know to be compliant with VATSIM Global Ratings Policy as well as about local procedures relevant to your area.</p>
                        <hr>
                        <h5 class="card-title"><i class="fas fa-user"></i>&nbsp;What do we expect from you?</h5>
                        <p class="card-text">First of all, we expect that you take the training seriously and for you to show up on time and prepared for your online training sessions. We also expect that you respect that everyone in the Training Department is doing this as a hobby in their spare time. You have to be able to study on your own as part of the training program is designed as a self-study. We are not getting paid to do this job, but we simply want to see our network grow and be a great community.</p>
                        <hr>
                        <h5 class="card-title"><i class="fas fa-chalkboard-teacher"></i>&nbsp;What should you expect from us?</h5>
                        <p class="card-text">You should expect that we will help you as best as we can to prepare you for your practical exam. You will be assigned to a mentor that will guide you on the way, and you should expect him to take you and your time seriously and to adapt the training to your level of competence.</p>
                        <hr>
                        <h5 class="card-title"><i class="fas fa-hourglass-start"></i>&nbsp;How long is the training queue?</h5>
                        <p class="card-text">{{ Setting::get('trainingQueue') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-6 col-lg-12 col-md-12 mb-12">
                <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Training choices</h6>
                        </div>
                        <div class="card-body">

                            <p class="text-muted">
                                <i class="fas fa-info-circle"></i>&nbsp;&nbsp;S2 is the lowest rating you can apply for in {{ Config::get('app.owner') }}. S1 is included in this training.
                            </p>

                            <div class="row">
                                <div class="col-xl-6 col-md-6 mb-12">
                                    <label class="my-1 mr-2" for="areaSelect">Training area</label>
                                    <select id="areaSelect" @change="areaSelectChange($event)" class="custom-select my-1 mr-sm-2">
                                        <option selected disabled>Choose training area</option>
                                        @foreach($payload as $areaId => $area)
                                            <option value="{{ $areaId }}">{{ $area["name"] }}</option>
                                        @endforeach
                                    </select>
                                    <span v-show="errArea" class="text-danger" style="display: none">Select training area</span>
                                </div>
                                <div class="col-xl-6 col-md-6 mb-12">
                                    <label class="my-1 mr-2" for="ratingSelect">Training type</label>
                                    <select id="ratingSelect" @change="ratingSelectChange($event)" class="custom-select my-1 mr-sm-2">
                                        <option v-if="ratings.length == 0" selected disabled>None available</option>
                                        <option v-for="rating in ratings" :value="rating.id">@{{ rating.name }}</option>
                                    </select> 
                                    <span v-show="errArea" class="text-danger" style="display: none">Select available rating</span>
                                </div>
                            </div>

                            <a class="btn btn-success mt-2" href="#" v-on:click="next">Continue</a>
                        </div>
                    </div>
            </div>
        </div>

        <!-- Student SOP -->
        <div class="row" style="display: none" v-show="step === 2">
            <div class="col-xl-12 col-md-12 mb-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Standard Operating Procedures</h6>
                    </div>
                    <div class="card-body">

                        <p>Please read through the standard operating procedures for students below, and accept the terms by continuing to the next step. If you can't see the document below, <a href="{{ Setting::get('trainingSOP') }}" target="_blank">click here</a>.</p>

                        <embed src="{{ Setting::get('trainingSOP') }}" type="application/pdf" width="100%" height="800px">

                        <a class="btn btn-success"  href="#" v-on:click="next">I accept</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="row" style="display: none" v-show="step === 3">
            <div class="col-xl-12 col-md-12 mb-12">

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-6 col-lg-12 col-md-12 mb-12">
                                <div class="form-group">
                                    <label for="experience">Experience level</label>
                                    <select class="custom-select" name="experience" id="experience">
                                        <option selected disabled>Choose best fitting level...</option>
                                        @foreach(\App\Http\Controllers\TrainingController::$experiences as $id => $data)
                                            <option value="{{ $id }}">{{ $data["text"] }}</option>
                                        @endforeach
                                    </select>
                                    <span v-show="errExperience" class="text-danger" style="display: none">Please select a proper experience level</span>
                                </div>

                                <div class="form-group form-check">
                                    <input type="checkbox" class="form-check-input" id="englishOnly" name="englishOnly" value="true">
                                    <label class="form-check-label" for="englishOnly">I'm <u>only</u> able to receive training in English instead of local language</label>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <label for="motivationTextarea">Letter of motivation</label>
                                    <p class="text-muted">Please tell us about yourself, your background, experience and motivation for applying to {{ Config::get('app.owner') }}</p>
                                    <textarea class="form-control" name="motivation" id="motivationTextarea" rows="10" placeholder="Minimum 250 characters" maxlength="1500"></textarea>
                                    <span v-show="errLOM" class="text-danger" style="display: none">The letter of motivation needs at least 250 characters</span>
                                </div>

                                <hr>

                                <div class="form-group form-check">
                                    <input type="checkbox" class="form-check-input" id="wantRemark" v-model="remarkChecked">
                                    <label class="form-check-label" for="wantRemark">I've an important remark about my training I would like to add</label>
                                </div>

                                <div class="form-group" v-show="remarkChecked">
                                    <label for="remarkTextarea">Remark</label>
                                    <textarea class="form-control" name="comment" id="remarkTextarea" rows="2" placeholder="Please don't repeat information from the application" maxlength="500"></textarea>
                                </div>
                            </div>

                            <div class="col-xl-6 col-lg-12 col-md-12 mb-12">
                                <img class="d-none d-xl-block img-fluid px-3 px-sm-4 mt-3 mb-4" src="{{asset('images/undraw_files_6b3d.svg')}}" alt="">
                            </div>

                        </div>

                        <button type="submit" id="training-submit-btn" class="btn btn-success" v-on:click="submit">Submit training request<div class="submit-spinner spinner-border spinner-border-sm" role="status">&nbsp;</div></button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('js')
<script>

    var payload = {!! json_encode($payload, true) !!}
    var motivationRequired = {{ $motivation_required }}

    const application = new Vue({
        el: '#application',
        data: {
            step: 1,
            ratings: '',
            remarkChecked: 0,
            errArea: 0,
            errRating: 0,
            errExperience: 0,
            errLOM: 0,
        },
        methods:{
            next() {
                if(this.validate(this.step)) this.step++;
            },
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
                this.errLOM = true;
                $('#experience').removeClass('is-invalid');
                $('#motivationTextarea').removeClass('is-invalid');

                // Validate
                let trainingExperience = $('#experience').val();
                let trainingLOM = $('#motivationTextarea').val();

                if(trainingExperience == null){
                    $('#experience').addClass('is-invalid');
                    this.errExperience = true;
                }

                if(trainingLOM.length < 250){
                    $('#motivationTextarea').addClass('is-invalid');
                    this.errLOM = true;
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


/*
    $('#training-submit-btn').click( function (e) {

        e.preventDefault();

        $(this).prop('disabled', true);
        $('.submit-spinner').css('display', 'inherit');

        var form = document.getElementById('training-form');
        var data = new FormData(form);

        data.append('training_area', sessionStorage.getItem('training_area'));
        data.append('training_level', sessionStorage.getItem('training_level'));

        $.ajax('/training/store',
            {
                type: 'post',
                data: data,
                processData: false,
                contentType: false,
                success: function () {
                    sessionStorage.removeItem('training_area');
                    sessionStorage.removeItem('training_level');

                    sessionStorage.setItem("successMessage", "Your training request has been added to the queue!");
                    window.location = "/";

                },
                error: function (error) {
                    var message = error.responseJSON.errors;

                    if (message.experience)
                        $("#err-experience").html("Please select a proper experience level");

                    if (message.motivation)
                        $("#err-motivation").html(message.motivation[0]);

                    $('#training-submit-btn').prop('disabled', false);
                    $('.submit-spinner').hide();

                }
            }
        )

    });*/

</script>
@endsection
