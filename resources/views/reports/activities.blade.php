@extends('layouts.app')

@section('title', 'Training Activities')
@section('title-extension')
    <div class="dropdown show" style="display: inline;">
        <a class="btn btn-sm btn-primary dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Filter: {{ $filterName }}
        </a>
    
        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
            @if(\Auth::user()->isAdmin())
                <a class="dropdown-item" href="{{ route('reports.activities') }}">All Areas</a>
            @endif
            @foreach($areas as $area)
                @if(\Auth::user()->isModeratorOrAbove($area))
                    <a class="dropdown-item" href="{{ route('reports.activities.area', $area->id) }}">{{ $area->name }}</a>
                @endif
            @endforeach 
        </div>
    </div>
@endsection
@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Training Activities
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-cookie="true"
                        data-cookie-id-table="activities"
                        data-cookie-expire="90d"
                        data-page-size="25"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true">
                        <thead class="thead-light">
                            <tr>
                                <th data-field="id" data-sortable="false" data-filter-control="input">Training</th>
                                <th data-field="who" data-sortable="false" data-filter-control="input">Who</th>
                                <th data-field="mentor" data-sortable="false" data-filter-control="input">Activity</th>
                                <th data-field="area" data-sortable="false" data-filter-control="select">Area</th>
                                <th data-field="level" data-sortable="false">When</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activities as $activity)
                                <tr>
                                    <td>
                                        <i class="{{ $statuses[$activity->training->status]["icon"] }} text-{{  $statuses[$activity->training->status]["color"] }}"></i>
                                        <a href="{{ route('training.show', $activity->training) }}">{{ $activity->training->user->name }}'s {{ $activity->training->getInlineRatings() }}</a>
                                    </td>
                                    <td>
                                        @isset($activity->triggered_by_id)
                                            {{ \App\Models\User::find($activity->triggered_by_id)->name }}
                                        @else
                                            System
                                        @endisset
                                    </td>
                                    <td>

                                        @if($activity->type == "STATUS" || $activity->type == "TYPE")
                                            <i class="fas fa-right-left"></i>
                                        @elseif($activity->type == "MENTOR")
                                            @if($activity->new_data)
                                                <i class="fas fa-user-plus"></i>
                                            @elseif($activity->old_data)
                                                <i class="fas fa-user-minus"></i>
                                            @endif
                                        @elseif($activity->type == "PAUSE")
                                            <i class="fas fa-circle-pause"></i>
                                        @elseif($activity->type == "ENDORSEMENT")
                                            <i class="fas fa-check-square"></i>
                                        @elseif($activity->type == "COMMENT")
                                            <i class="fas fa-comment"></i>
                                        @endif

                                        @if($activity->type == "STATUS")
                                            @if(($activity->new_data == -2 || $activity->new_data == -4) && isset($activity->comment))
                                                Status changed from <span class="badge badge-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->old_data]["text"] }}</span>
                                            to <span class="badge badge-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->new_data]["text"] }}</span>
                                            with reason <span class="badge badge-light">{{ $activity->comment }}</span>
                                            @else
                                                Status changed from <span class="badge badge-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->old_data]["text"] }}</span>
                                            to <span class="badge badge-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->new_data]["text"] }}</span>
                                            @endif
                                        @elseif($activity->type == "TYPE")
                                            Training type changed from <span class="badge badge-light">{{ \App\Http\Controllers\TrainingController::$types[$activity->old_data]["text"] }}</span>
                                            to <span class="badge badge-light">{{ \App\Http\Controllers\TrainingController::$types[$activity->new_data]["text"] }}</span>
                                        @elseif($activity->type == "MENTOR")
                                            @if($activity->new_data)
                                                <span class="badge badge-light">{{ \App\Models\User::find($activity->new_data)->name }}</span> assigned as mentor
                                            @elseif($activity->old_data)
                                            <span class="badge badge-light">{{ \App\Models\User::find($activity->old_data)->name }}</span> removed as mentor
                                            @endif
                                        @elseif($activity->type == "PAUSE")
                                            @if($activity->new_data)
                                                Training paused
                                            @else
                                                Training unpaused
                                            @endif
                                        @elseif($activity->type == "ENDORSEMENT")
                                            @if(\App\Models\Endorsement::find($activity->new_data) !== null)
                                                @empty($activity->comment)
                                                    <span class="badge badge-light">
                                                        {{ str(\App\Models\Endorsement::find($activity->new_data)->type)->lower()->ucfirst() }} endorsement
                                                    </span> granted, valid to 
                                                    <span class="badge badge-light">
                                                        @isset(\App\Models\Endorsement::find($activity->new_data)->valid_to)
                                                            {{ \App\Models\Endorsement::find($activity->new_data)->valid_to->toEuropeanDateTime() }}
                                                        @else
                                                            Forever
                                                        @endisset
                                                    </span>
                                                @else
                                                    <span class="badge badge-light">
                                                        {{ str(\App\Models\Endorsement::find($activity->new_data)->type)->lower()->ucfirst() }} endorsement
                                                    </span> granted, valid to 
                                                    <span class="badge badge-light">
                                                        @isset(\App\Models\Endorsement::find($activity->new_data)->valid_to)
                                                            {{ \App\Models\Endorsement::find($activity->new_data)->valid_to->toEuropeanDateTime() }}
                                                        @else
                                                            Forever
                                                        @endisset
                                                    </span>
                                                    for positions: 
                                                    @foreach(explode(',', $activity->comment) as $p)
                                                        <span class="badge badge-light">{{ $p }}</span>
                                                    @endforeach
                                                @endempty
                                            @endif
                                        @elseif($activity->type == "COMMENT")
                                            {!! nl2br($activity->comment) !!}

                                            @if($activity->created_at != $activity->updated_at)
                                                <span class="text-muted">(edited)</span>
                                            @endif
                                        @endif

                                    </td>
                                    <td>
                                        {{ $activity->training->area->name }}
                                    </td>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="{{ $activity->created_at->toEuropeanDateTime() }}">
                                            {{ $activity->created_at->diffForHumans() }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@section('js')
<script>
    //Activate bootstrap tooltips
    $(document).ready(function() {
        $("body").tooltip({ selector: '[data-toggle=tooltip]', delay: {"show": 150, "hide": 0} });
    });
</script>
@endsection