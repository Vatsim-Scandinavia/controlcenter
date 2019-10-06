@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Dashboard</h1>

<div class="row">
    <!-- Current rank card  -->
    <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
            <div class="text-xs font-weight-bold text-uppercase mb-1">Current Rank</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">Student 3</div>
            </div>
            <div class="col-auto">
            <i class="fas fa-id-badge fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

    <!-- Favorite position card -->
    <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
            <div class="text-xs font-weight-bold text-uppercase mb-1">Your favorite position</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">ENGM_W_APP</div>
            </div>
            <div class="col-auto">
            <i class="fas fa-star fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

    <!-- ATC Hours card -->
    <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">ATC Hours (Annual)</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">245 of 60 hours</div>
            </div>
            <div class="col-auto">
            <i class="fas fa-clock fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

    

    <!-- Last training card -->
    <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-info shadow h-100 py-2">
        <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Last training</div>
            <div class="row no-gutters align-items-center">
                <div class="col-auto">
                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">20th June 2019</div>
                </div>
            </div>
            </div>
            <div class="col-auto">
            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

</div>

<div class="row">
    <!-- Area Chart -->
    <div class="col-xl-8 col-lg-7">
    <div class="card shadow mb-4">
        <!-- Card Header - Dropdown -->
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">My Trainings</h6>
        </div>
        <!-- Card Body -->
        <div class="card-body">
        
        <div class="table-responsive">
            <table class="table table-striped" width="100%" cellspacing="0">
            <thead>
                <tr>
                <th>Level</th>
                <th>Country</th>
                <th>Period</th>
                <th>State</th>
                <th>Reports</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                <td>S2</td>
                <td>NO</td>
                <td>18 Apr 2016 - 19 May 2016</td>
                <td>Training completed</td>
                <td>
                    <a href="#" class="btn btn-sm btn-primary"><i class="fas fa-clipboard"></i>&nbsp;6</a>
                </td>
                </tr>
                <tr>
                <td>S2 + MAE ENGM</td>
                <td>NO</td>
                <td>20 Sept 2017 - 19 Jan 2018</td>
                <td>Training completed</td>
                <td>
                    <a href="#" class="btn btn-sm btn-primary"><i class="fas fa-clipboard"></i>&nbsp;3</a>
                </td>
                </tr>
                <tr>
                <td>S3</td>
                <td>NO</td>
                <td>20 Apr 2019 - Now</td>
                <td>Training active</td>
                <td>
                    <a href="#" class="btn btn-sm btn-primary"><i class="fas fa-clipboard"></i>&nbsp;1</a>
                </td>
                </tr>
            </tbody>
            </table>
        </div>

        </div>
    </div>
    </div>

    <!-- Pie Chart -->
    <div class="col-xl-4 col-lg-5">
    <div class="card shadow mb-4">
        <!-- Card Header - Dropdown -->
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Training</h6>
        </div>
        <!-- Card Body -->
        <div class="card-body">
        <div class="text-center">
            <img class="img-fluid px-3 px-sm-4 mt-3 mb-4" style="width: 25rem;" src="images/undraw_aircraft_fbvl.svg" alt="">
        </div>
        <p>Are you interested in becoming an air traffic controller, or get a higher rank? Here you can request your training, and you will be notified when it's your turn.</p>
        <a href="{{ route('training.apply') }}" class="btn btn-success btn-block">
            Request training
        </a>
        </div>
    </div>
    </div>

</div>
@endsection
