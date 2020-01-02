@extends('layouts.app')

@section('title', 'Apply')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Application for ATC Training</h1>

<div class="row">

    @if(!Request::get('step'))
    <!-- Information about training -->
    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4 border-left-danger">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Important information</h6>
            </div>
            <div class="card-body">
                <h5 class="card-title">What is ATC trainig?</h5>
                <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut luctus, nisl non egestas vehicula, velit neque porttitor orci, nec dictum nulla nisi id felis. Etiam consequat semper ante, a sagittis ligula feugiat eu. Morbi sapien arcu, faucibus in fermentum vitae, dapibus in magna. Sed pellentesque nunc id scelerisque egestas. Pellentesque eget laoreet elit. Curabitur vel risus rhoncus, mollis risus in, sodales libero. Etiam condimentum erat euismod, mattis enim sit amet, ultricies eros.</p>

                <h5 class="card-title">What do we expect from you?</h5>
                <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut luctus, nisl non egestas vehicula, velit neque porttitor orci, nec dictum nulla nisi id felis. Etiam consequat semper ante, a sagittis ligula feugiat eu. Morbi sapien arcu, faucibus in fermentum vitae, dapibus in magna. Sed pellentesque nunc id scelerisque egestas. Pellentesque eget laoreet elit. Curabitur vel risus rhoncus, mollis risus in, sodales libero. Etiam condimentum erat euismod, mattis enim sit amet, ultricies eros.</p>

                <h5 class="card-title">What should you expect from us?</h5>
                <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut luctus, nisl non egestas vehicula, velit neque porttitor orci, nec dictum nulla nisi id felis. Etiam consequat semper ante, a sagittis ligula feugiat eu. Morbi sapien arcu, faucibus in fermentum vitae, dapibus in magna. Sed pellentesque nunc id scelerisque egestas. Pellentesque eget laoreet elit. Curabitur vel risus rhoncus, mollis risus in, sodales libero. Etiam condimentum erat euismod, mattis enim sit amet, ultricies eros.</p>
            </div>
        </div>
    </div>

    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Training choices</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-6 col-md-6 mb-12">
                            <label class="my-1 mr-2" for="inlineFormCustomSelectPref">Training country</label>
                            <select id="countrySelect" class="custom-select my-1 mr-sm-2" @change="onChange($event)">
                                <option selected disabled>Choose training country</option>
                                @foreach($payload as $countryId => $country)
                                    <option value="{{ $countryId }}">{{ $country["name"] }}</option>
                                @endforeach

                            </select>
                        </div>
                        <div class="col-xl-6 col-md-6 mb-12">
                            <label class="my-1 mr-2" for="inlineFormCustomSelectPref">Training type</label>
                            <select id="ratingSelect" class="custom-select my-1 mr-sm-2">
                                <option selected disabled>Choose</option>
                                <option v-for="rating in ratings" :value="rating.id">@{{ rating.name }}</option>
                            </select>
                        </div>
                    </div>

                    <a class="btn btn-success" id="continue-btn-step-1" href="?step=2">Continue</a>
                </div>
            </div>
    </div>


    <!-- Student SOP -->
    @elseif(Request::get('step') == 2)

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Standard Operating Procedures</h6>
            </div>
            <div class="card-body">

                <p>Please read through the standard operating procedures for students below, and accept the terms by continueing to the next step.</p>

                <embed src="https://drive.google.com/viewerng/viewer?embedded=true&url=http://vatsim-scandinavia.org/wp-content/uploads/2017/11/VACCSCA-SOP-Students-v2.pdf" type="application/pdf" width="100%" height="800px">

                <a class="btn btn-success" href="?step=3">I accept</a>
            </div>
        </div>
    </div>

    @elseif(Request::get('step') == 3)
    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Details</h6>
            </div>
            <div class="card-body">

                <form id="training-form">
                    @csrf

                    <div class="row">
                        <div class="col-xl-6 col-lg-12 col-md-12 mb-12">
                            <div class="form-group">
                                <label for="inlineFormCustomSelectPref">Experience level</label>
                                <select class="custom-select" name="experience" id="inlineFormCustomSelectPref" onchange="function removeErr() {
                                  $('#err-experience').html('');
                                }; removeErr();">
                                    <option selected disabled>Choose best fitting level...</option>
                                    <option value="1">New to VATSIM</option>
                                    <option value="2">Experienced on VATSIM</option>
                                    <option value="3">Real world pilot</option>
                                    <option value="4">Real world ATC</option>
                                    <option value="5">Holding ATC rating from other vACC</option>
                                    <option value="5">Holding ATC rating from other virtual network</option>
                                </select>
                                <div class="danger text-danger" id="err-experience">

                                </div>
                            </div>

                            <div class="form-group form-check">
                                <input type="checkbox" class="form-check-input" id="exampleCheck1">
                                <label class="form-check-label" name="englishOnly" for="exampleCheck1">I'm only able to receive training in English</label>
                            </div>

                            <hr>

                            <div class="form-group">
                                <label for="motivationTextarea">Letter of motivation</label>
                                <textarea class="form-control" name="motivation" id="motivationTextarea" rows="10" placeholder="Write a short letter of motivation here. Minimum 400 characters" maxlength="1500" onchange="function removeErr() {
                                  $('#err-motivation').html('');
                                }; removeErr();"></textarea>
                                <div class="danger text-danger" id="err-motivation">

                                </div>
                            </div>

                            <div class="form-group">
                                <label for="remarkTextarea">Comments or remarks</label>
                                <textarea class="form-control" name="comment" id="remarkTextarea" rows="2" placeholder="Comment your experience, perferred training language, and other things you think want us to know." maxlength="500"></textarea>
                            </div>
                        </div>

                        <div class="col-xl-6 col-lg-12 col-md-12 mb-12">
                            <img class="d-none d-xl-block img-fluid px-3 px-sm-4 mt-3 mb-4" src="{{asset('images/undraw_files_6b3d.svg')}}" alt="">
                        </div>

                    </div>

                    <button type="submit" id="training-submit-btn" class="btn btn-success">Submit training request</button>
                </form>
            </div>
        </div>


    </div>
    @endif

</div>
@endsection

@section('js')
<script>

    var payload = {!! json_encode($payload, true) !!}

    const country = new Vue({
        el: '#countrySelect',
        methods: {
            onChange(event) {
                rating.update(event.srcElement.value)
            }
        }

    });

    const rating = new Vue({
        el: '#ratingSelect',
        data: {
            ratings: '',
        },
        methods: {
            update: function(value){
                this.ratings = payload[event.target.value].data
            }
        }
    });

    $('#continue-btn-step-1').click( function () {

        let training_country = $('#countrySelect').val();
        sessionStorage.setItem('training_country', training_country);
        let training_level = $('#ratingSelect').val();
        sessionStorage.setItem('training_level', training_level);

    });

    $('#training-submit-btn').click( function (e) {

        e.preventDefault();

        var form = document.getElementById('training-form');
        var data = new FormData(form);

        data.append('training_country', sessionStorage.getItem('training_country'));
        data.append('training_level', sessionStorage.getItem('training_level'));


        $.ajax('/training/store',
            {
                type: 'post',
                data: data,
                processData: false,
                contentType: false,
                success: function () {
                    sessionStorage.removeItem('training_country');
                    sessionStorage.removeItem('training_level');
                },
                error: function (error) {
                    var message = error.responseJSON.errors;

                    if (message.experience)
                        $("#err-experience").html("Please select a proper experience level");

                    if (message.motivation)
                        $("#err-motivation").html(message.motivation[0]);

                }
            }
        )

    });

</script>
@endsection
