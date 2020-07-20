@extends('layouts.app')

@section('title', 'Training Statistics')
@section('content')

<h1 class="h3 mb-4 text-gray-800">Training Statistics
    <div class="dropdown show" style="display: inline;">
        <a class="btn btn-sm btn-primary dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            {{ $filterName }}
        </a>
    
        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
            <a class="dropdown-item" href="{{ route('reports.trainings') }}">All Countries</a>
            @foreach($countries as $country)
                <a class="dropdown-item" href="{{ route('reports.training.country', $country->id) }}">{{ $country->name }}</a>
            @endforeach 
        </div>
    </div>
</h1>

<div class="row">

    <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-secondary shadow h-100 py-2">
        <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
            <div class="text-xs font-weight-bold text-uppercase text-gray-600 mb-1">In queue</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $cardStats["waiting"] }} requests</div>
            </div>
            <div class="col-auto">
            <i class="fas fa-hourglass fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">In training</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $cardStats["training"] }} requests</div>
            </div>
            <div class="col-auto">
            <i class="fas fa-book-open fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-info shadow h-100 py-2">
        <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Awaiting exam</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $cardStats["exam"] }} requests</div>
            </div>
            <div class="col-auto">
            <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed this year</div>
                <div class="row no-gutters align-items-center">
                    <div class="col-auto">
                        <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">{{ $cardStats["completed"] }} requests</div>
                    </div>
                </div>
                </div>
                <div class="col-auto">
                <i class="fas fa-check fa-2x text-gray-300"></i>
                </div>
            </div>
            </div>
        </div>
    </div>

</div>

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Training requests last 12 months
                </h6> 
            </div>
            <div class="card-body">
                <canvas id="trainingChart"></canvas>
            </div>
        </div>
    </div>

</div>

<div class="row">

    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    New requests last 6 months
                </h6> 
            </div>
            <div class="card-body">
                <canvas id="newTrainingRequests"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Completed requests last 6 months
                </h6> 
            </div>
            <div class="card-body">
                <canvas id="completedTrainingRequests"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Queue lengths <span class="badge badge-danger">Beta and inaccurate</span>
                </h6> 
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Rating</th>
                            <th>Average waiting time</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach($queues as $queue => $time)
                            <tr>
                                <td>{{ $queue }}</td>
                                <td>{{ \Carbon\CarbonInterval::seconds($time)->cascade()->forHumans(['parts' => 2]) }}</td>
                            </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>


@endsection

@section('js')

<script>
    // Total training amount chart

    var ctx = document.getElementById('trainingChart').getContext('2d');
    ctx.canvas.width = 1000;
    ctx.canvas.height = 200;

    var requestData = {!! json_encode($totalRequests) !!}

    var color = Chart.helpers.color;
    var cfg = {
        data: {
            datasets: [{
                label: 'Training Requests',
                backgroundColor: 'rgb(200, 100, 100)',
                borderColor: 'rgb(255, 100, 100)',
                data: requestData,
                type: 'bar',
                pointRadius: 0,
                fill: false,
                lineTension: 0,
                borderWidth: 2
            }]
        },
        options: {
            animation: {
                duration: 0
            },
            scales: {
                xAxes: [{
                    type: 'time',
                    distribution: 'series',
                    offset: true,
                    ticks: {
                        major: {
                            enabled: true,
                            fontStyle: 'bold'
                        },
                        source: 'data',
                        autoSkip: true,
                        autoSkipPadding: 75,
                        maxRotation: 0,
                        sampleSize: 100
                    },
                }],
                yAxes: [{
                    gridLines: {
                        drawBorder: false
                    },
                    scaleLabel: {
                        display: true,
                        labelString: 'Requests'
                    },
                    ticks: {
                        beginAtZero: true
                    }
                }]
            },
            tooltips: {
                intersect: false,
                mode: 'index',
            }
        }
    };

    var chart = new Chart(ctx, cfg);

</script>

<script>

    // New request chart
    var newRequestsData = {!! json_encode($newRequests) !!}

    var barChartData = {
        labels: [moment().subtract(6, "month").startOf("month").format('MMMM'),
                moment().subtract(5, "month").startOf("month").format('MMMM'),
                moment().subtract(4, "month").startOf("month").format('MMMM'),
                moment().subtract(3, "month").startOf("month").format('MMMM'),
                moment().subtract(2, "month").startOf("month").format('MMMM'),
                moment().subtract(1, "month").startOf("month").format('MMMM'),
                moment().startOf("month").format('MMMM')],
        datasets: [{
            label: 'S2',
            backgroundColor: 'rgb(200, 100, 100)',
            data: newRequestsData["S2"]
        }, {
            label: 'S3',
            backgroundColor: 'rgb(100, 100, 200)',
            data: newRequestsData["S3"]
        }, {
            label: 'C1',
            backgroundColor: 'rgb(100, 200, 100)',
            data: newRequestsData["C1"]
        }]

    };

    var mix = document.getElementById("newTrainingRequests").getContext('2d');
    var newTrainingRequests = new Chart(mix, {
        type: 'bar',
        data: barChartData,
        options: {
            tooltips: {
                mode: 'index',
                intersect: false
            },
            responsive: true,
            scales: {
                xAxes: [{
                    stacked: true,
                }],
                yAxes: [{
                    stacked: true
                }]
            }
        }
    });
</script>

<script>

    // Completed requests chart
    var completedRequestsData = {!! json_encode($completedRequests) !!}

    var barChartData = {
        labels: [moment().subtract(6, "month").startOf("month").format('MMMM'),
                moment().subtract(5, "month").startOf("month").format('MMMM'),
                moment().subtract(4, "month").startOf("month").format('MMMM'),
                moment().subtract(3, "month").startOf("month").format('MMMM'),
                moment().subtract(2, "month").startOf("month").format('MMMM'),
                moment().subtract(1, "month").startOf("month").format('MMMM'),
                moment().startOf("month").format('MMMM')],
        datasets: [{
            label: 'S2',
            backgroundColor: 'rgb(200, 100, 100)',
            data: completedRequestsData["S2"]
        }, {
            label: 'S3',
            backgroundColor: 'rgb(100, 100, 200)',
            data: completedRequestsData["S3"]
        }, {
            label: 'C1',
            backgroundColor: 'rgb(100, 200, 100)',
            data: completedRequestsData["C1"]
        }]

    };

    var mix = document.getElementById("completedTrainingRequests").getContext('2d');
    var completedTrainingRequests = new Chart(mix, {
        type: 'bar',
        data: barChartData,
        options: {
            tooltips: {
                mode: 'index',
                intersect: false
            },
            responsive: true,
            scales: {
                xAxes: [{
                    stacked: true,
                }],
                yAxes: [{
                    stacked: true
                }]
            }
        }
    });
</script>

@endsection