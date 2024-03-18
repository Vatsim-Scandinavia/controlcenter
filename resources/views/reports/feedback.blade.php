@extends('layouts.app')

@section('title', 'Feedback')

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Feedback
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-cookie="true"
                        data-cookie-id-table="mentors"
                        data-cookie-expire="90d"
                        data-page-size="25"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true">
                        <thead class="table-light">
                            <tr>
                                <th data-field="received" data-sortable="true">Received</th>
                                <th data-field="submitter" data-sortable="true" data-filter-control="input">Submitter</th>
                                <th data-field="controller" data-sortable="true" data-filter-control="select">Controller</th>
                                <th data-field="position" data-sortable="true" data-filter-control="select">Position</th>
                                <th data-field="feedback" data-sortable="false" data-filter-control="input">Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($feedback as $f)
                                <tr>
                                    <td>{{ $f->created_at->toEuropeanDateTime() }}</td>
                                    <td><a href="{{ route('user.show', $f->submitter->id) }}">{{ $f->submitter->name }} ({{ $f->submitter_user_id }})</a></td>
                                    <td>
                                        @isset($f->referenceUser)
                                            <a href="{{ route('user.show', $f->referenceUser) }}">{{ $f->referenceUser->name }} ({{ $f->referenceUser->id }})</a>
                                        @else
                                            N/A
                                        @endisset
                                    </td>
                                    <td>
                                        @isset($f->referencePosition)
                                            {{ $f->referencePosition->callsign }}
                                        @else
                                            N/A
                                        @endisset
                                    </td>
                                    <td>
                                        {!! nl2br($f->feedback) !!}
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