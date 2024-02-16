@extends('layouts.app')

@section('title', 'User Settings')
@section('content')

<div class="row">
    <div class="col-xl-6 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-primary">Settings</h6> 
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-12 col-md-12 mb-12">
                        <form action="{{ route('user.settings.store') }}" method="POST">
                            @csrf

                            <p><b>To change email or password, log into <a href="https://my.vatsim.net/" target="_blank">myVATSIM</a>.</b></p>
                        
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check0" name="setting_notify_newreport" {{ $user->setting_notify_newreport ? "checked" : "" }}>
                                <label class="form-check-label" for="check0">
                                    Send notification when my mentor publishes a new training report
                                </label>
                            </div>
                            
                            <div class="mb-3 mt-4">
                                <label class="form-label" for="active_email">Your VATSIM registered e-mail</label>
                                <input type="email" class="form-control" name="active_email" value="{{ $user->email }}" disabled>
                                @if(!$user->setting_workmail_address)
                                    <small class="form-text text-muted">We're sending notifications to this address.</small>
                                @endif
                            </div>

                            @if($user->isMentorOrAbove())
                                <hr>

                                <h5>Mentor Notifications</h5>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check4" name="setting_notify_tasks" {{ $user->setting_notify_tasks ? "checked" : "" }}>
                                    <label class="form-check-label" for="check4">
                                        Send notification of new tasks
                                    </label>
                                </div>
                                
                            @endif

                            @if($user->isModeratorOrAbove())
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
                                
                                <div class="mb-3 mt-4">
                                    <label class="form-label" for="setting_workmail_address">Work e-mail</label>
                                    <input type="email" class="form-control @error('setting_workmail_address') is-invalid @enderror" id="setting_workmail_address" name="setting_workmail_address" value="{{ $user->setting_workmail_address ? $user->setting_workmail_address : "" }}">
                                    <small class="form-text text-muted">Enter a work e-mail address to send all notifications to instead. Has to be &#64;{{ Setting::get('linkDomain') }}</small>
                                    @error('setting_workmail_address')
                                        <span class="text-danger">{{ $errors->first('setting_workmail_address') }}</span>
                                    @enderror
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