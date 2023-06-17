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

        <form action="{{ route('admin.settings.store') }}" method="POST">
            @csrf

            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">Training</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12 col-md-12 mb-12">

                            <div class="form-check">
                                <input class="form-check-input @error('trainingEnabled') is-invalid @enderror" type="checkbox" id="check0" name="trainingEnabled" {{ Setting::get('trainingEnabled') ? "checked" : "" }}>
                                <label class="form-check-label" for="check0">
                                    Accept new training requests
                                </label>
                            </div>

                            <hr>

                            <div class="form-group">
                                <label class="form-label" for="spoUrl">Student SOP URL</label>
                                <input type="url" class="form-control @error('trainingSOP') is-invalid @enderror" id="spoUrl" name="trainingSOP" required value="{{ Setting::get("trainingSOP") }}">
                                <small class="form-text text-muted">Link to PDF to display when applying for training</small>
                            </div>
                            @error('trainingSOP')
                                <span class="text-danger">{{ $errors->first('trainingSOP') }}</span>
                            @enderror

                            <div class="form-group">
                                <label class="form-label" for="exmUrl">Exam Template URL</label>
                                <input type="url" class="form-control @error('trainingExamTemplate') is-invalid @enderror" id="exmUrl" name="trainingExamTemplate" value="{{ (Setting::get("trainingExamTemplate") != false) ? Setting::get("trainingExamTemplate") : '' }}">
                                <small class="form-text text-muted">Link to examination template for examiners. Leave blank to disable.</small>
                            </div>
                            @error('trainingExamTemplate')
                                <span class="text-danger">{{ $errors->first('trainingExamTemplate') }}</span>
                            @enderror

                            <div class="form-group">
                                <label class="form-label" for="trainingSubDivisions">Subdivisions accepted for training</label>
                                <input type="text" class="form-control @error('trainingSubDivisions') is-invalid @enderror" id="trainingSubDivisions" name="trainingSubDivisions" value="{{ Setting::get("trainingSubDivisions") }}">
                                <small class="form-text text-muted">List subdivisions separated by comma, e.g. SCA, ITA</small>
                            </div>
                            @error('trainingSubDivisions')
                                <span class="text-danger">{{ $errors->first('trainingSubDivisions') }}</span>
                            @enderror

                            <div class="form-group">
                                <label class="form-label" for="trainingQueue">Training queue length</label>
                                <input type="text" class="form-control @error('trainingQueue') is-invalid @enderror" id="trainingQueue" placeholder="Write your text here, keep it short." name="trainingQueue" required value="{{ Setting::get("trainingQueue") }}">
                                <small class="form-text text-muted">Text displayed in FAQ and e-mails. Keep it short.</small>
                            </div>
                            @error('trainingQueue')
                                <span class="text-danger">{{ $errors->first('trainingQueue') }}</span>
                            @enderror

                            <div class="form-group">
                                <label class="form-label" for="atcActivityQualificationPeriod">Required Training Interval</label>
                                <input type="number" class="form-control @error('trainingInterval') is-invalid @enderror" id="trainingInterval" name="trainingInterval" required value="{{ Setting::get("trainingInterval") }}">
                                <small class="form-text text-muted">Input number of days required, used to mark stalled trainings</small>
                            </div>
                            @error('trainingInterval')
                                <span class="text-danger">{{ $errors->first('trainingInterval') }}</span>
                            @enderror

                            <div class="form-group">
                                <label class="form-label" for="atcActivityQualificationPeriod">Solo Endorsement Requirement</label>
                                <input type="text" class="form-control @error('trainingSoloRequirement') is-invalid @enderror" id="trainingSoloRequirement" maxlength="200" name="trainingSoloRequirement" required value="{{ Setting::get("trainingSoloRequirement") }}">
                                <small class="form-text text-muted">Used to confirm solo endorsement creation.</small>
                            </div>
                            @error('trainingSoloRequirement')
                                <span class="text-danger">{{ $errors->first('trainingSoloRequirement') }}</span>
                            @enderror

                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">ATC Activity</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-4 col-md-12 col-sm-12">
                            <div class="form-group">
                                <label class="form-label" for="atcActivityQualificationPeriod">Qualification Period</label>
                                <input type="number" class="form-control @error('atcActivityQualificationPeriod') is-invalid @enderror" id="atcActivityQualificationPeriod" name="atcActivityQualificationPeriod" required value="{{ Setting::get("atcActivityQualificationPeriod") }}">
                                <small class="form-text text-muted">Input number of months</small>
                            </div>
                            @error('atcActivityQualificationPeriod')
                                <span class="text-danger">{{ $errors->first('atcActivityQualificationPeriod') }}</span>
                            @enderror
                        </div>

                        <div class="col-lg-4 col-md-12 col-sm-12">
                            <div class="form-group">
                                <label class="form-label" for="atcActivityGracePeriod">Grace Period</label>
                                <input type="number" class="form-control @error('atcActivityGracePeriod') is-invalid @enderror" id="atcActivityGracePeriod" name="atcActivityGracePeriod" required value="{{ Setting::get("atcActivityGracePeriod") }}">
                                <small class="form-text text-muted">Input number of months</small>
                            </div>
                            @error('atcActivityGracePeriod')
                                <span class="text-danger">{{ $errors->first('atcActivityGracePeriod') }}</span>
                            @enderror
                        </div>

                        <div class="col-lg-4 col-md-12 col-sm-12">
                            <div class="form-group">
                                <label class="form-label" for="atcActivityRequirement">Activity Requirement</label>
                                <input type="number" class="form-control @error('atcActivityRequirement') is-invalid @enderror" id="atcActivityRequirement" name="atcActivityRequirement" required value="{{ Setting::get("atcActivityRequirement") }}">
                                <small class="form-text text-muted">Input number of hours</small>
                            </div>
                            @error('atcActivityRequirement')
                                <span class="text-danger">{{ $errors->first('atcActivityRequirement') }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <div class="form-group">
                                <label class="form-label" for="atcActivityContact">Inactivity Warning Contact</label>
                                <input type="text" class="form-control @error('atcActivityContact') is-invalid @enderror" id="atcActivityContact" placeholder="e.g. Training Director" maxlength="40" name="atcActivityContact" required value="{{ Setting::get("atcActivityContact") }}">
                                <small class="form-text text-muted">Write who the user should contact regarding refresh or transfer trainings. This text links to your <a href="#linkContact">contact list</a>.</small>
                            </div>
                            @error('atcActivityContact')
                                <span class="text-danger">{{ $errors->first('atcActivityContact') }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input @error('atcActivityNotifyInactive') is-invalid @enderror" type="checkbox" id="check1" name="atcActivityNotifyInactive" {{ Setting::get('atcActivityNotifyInactive') ? "checked" : "" }}>
                        <label class="form-check-label" for="check1">
                            Send inactive login notification
                        </label>
                        <small class="form-text text-muted">Sends an e-mail to users with copy to admins, when logged in as inactive controller on the network.</small>
                    </div>
                    <div class="form-check mt-3">
                        <input class="form-check-input @error('atcActivityAllowReactivation') is-invalid @enderror" type="checkbox" id="check2" name="atcActivityAllowReactivation" {{ Setting::get('atcActivityAllowReactivation') ? "checked" : "" }}>
                        <label class="form-check-label" for="check2">
                            Allow automatic re-activation of inactive controllers
                        </label>
                        <small class="form-text text-muted">
                            Disabled: Only a training marked as completed will reactivate a controller as ATC Active<br>
                            Enabled: Same as above and if controller passes activity requirement they will automatically be set as ATC Active. 
                        </small>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">Links</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12 col-md-12 mb-12">

                            <div class="form-group">
                                <label class="form-label" for="linkDomain">Division domain</label>
                                <input type="text" class="form-control @error('linkDomain') is-invalid @enderror" id="linkDomain" name="linkDomain" required value="{{ Setting::get("linkDomain") }}">
                                <small class="form-text text-muted">Enter domain without http or any slashes</small>
                            </div>
                            @error('linkDomain')
                                <span class="text-danger">{{ $errors->first('linkDomain') }}</span>
                            @enderror


                            <div class="form-group">
                                <label for="linkHome">Division homepage</label>
                                <input type="url" class="form-control @error('linkHome') is-invalid @enderror" id="linkHome" name="linkHome" required value="{{ Setting::get("linkHome") }}">
                                <small class="form-text text-muted">Enter full homepage url</small>
                            </div>
                            @error('linkHome')
                                <span class="text-danger">{{ $errors->first('linkHome') }}</span>
                            @enderror


                            <div class="form-group">
                                <label class="form-label" for="linkJoin">Join info</label>
                                <input type="url" class="form-control @error('linkJoin') is-invalid @enderror" id="linkJoin" name="linkJoin" required value="{{ Setting::get("linkJoin") }}">
                                <small class="form-text text-muted">Enter link to a page explaining on how to join your division. Shown in FAQ</small>
                            </div>
                            @error('linkJoin')
                                <span class="text-danger">{{ $errors->first('linkJoin') }}</span>
                            @enderror


                            <div class="form-group">
                                <label class="form-label" for="linkContact">Contact list</label>
                                <input type="url" class="form-control @error('linkContact') is-invalid @enderror" id="linkContact" name="linkContact" required value="{{ Setting::get("linkContact") }}">
                                <small class="form-text text-muted">Enter link to staff or contact list. Shown in FAQ and inactivity warning</small>
                            </div>
                            @error('linkContact')
                                <span class="text-danger">{{ $errors->first('linkContact') }}</span>
                            @enderror


                            <div class="form-group">
                                <label class="form-label" for="linkVisiting">Visiting Controller Info</label>
                                <input type="url" class="form-control @error('linkVisiting') is-invalid @enderror" id="linkVisiting" name="linkVisiting" required value="{{ Setting::get("linkVisiting") }}">
                                <small class="form-text text-muted">Enter link to webpage informing about visiting controlling. Shown in FAQ</small>
                            </div>
                            @error('linkVisiting')
                                <span class="text-danger">{{ $errors->first('linkVisiting') }}</span>
                            @enderror


                            <div class="form-group">
                                <label class="form-label" for="linkDiscord">Discord</label>
                                <input type="url" class="form-control @error('linkDiscord') is-invalid @enderror" id="linkDiscord" name="linkDiscord" required value="{{ Setting::get("linkDiscord") }}">
                                <small class="form-text text-muted">Enter Discord invite link. Shown in e-mails to contact mentor on assignment</small>
                            </div>
                            @error('linkDiscord')
                                <span class="text-danger">{{ $errors->first('linkDiscord') }}</span>
                            @enderror


                            <div class="form-group">
                                <label class="form-label" for="linkMoodle">Moodle</label>
                                <input type="url" class="form-control @error('linkMoodle') is-invalid @enderror" id="linkMoodle" name="linkMoodle" value="{{ (Setting::get("linkMoodle") != false) ? Setting::get("linkMoodle") : '' }}">
                                <small class="form-text text-muted">Enter full link to Moodle or leave blank to disable</small>
                            </div>
                            @error('linkMoodle')
                                <span class="text-danger">{{ $errors->first('linkMoodle') }}</span>
                            @enderror

                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">Telemetry</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12 col-md-12 mb-12">

                            <div class="form-check">
                                <input class="form-check-input @error('telemetryEnabled') is-invalid @enderror" type="checkbox" id="checkTele" name="telemetryEnabled" {{ Setting::get('telemetryEnabled') ? "checked" : "" }}>
                                <label class="form-check-label" for="checkTele">
                                    Enable telemetry
                                </label>
                                <small class="form-text text-muted">This is used to prioritise development based on stats and who is using Control Center. Telemetry only sends the url, version and division name.</small>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div>
                <button class="btn btn-success mt-3 mb-3" type="submit">Save</button>
            </div>

        </form>
    </div>

</div>
@endsection
