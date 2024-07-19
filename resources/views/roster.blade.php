@extends('layouts.app')

@section('title', 'ATC Roster '.$area->name)

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Active controllers</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-page-size="25"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true"
                        data-sort-select-options="true"
                        >
                        <thead class="table-light">
                            <tr>
                                <th data-field="member" class="w-50" data-sortable="true" data-filter-control="input">Member</th>
                                <th data-field="rating" data-sortable="true" data-filter-control="select">Rating</th>
                                <th data-field="active" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">ATC Active</th>
                                @foreach($ratings as $r)
                                    <th data-field="{{ $r->id }}" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">{{ $r->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $u)
                                <tr>
                                    <td>
                                        @can('view', $u)
                                            <a href="{{ route('user.show', $u->id) }}">{{ $u->name }} ({{ $u->id }})</a>
                                        @else
                                            {{ $u->name }} ({{ $u->id }})
                                        @endcan
                                    </td>
                                    <td>{{ $u->rating_short }} {{ $u->rating_long }}</td>
                                    <td class="text-center text-white {{ $u->isAtcActive() || $u->isVisiting() ? 'bg-success' : 'bg-danger' }}">
                                        @if($u->isAtcActive())
                                            <i class="fas fa-check-circle"></i><span class="d-none">Yes</span>
                                        @elseif($u->isVisiting())
                                            <i class="far fa-check-circle"></i>
                                            Visiting
                                        @else
                                            <i class="fas fa-times-circle"></i><span class="d-none">Inactive</span>
                                        @endif
                                    </td>

                                    @foreach($ratings as $r)
                                        @php $found = false; @endphp
                                        @foreach($u->endorsements->where('type', 'FACILITY')->where('expired', false)->where('revoked', false) as $e)
                                            @if($e->ratings->first()->id == $r->id)
                                                <td class="text-center bg-success text-white">
                                                    <i class="fas fa-check-circle"></i><span class="d-none">Approved</span>
                                                </td>
                                                @php $found = true; @endphp
                                                @break
                                            @endif
                                        @endforeach

                                        @if(!$found)
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