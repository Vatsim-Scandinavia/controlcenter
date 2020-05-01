@extends('layouts.app')

@section('title', 'Statistics')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Statistics</h1>

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

        function beforeNineThirty(date) {
            return date.hour() < 9 || (date.hour() === 9 && date.minute() < 30);
        }

        // Returns true if outside 9:30am-4pm on a weekday
        function outsideMarketHours(date) {
            if (date.isoWeekday() > 5) {
                return true;
            }
            if (unitLessThanDay() && (beforeNineThirty(date) || date.hour() > 16)) {
                return true;
            }
            return false;
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
            if (outsideMarketHours(date)) {
                if (!lessThanDay || !beforeNineThirty(date)) {
                    date = date.clone().add(date.isoWeekday() >= 5 ? 8 - date.isoWeekday() : 1, 'day');
                }
                if (lessThanDay) {
                    date = date.hour(9).minute(30).second(0);
                }
            }
            data.push(randomBar(date, data.length > 0 ? data[data.length - 1].y : 30));
        }

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
                    afterBuildTicks: function(scale, ticks) {
                        var majorUnit = scale._majorUnit;
                        var firstTick = ticks[0];
                        var i, ilen, val, tick, currMajor, lastMajor;

                        val = moment(ticks[0].value);
                        if ((majorUnit === 'minute' && val.second() === 0)
                                || (majorUnit === 'hour' && val.minute() === 0)
                                || (majorUnit === 'day' && val.hour() === 9)
                                || (majorUnit === 'month' && val.date() <= 3 && val.isoWeekday() === 1)
                                || (majorUnit === 'year' && val.month() === 0)) {
                            firstTick.major = true;
                        } else {
                            firstTick.major = false;
                        }
                        lastMajor = val.get(majorUnit);

                        for (i = 1, ilen = ticks.length; i < ilen; i++) {
                            tick = ticks[i];
                            val = moment(tick.value);
                            currMajor = val.get(majorUnit);
                            tick.major = currMajor !== lastMajor;
                            lastMajor = currMajor;
                        }
                        return ticks;
                    }
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
                callbacks: {
                    label: function(tooltipItem, myData) {
                        var label = myData.datasets[tooltipItem.datasetIndex].label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += parseFloat(tooltipItem.value).toFixed(2);
                        return label;
                    }
                }
            }
        }
    };

    var chart = new Chart(ctx, cfg);

</script>

@endsection