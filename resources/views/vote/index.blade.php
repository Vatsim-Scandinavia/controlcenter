@extends('layouts.app')

@section('title', 'Votes')
@section('content')

<div class="row">

    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Overview</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>Question</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Status</th>
                                <th>Only for ATC Active</th>
                                <th>Only for {{ config('app.owner_name_short') }} members</th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach($votes as $vote)

                                <tr>
                                    <td><a href="{{ route('vote.show', $vote->id) }}">{{ $vote->question }}</a></td>
                                    <td>{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $vote->created_at)->toEuropeanDateTime() }}</td>
                                    <td>{{ \Carbon\Carbon::create($vote->end_at)->toEuropeanDateTime() }}</td>
                                    <td>{{ $vote->closed ? "Closed" : "Accepting answers" }}</td>
                                    <td><i class="fas fa-{{ $vote->require_active ? "check" : "times" }}"></i></td>
                                    <td><i class="fas fa-{{ $vote->require_our_member ? "check" : "times" }}"></i></td>
                                </tr>

                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="align-items-left">
            <a href="{{ route('vote.create') }}" class="btn btn-success">Create new vote</a>
        </div>
    </div>

</div>
@endsection
