@extends('layouts.app')

@section('title', 'New Training Report')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">
        New Training Report for {{ $training->user->firstName }}'s training for
        @foreach($training->ratings as $rating)
            @if ($loop->last)
                {{ $rating->name }}
            @else
                {{ $rating->name . " + " }}
            @endif
        @endforeach
    </h1>

    <div class="row">
        <div class="col-xl-12 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-white">
                        New Training Report
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-8 col-md-12 mb-12">
                            <form action="{{ route('training.report.store', ['training' => $training->id]) }}" method="POST">
                                @csrf
                                <div class="row flex-row">
                                    <label for="position"><span class="font-weight-bold">Position: </span></label>
                                    <input name="position" id="position" type="text" placeholder="Position" class="ml-2">
                                </div>

                                <div class="row">
                                    <label for="content"><span class="font-weight-bold col-12 px-2 mx-2">Content: </span></label>
                                    <textarea rows="8" placeholder="Content..." name="content" class="mt-2 col-10 px-2 mx-2"></textarea>
                                </div>
                                <div class="row">
                                    <label for="draft"><span class="font-weight-bold">Draft?</span></label>
                                    <input type="checkbox" name="draft" id="draft">
                                </div>
                                <div class="row mt-8">
                                    <button type="submit" class="btn btn-primary ml-2 mx-1">Save report</button>
                                </div>
                            </form>
                        </div>
                        <div class="col-xl-4 col-md-12 border-left-primary">
                            <p><span class="font-weight-bold">Attachments:</span></p>
                            <p>

                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection
