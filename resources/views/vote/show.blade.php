@extends('layouts.app')

@section('title', 'Votes')
@section('content')

<div class="row">

    <div class="col-xl-6 col-md-6 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Cast your vote</h6>
            </div>
            <div class="card-body">
                <h3>{{ $vote->question }}</h3>

                @can('vote', $vote)

                    <form action="{{ route('vote.update', $vote->id) }}" method="POST">
                        @method('PATCH')
                        @csrf

                        @foreach( $vote->option as $votefor )
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="vote" id="{{ $votefor->option }}" value="{{ $votefor->id }}">
                                <label class="form-check-label" for="{{ $votefor->option }}">
                                    {{ $votefor->option }}
                                </label>
                            </div>
                        @endforeach
                        @error('vote')
                            <span class="text-danger">{{ $errors->first('vote') }}</span>
                        @enderror

                        <br>
                        <p class="text-muted">Your vote is secret and can not be traced. The vote is final and cannot be changed.</p>
                        <button type="submit" class="btn btn-success">Submit Vote</button>

                    </form>

                @else
                    <p class="text-danger">{{ Gate::inspect('vote', $vote)->message() }}</p>
                @endcan

            </div>
        </div>
    </div>

    <div class="col-xl-6 col-md-6 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Vote summary</h6>
            </div>
            <div class="card-body">

                @if ($vote->closed)

                    <h3>Results</h3>
                    <canvas id="voteResults"></canvas>

                @else

                    <p>Summary will be publicly available once the vote closes at {{ \Carbon\Carbon::create($vote->end_at)->toEuropeanDateTime() }}</p>

                @endif

            </div>
        </div>
    </div>


</div>
@endsection

@section('js')
@vite('resources/js/chart.js')
<script>

    document.addEventListener("DOMContentLoaded", function () {

        var vote = {!! json_encode($vote->option) !!};

        var voteOption = [];
        var voteVotes = [];
        for (i = 0; i < vote.length; i++) {
            voteOption.push(vote[i]['option']);
            voteVotes.push(vote[i]['voted']);
        }

        var barChartData = {
            labels: voteOption,
            datasets: [{
                label: 'Votes',
                backgroundColor: 'rgb(200, 100, 100)',
                data: voteVotes,
            }]

        };

        var mix = document.getElementById("voteResults").getContext('2d');
        var voteResults = new Chart(mix, {
            type: 'bar',
            data: barChartData,
            options: {
                tooltips: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        ticks: {
                            beginAtZero: true,
                            stepSize: 1,
                        }
                    }
                },
                responsive: true,
            }
        });
    });

</script>
@endsection
