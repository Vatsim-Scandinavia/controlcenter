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
                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Min/Max waiting time</div>
                <div class="row no-gutters align-items-center">
                    <div class="col-auto">
                        <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">2/7 months</div>
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
                    Training Requests last year
                </h6> 
            </div>
            <div class="card-body">
                <canvas id="trainingChart"></canvas>
            </div>
        </div>
    </div>

</div>
<!--
<div class="row">
    <div class="col-xl-6 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    New Training Requests (7 days)
                </h6> 
            </div>
            <div class="card-body">
                <canvas id="trainingRequestChart7day" height="200"></canvas>
            </div>
        </div>
    </div>

</div>
-->

@endsection

@section('js')

<script>
    var labels = [
        "Sunday",
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday"
    ];

    var dataFinland = [
        5,
        2,
        6,
        7,
        1,
        6,
        7
    ];

    var dataIceland = [
        7,
        2,
        0,
        1,
        1,
        2,
        0
    ];

    var dataDenmark = [
        7,
        8,
        2,
        1,
        7,
        1,
        0
    ];

    var dataNorway = [
        8,
        9,
        10,
        2,
        6,
        4,
        8
    ];

    var dataSweden = [
        8,
        2,
        6,
        2,
        7,
        1,
        2
    ];

    var mix = document.getElementById("trainingRequestChart7day").getContext('2d');
    var trainingRequestChart7day = new Chart(mix, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: "Finland",
                    data: dataFinland,
                    borderColor: '#43c6e7',
                    backgroundColor: '#43c6e7',
                    yAxisID: 'requests',
                },
                {
                    label: "Iceland",
                    data: dataIceland,
                    borderColor: '#ff9800',
                    backgroundColor: '#ff9800',
                    yAxisID: 'requests',
                },
                {
                    label: "Denmark",
                    data: dataDenmark,
                    borderColor: '#537bc4',
                    backgroundColor: '#537bc4',
                    yAxisID: 'requests',
                },
                {
                    label: "Norway",
                    data: dataNorway,
                    borderColor: '#b63f3f',
                    backgroundColor: '#b63f3f',
                    yAxisID: 'requests',
                },
                {
                    label: "Sweden",
                    data: dataSweden,
                    borderColor: '#41826e',
                    backgroundColor: '#41826e',
                    yAxisID: 'requests',
                },
            ]
        },
        options: {
            elements: {
                line: {
                    tension: 0
                }
            },
            hover: {
                mode: 'nearest',
                intersect: true
            },
            scales: {
                yAxes: [
                    {
                        id: "requests",
                        ticks: {
                            beginAtZero: true,
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Amount of requests'
                        }
                    },
                ]
            },
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
    ctx.canvas.height = 300;

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