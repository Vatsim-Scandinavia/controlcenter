@extends('layouts.app')

@section('title', 'Training Statistics')
@section('title-flex')
    <div class="d-flex align-items-center flex-wrap gap-2">
        <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center me-3">
             <div class="input-group input-group-sm w-auto">
                <span class="input-group-text"><i class="fas fa-calendar me-1"></i>Date</span>
                <span class="input-group-text">From</span>
                <input type="text" name="start_date" class="form-control datepicker" value="{{ $startDate ? $startDate->format('Y-m-d') : '' }}" placeholder="Start Date">
                <span class="input-group-text">To</span>
                <input type="text" name="end_date" class="form-control datepicker" value="{{ $endDate ? $endDate->format('Y-m-d') : '' }}" placeholder="End Date">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                @if($startDate || $endDate)
                    <a href="{{ url()->current() }}" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
                @endif
             </div>
        </form>

        <div class="input-group input-group-sm w-auto">
            <span class="input-group-text"><i class="fas fa-filter me-1"></i>Filter</span>
            @if(\Auth::user()->isAdmin())
                <a class="btn btn-sm {{ $filterName == "All Areas" ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('reports.trainings', ['start_date' => request('start_date'), 'end_date' => request('end_date')]) }}">All Areas</a>
            @endif
            @foreach($areas as $area)
                @if(\Auth::user()->isModeratorOrAbove($area))
                    <a class="btn btn-sm {{ $filterName == $area->name ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('reports.training.area', ['id' => $area->id, 'start_date' => request('start_date'), 'end_date' => request('end_date')]) }}">{{ $area->name }}</a>
                @endif
            @endforeach
        </div>
    </div>
@endsection
@section('content')

@if($startDate && $endDate && $startDate->greaterThanOrEqualTo($endDate))
    <div class="row mt-3">
        <div class="col-xl-12 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-body p-0">
                    <div class="text-center pt-4 pb-4">
                        <i class="fas fa-exclamation-triangle" style="font-size: 5rem;"></i>
                        <p class="pt-4 fs-5">
                            Start date must be before end date
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else

<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-secondary shadow h-100 py-2">
        <div class="card-body">
        <div class="row g-0 align-items-center">
            <div class="col me-2">
            <div class="fs-sm fw-bold text-uppercase text-gray-600 mb-1">In queue</div>
            <div class="h5 mb-0 fw-bold text-gray-800">{{ $cardStats["waiting"] }} requests</div>
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
        <div class="row g-0 align-items-center">
            <div class="col me-2">
            <div class="fs-sm fw-bold text-warning text-uppercase mb-1">In training</div>
            <div class="h5 mb-0 fw-bold text-gray-800">{{ $cardStats["training"] }} requests</div>
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
        <div class="row g-0 align-items-center">
            <div class="col me-2">
            <div class="fs-sm fw-bold text-info text-uppercase mb-1">Awaiting exam</div>
            <div class="h5 mb-0 fw-bold text-gray-800">{{ $cardStats["exam"] }} requests</div>
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
            <div class="row g-0 align-items-center">
                <div class="col me-2">
                <div class="fs-sm fw-bold text-success text-uppercase mb-1">Completed{{ $startDate ? '' : ' this year' }}</div>
                <div class="row g-0 align-items-center">
                    <div class="col-auto">
                        <div class="h5 mb-0 me-3 fw-bold text-gray-800">{{ $cardStats["completed"] }} requests</div>
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

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
            <div class="row g-0 align-items-center">
                <div class="col me-2">
                <div class="fs-sm fw-bold text-danger text-uppercase mb-1">Closed{{ $startDate ? '' : ' this year' }}</div>
                <div class="row g-0 align-items-center">
                    <div class="col-auto">
                        <div class="h5 mb-0 me-3 fw-bold text-gray-800">{{ $cardStats["closed"] }} requests</div>
                    </div>
                </div>
                </div>
                <div class="col-auto">
                <i class="fas fa-ban fa-2x text-gray-300"></i>
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
                <h6 class="m-0 fw-bold text-white">
                    Training requests{{ $startDate ? '' : ' last 12 months' }}
                </h6>
            </div>
            <div class="card-body">
                <canvas id="trainingChart"></canvas>
            </div>
        </div>
    </div>

</div>

<div class="row">

    <div class="col-xl-4 col-md-12 mb-12 d-none d-xl-block d-lg-block d-md-block">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    New requests{{ $startDate ? '' : ' last 6 months' }}
                </h6>
            </div>
            <div class="card-body">
                <canvas id="newTrainingRequests"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-12 mb-12 d-none d-xl-block d-lg-block d-md-block">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Completed requests{{ $startDate ? '' : ' last 6 months' }}
                </h6>
            </div>
            <div class="card-body">
                <canvas id="completedTrainingRequests"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-12 mb-12 d-none d-xl-block d-lg-block d-md-block">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Closed requests{{ $startDate ? '' : ' last 6 months' }}
                </h6>
            </div>
            <div class="card-body">
                <canvas id="closedTrainingRequests"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-12 mb-12 d-none d-xl-block d-lg-block d-md-block">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Passed and failed exams{{ $startDate ? '' : ' last 6 months' }}
                </h6>
            </div>
            <div class="card-body">
                <canvas id="TrainingPassFailRate"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Estimated queue lengths
                </h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Rating</th>
                            <th>Waiting time average low — high</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach($queues as $queue => $time)
                            <tr>
                                <td>{{ $queue }}</td>
                                <td>{{ \Carbon\CarbonInterval::seconds(round($time[0]))->cascade()->forHumans(['parts' => 2]) }} — {{ \Carbon\CarbonInterval::seconds(round($time[1]))->cascade()->forHumans(['parts' => 2]) }}</td>
                            </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endif

