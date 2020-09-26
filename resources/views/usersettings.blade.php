@extends('layouts.app')

@section('title', 'User Settings')
@section('content')

<div class="row">
    <div class="col-xl-6 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Settings</h6> 
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-12 col-md-12 mb-12">
                        <form action="{{ route('user.settings.store') }}" method="POST">
                            @csrf

                            <p>To change email or password, log into <a href="https://my.vatsim.net/" target="_blank">myVatsim</a>.</p>
                        
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check0" name="setting_notify_newreport" {{ $user->setting_notify_newreport ? "checked" : "" }}>
                                <label class="form-check-label" for="check0">
                                    Send notification when my mentor publishes a new training report
                                </label>
                            </div>

                            @if($user->isModerator())
                                <hr>

                                <h5>Moderator Notifications</h5>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check1" name="setting_notify_newreq" {{ $user->setting_notify_newreq ? "checked" : "" }}>
                                    <label class="form-check-label" for="check1">
                                        Send notification of new training requests
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check2" name="setting_notify_closedreq" {{ $user->setting_notify_closedreq ? "checked" : "" }}>
                                    <label class="form-check-label" for="check2">
                                        Send notification of closed training requests
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check3" name="setting_notify_newexamreport" {{ $user->setting_notify_newexamreport ? "checked" : "" }}>
                                    <label class="form-check-label" for="check3">
                                        Send notification of new examination reports
                                    </label>
                                </div>
                            @endif

                            <button class="btn btn-success mt-3" type="submit">Save</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection