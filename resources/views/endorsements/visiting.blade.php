@extends('layouts.app')

@section('title', 'Visiting')
@section('title-flex')
    <div>
        @can('endorsements.visiting.manage')
            <a href="{{ route('endorsements.create') }}" class="btn btn-outline-success"><i class="fas fa-plus"></i> Add new endorsement</a>
        @endcan
    </div>
@endsection

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Visiting Endorsements</h6> 
            </div>        
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-page-size="25"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true"
                        >
                        <thead class="table-light">
                            <tr>
                                <th data-field="student" class="w-25" data-sortable="true" data-filter-control="input">Member</th>
                                <th data-field="rating" data-sortable="true" data-filter-control="select">Rating</th>
                                <th data-field="activity" data-sortable="true" data-filter-control="select">Activity</th>
                                <th data-field="hours" data-sortable="true" title="Local hours within the configured activity qualification period">Local hours</th>
                                <th data-field="last-online" data-sortable="true">Last local session</th>
                                <th data-field="expires" data-sortable="true">Visiting expiry</th>
                                @foreach($areas as $a)
                                    <th data-field="{{ $a->id }}" data-sortable="true" data-filter-control="input" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">{{ $a->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($endorsements as $visitingEndorsement)
                                <tr>
                                    <td>
                                        @can('view', $visitingEndorsement->user)
                                            <a href="{{ route('user.show', $visitingEndorsement->user->id) }}">{{ $visitingEndorsement->user->name }} ({{ $visitingEndorsement->user->id }})</a>
                                        @else
                                            {{ $visitingEndorsement->user->name }} ({{ $visitingEndorsement->user->id }})
                                        @endcan
                                    </td>
                                    <td>{{ $visitingEndorsement->ratings->whereNotNull('vatsim_rating')->first()->name }}</td>
                                    @php
                                        $localActivities = $visitingEndorsement->user->atcActivity->whereIn('area_id', $visitingEndorsement->areas->modelKeys());
                                        $lastOnline = $localActivities->max('last_online');
                                    @endphp
                                    <td>{{ $visitingEndorsement->user->isAtcActive() ? 'Active' : 'Inactive' }}</td>
                                    <td>{{ number_format($localActivities->sum('hours_in_period'), 1) }}</td>
                                    <td>{{ $lastOnline ? $lastOnline->format('Y-m-d') : 'Never' }}</td>
                                    <td>{{ $visitingEndorsement->valid_to?->format('Y-m-d') ?? 'No expiry' }}</td>

                                    @foreach($areas as $a)
                                        @php $count = 0; @endphp

                                        @foreach($visitingEndorsement->areas as $endorsedArea)
                                            @if($endorsedArea->id == $a->id)

                                                @php $count++; @endphp

                                                <td class="text-center bg-success text-white">
                                                    <i class="fas fa-check-circle"></i><span class="d-none">Approved</span>

                                                    {{-- Display the FACILITY endorsements connected to this area --}}
                                                    @foreach($endorsedArea->ratings->whereNull('vatsim_rating') as $areaRating)
                                                        @foreach($visitingEndorsement->user->endorsements->where('type', 'FACILITY')->where('revoked', false)->where('expired', false) as $facilityEndorsement)
                                                            @if($areaRating->id == $facilityEndorsement->ratings->first()->id)
                                                                <small class="d-block">{{ $areaRating->name }}</small>
                                                            @endif
                                                        @endforeach
                                                    @endforeach
                                                </td>
                                            @endif
                                        @endforeach

                                        @if(!$count)
                                            <td></td>
                                        @endif

                                    @endforeach
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
