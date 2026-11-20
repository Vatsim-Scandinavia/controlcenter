@extends('layouts.app')

@section('title', 'Feedback')
@section('content')

<div class="row">
    <div class="col-xl-6 col-lg-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Submit Feedback
                </h6>
            </div>
            <div class="card-body">
                <p>Which area is your feedback about?</p>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    @foreach($areas as $area)
                        <a href="{{ route('feedback.create', $area) }}" class="btn btn-primary">{{ $area->name }}</a>
                    @endforeach
                </div>

                <p class="mb-2 text-muted">Not related to a specific area?</p>
                <a href="{{ route('feedback.create.general') }}" class="btn btn-outline-secondary">General feedback</a>
            </div>
        </div>
    </div>
</div>

@endsection
