@extends('layouts.app')

@section('title', 'Global System Settings')

@section('content')

<div class="row">

    <div class="col-xl-6 col-md-12 mb-12">

        @if(Session::has('success') OR isset($success))
            <div class="alert alert-success" role="alert">
                {!! Session::has('success') ? Session::pull("success") : $error !!}
            </div>
        @endif

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Trainings</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-12 col-md-12 mb-12">

                        <form action="{{ route('admin.settings.store') }}" method="POST">
                            @csrf

                            <div class="form-check">
                                <input class="form-check-input @error('trainingEnabled') is-invalid @enderror" type="checkbox" id="check0" name="trainingEnabled" {{ Setting::get('trainingEnabled') ? "checked" : "" }}>
                                <label class="form-check-label" for="check0">
                                    Accept new training requests
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input @error('trainingShowEstimate') is-invalid @enderror" type="checkbox" id="check1" name="trainingShowEstimate" {{ Setting::get('trainingShowEstimate') ? "checked" : "" }}>
                                <label class="form-check-label" for="check1">
                                    Display estimated waiting time for people in queue
                                </label>
                            </div>

                            <hr>

                            <div class="form-group">
                                <label for="spoUrl">Student SOP URL</label>
                                <input type="url" class="form-control @error('trainingSOP') is-invalid @enderror" id="spoUrl" placeholder="Url to PDF" name="trainingSOP" value="{{ Setting::get("trainingSOP") }}">
                                <small id="spoUrl" class="form-text text-muted">Displayed when applying for training</small>
                            </div>
                            @error('trainingSOP')
                                <span class="text-danger">{{ $errors->first('trainingSOP') }}</span>
                            @enderror

                            <div class="form-group">
                                <label for="trainingSubDivisions">Subdivisions allowed to apply for training (separated by comma)</label>
                                <input type="text" class="form-control @error('trainingSubDivisions') is-invalid @enderror" id="trainingSubDivisions" placeholder="E.g. SCA, ITA..." name="trainingSubDivisions" value="{{ Setting::get("trainingSubDivisions") }}">
                            </div>
                            @error('trainingSubDivisions')
                                <span class="text-danger">{{ $errors->first('trainingSubDivisions') }}</span>
                            @enderror

                            <div class="form-group">
                                <label for="trainingQueue">Training queue text for FAQ</label>
                                <input type="text" class="form-control @error('trainingQueue') is-invalid @enderror" id="trainingQueue" placeholder="Write your text here, keep it short." name="trainingQueue" value="{{ Setting::get("trainingQueue") }}">
                            </div>
                            @error('trainingQueue')
                                <span class="text-danger">{{ $errors->first('trainingQueue') }}</span>
                            @enderror
                            
                            <div>
                                <button class="btn btn-success mt-3" type="submit">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
