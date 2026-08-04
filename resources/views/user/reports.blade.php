@extends('layouts.app')

@section('title', 'Reports Archive')
@section('content')

<div class="row">

    <div class="col-xl-5 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">{{ $user->first_name }}'s Reports</h6>
            </div>
            <div class="card-body p-0">
                <div class="accordion" id="reportAccordion">
                    @if ($reportsAndExams->count() == 0)
                        <div class="card-text text-primary p-3">
                            No training reports yet or you don't have permission to view them.
                        </div>
                    @else

                        @foreach($reportsAndExams as $reportModel)
                            @if(is_a($reportModel, '\App\Models\TrainingReport'))
                                <x-training.training-report :report="$reportModel" show-context />
                            @else
                                <x-training.exam-report :report="$reportModel" show-context />
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
