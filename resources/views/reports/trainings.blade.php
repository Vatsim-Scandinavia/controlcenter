@extends('layouts.app')

@section('title', 'Training Statistics')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Training Statistics
    <div class="dropdown show" style="display: inline;">
        <a class="btn btn-sm btn-primary dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            All FIRs
        </a>
    
        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
            <a class="dropdown-item" href="#">Denmark</a>
            <a class="dropdown-item" href="#">Finland</a>
            <a class="dropdown-item" href="#">Iceland</a>
        </div>
    </div>
</h1>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif



<div class="row">

    <div class="col-xl-2 col-md-6 mb-4">
    <div class="card border-left-secondary shadow h-100 py-2">
        <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
            <div class="text-xs font-weight-bold text-uppercase mb-1">In queue</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">21</div>
            </div>
            <div class="col-auto">
            <i class="fas fa-hourglass fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

    <div class="col-xl-2 col-md-6 mb-4">
    <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">In training</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">5</div>
            </div>
            <div class="col-auto">
            <i class="fas fa-book-open fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

    <div class="col-xl-2 col-md-6 mb-4">
    <div class="card border-left-info shadow h-100 py-2">
        <div class="card-body">
        <div class="row no-gutters align-items-center">
            <div class="col mr-2">
            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Awaiting exam</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">2</div>
            </div>
            <div class="col-auto">
            <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
            </div>
        </div>
        </div>
    </div>
    </div>

    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed this year</div>
                <div class="row no-gutters align-items-center">
                    <div class="col-auto">
                        <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">19</div>
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

    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2">
            <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Average waiting time</div>
                <div class="row no-gutters align-items-center">
                    <div class="col-auto">
                        <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">5.4 months</div>
                    </div>
                </div>
                </div>
                <div class="col-auto">
                <i class="fas fa-hourglass fa-2x text-gray-300"></i>
                </div>
            </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-6 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2">
            <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Min-Max waiting time</div>
                <div class="row no-gutters align-items-center">
                    <div class="col-auto">
                        <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">2-7 months</div>
                    </div>
                </div>
                </div>
                <div class="col-auto">
                <i class="fas fa-hourglass fa-2x text-gray-300"></i>
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
                    Training requests last year
                </h6> 
            </div>
            <div class="card-body">
                <canvas id="trainingChart"></canvas>
            </div>
        </div>
    </div>

</div>

<div class="row">

    <div class="col-xl-6 col-md-12 mb-12">
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

    <div class="col-xl-6 col-md-12 mb-12">
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

</div>


@endsection

@section('js')

<script>
    function randomScalingFactor() {
		return Math.round(Math.random() * 100);
	};

    var barChartData = {
        labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
        datasets: [{
            label: 'S2',
            backgroundColor: 'rgb(200, 100, 100)',
            data: [
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor()
            ]
        }, {
            label: 'S3',
            backgroundColor: 'rgb(100, 100, 200)',
            data: [
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor()
            ]
        }, {
            label: 'C1',
            backgroundColor: 'rgb(100, 200, 100)',
            data: [
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor()
            ]
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
    function randomScalingFactor() {
		return Math.round(Math.random() * 100);
	};

    var barChartData = {
        labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
        datasets: [{
            label: 'S2',
            backgroundColor: 'rgb(200, 100, 100)',
            data: [
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor()
            ]
        }, {
            label: 'S3',
            backgroundColor: 'rgb(100, 100, 200)',
            data: [
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor()
            ]
        }, {
            label: 'C1',
            backgroundColor: 'rgb(100, 200, 100)',
            data: [
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor(),
                randomScalingFactor()
            ]
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

<script>

    function generateData() {
        var unit = "day";

        function unitLessThanDay() {
            return unit === 'second' || unit === 'minute' || unit === 'hour';
        }

        function randomNumber(min, max) {
            return Math.random() * (max - min) + min;
        }

        function randomBar(date, lastClose) {
            var open = randomNumber(lastClose * 0.95, lastClose * 1.05).toFixed(2);
            var close = randomNumber(open * 0.95, open * 1.05).toFixed(2);
            return {
                t: date.valueOf(),
                y: close
            };
        }

        var date = moment('Jan 01 1990', 'MMM DD YYYY');
        var now = moment();
        var data = [];
        var lessThanDay = unitLessThanDay();
        for (; data.length < 600 && date.isBefore(now); date = date.clone().add(1, unit).startOf(unit)) {
            data.push(randomBar(date, data.length > 0 ? data[data.length - 1].y : 30));
        }

        console.log(data);
        return data;
    }

    var ctx = document.getElementById('trainingChart').getContext('2d');
    ctx.canvas.width = 1000;
    ctx.canvas.height = 200;

    var color = Chart.helpers.color;
    var cfg = {
        data: {
            datasets: [{
                label: 'Training Requests',
                backgroundColor: 'rgb(200, 100, 100)',
                borderColor: 'rgb(255, 100, 100)',
                data: generateData(),
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

@endsection