@endsection

@section('js')
@vite(['resources/js/chart.js', 'resources/js/flatpickr.js', 'resources/sass/flatpickr.scss'])
<script>
    function generateLegendLabelsWithTotal(chart) {
        const labels = Chart.defaults.plugins.legend.labels.generateLabels(chart);
        labels.forEach(label => {
            const dataset = chart.data.datasets[label.datasetIndex];
            const total = dataset.data.reduce((a, b) => a + b, 0);
            label.text += ' (' + total + ')';
        });
        return labels;
    }
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll('.datepicker').flatpickr({
            disableMobile: true,
            dateFormat: "Y-m-d",
            allowInput: true
        });
    });
</script>
<script>

    document.addEventListener("DOMContentLoaded", function () {

        // Total training amount chart
        var ctx = document.getElementById('trainingChart').getContext('2d');
        ctx.canvas.width = 1000;
        ctx.canvas.height = 200;

        var requestData = {!! json_encode($totalRequests) !!}

        var cfg = {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Training Requests',
                    borderColor: 'rgb(255, 100, 100)',
                    backgroundColor: 'rgba(255,50,50, 0.1)',
                    pointBackgroundColor: 'rgb(255,75,75)',
                    pointRadius: 1,
                    data: requestData,
                    fill: {
                        target: 'origin',
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'month',
                            tooltipFormat:'DD/MM/YYYY',
                        },
                        ticks: {
                            major: {
                                enabled: true,
                                fontStyle: 'bold'
                            },
                        },
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Requests'
                        },
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
            }
        };

        var chart = new Chart(ctx, cfg);

    });

</script>

<script>

    document.addEventListener("DOMContentLoaded", function () {

        // New request chart
        var newRequestsData = {!! json_encode($newRequests) !!}

        var datasets = [];
        for (const rating in newRequestsData) {
            datasets.push({
                label: rating,
                data: newRequestsData[rating]
            })
        }

        var barChartData = {
            labels: {!! json_encode($labels) !!},
            datasets: datasets
        };

        var mix = document.getElementById("newTrainingRequests").getContext('2d');
        var newTrainingRequests = new Chart(mix, {
            type: 'bar',
            data: barChartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            generateLabels: generateLegendLabelsWithTotal
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Note: One request may have multiple ratings shown indvidually in this graph'
                        }
                    },
                    y: {
                        stacked: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    });
</script>

<script>

    document.addEventListener("DOMContentLoaded", function () {

        // Completed requests chart
        var completedRequestsData = {!! json_encode($completedRequests) !!}

        var datasets = [];
        for (const rating in completedRequestsData) {
            datasets.push({
                label: rating,
                data: completedRequestsData[rating]
            })
        }

        var barChartData = {
            labels: {!! json_encode($labels) !!},
            datasets: datasets
        };

        var mix = document.getElementById("completedTrainingRequests").getContext('2d');
        var completedTrainingRequests = new Chart(mix, {
            type: 'bar',
            data: barChartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            generateLabels: generateLegendLabelsWithTotal
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Note: One request may have multiple ratings shown indvidually in this graph'
                        }
                    },
                    y: {
                        stacked: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

    });
</script>

<script>

    document.addEventListener("DOMContentLoaded", function () {

        // Closed requests chart
        var closedRequestsData = {!! json_encode($closedRequests) !!}

        var datasets = [];
        for (const rating in closedRequestsData) {
            datasets.push({
                label: rating,
                data: closedRequestsData[rating]
            })
        }

        var barChartData = {
            labels: {!! json_encode($labels) !!},
            datasets: datasets
        };

        var mix = document.getElementById("closedTrainingRequests").getContext('2d');
        var closedTrainingRequests = new Chart(mix, {
            type: 'bar',
            data: barChartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            generateLabels: generateLegendLabelsWithTotal
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Note: One request may have multiple ratings shown indvidually in this graph'
                        }
                    },
                    y: {
                        stacked: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    });
</script>

<script>

    document.addEventListener("DOMContentLoaded", function () {

        // Pass/fail rate for requests
        var passFailRequestsData = {!! json_encode($passFailRequests) !!}

        var barChartData = {
            labels: {!! json_encode($labels) !!},
            datasets: [{
                label: 'Failed',
                backgroundColor: 'rgb(200, 100, 100)',
                data: passFailRequestsData["FAILED"]
            }, {
                label: 'Passed',
                backgroundColor: 'rgb(100, 200, 100)',
                data: passFailRequestsData["PASSED"]
            }]

        };

        var mix = document.getElementById("TrainingPassFailRate").getContext('2d');
        var passFailTrainings = new Chart(mix, {
            type: 'bar',
            data: barChartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            generateLabels: generateLegendLabelsWithTotal
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Note: This graph only shows standard and fast-tracked CPTs excluding S1'
                        }
                    },
                    y: {
                        stacked: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    });
</script>

@endsection
