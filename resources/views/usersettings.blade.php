@extends('layouts.app')

@section('title', 'User Settings')

@section('content')
<h1 class="h3 mb-4 text-gray-800">User settings</h1>

<div class="row">

    <div class="col-xl-6 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Settings</h6> 
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-12 col-md-12 mb-12">
                        <p>To change email or password, log into the <a href="https://cert.vatsim.net/sso/home/dashboard/" target="_blank">Vatsim Membership Dashboard</a> and click on the yellow arrow on top.</p>
                    
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="check0">
                            <label class="form-check-label" for="check0">
                                Send notification when my mentor publishes a new training report
                            </label>
                        </div>

                        <a class="btn btn-success mt-3" href="#">Save</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Notifications</h6> 
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-12 col-md-12 mb-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="check1">
                            <label class="form-check-label" for="check1">
                                Send notification of new training requests
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="check2">
                            <label class="form-check-label" for="check2">
                                Send notification of closed training requests
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="check3">
                            <label class="form-check-label" for="check3">
                                Send notification of new examination reports
                            </label>
                        </div>

                        <a class="btn btn-success mt-3" href="#">Save</a>
                    </div>
                </div>               
            </div>
        </div>
    </div>

</div>
@endsection