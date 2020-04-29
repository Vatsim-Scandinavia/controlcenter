@extends('layouts.app')

@section('title', 'Create Booking')

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
    <div class="col-xl-6 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    New Training Requests (7 days)
                </h6> 
            </div>
            <div class="card-body">
                <canvas id="trainingRequestChart" height="200"></canvas>
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

    var mix = document.getElementById("trainingRequestChart").getContext('2d');
    var trainingRequestChart = new Chart(mix, {
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

@endsection