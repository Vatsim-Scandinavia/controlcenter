@extends('layouts.app')

@section('title', 'Activity Logs')

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Logs</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-page-size="100"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true">
                        <thead class="table-light">
                            <tr>
                                <th data-field="date" data-sortable="true" data-sorter="tableSortDates" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false" data-filter-order-by="desc">Time</th>
                                <th data-field="type" data-sortable="false" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">Type</th>
                                <th data-field="category" data-sortable="false" data-filter-control="select">Category</th>
                                <th data-field="id" data-sortable="false" data-filter-control="input" data-visible-search="true">Who</th>
                                <th data-field="message" data-sortable="false" data-filter-control="input">What</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($log->created_at)->toEuropeanDateTime() }}</td>
                                <td><span class="text-{{ strtolower($log->type) }}">{{ $log->type }}</span></td>
                                <td>{{ $log->category }}</td>
                                <td>
                                    @if(isset($log->user_id))
                                        <a href="{{ route('user.show', $log->user_id) }}">{{ $log->user->name }} ({{ $log->user_id }})</a>
                                    @else
                                        SYSTEM
                                    @endif
                                </td>
                                <td>{{ $log->message }}</td>
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
