@extends('layouts.app')

@section('title', $title)

@section('content')

    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-6">
            <div class="card shadow">
                <div class="card-header bg-primary py-3">
                    <h6 class="m-0 fw-bold text-white">Select an area to continue</h6>
                </div>
                <div class="card-body border-bottom">
                    <p class="text-muted mb-0">You have access to specific areas only. Select one to continue.</p>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($areas as $area)
                        <a href="{{ route($route, $area->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            {{ $area->name }}
                            <i class="fas fa-chevron-right text-gray-700"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

@endsection
