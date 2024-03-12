@extends('layouts.app')

@section('title', 'Member Overview')

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">{{ Config::get('app.owner_name_short') }} Members</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-page-size="15"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true">
                        <thead class="table-light">
                            <tr>
                                <th data-field="id" data-sortable="true" data-filter-control="input" data-visible-search="true">Vatsim ID</th>
                                <th data-field="firstname" data-sortable="true" data-filter-control="input">First Name</th>
                                <th data-field="lastname" data-sortable="true" data-filter-control="input">Last Name</th>
                                <th data-field="cc" data-sortable="true" data-filter-control="select" data-filter-strict-search="true">Registered in CC</th>
                                <th data-field="rating" data-sortable="true" data-filter-control="select" data-filter-strict-search="true">ATC Rating</th>
                                <th data-field="atcactive" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">ATC Active</th>
                                <th data-field="atchours" data-sortable="true">ATC Hours</th>
                                <th>Last rating change</th>
                                <th>Joined VATSIM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>

                                    @if(isset($user['cc_data']))
                                        <td><a href="{{ route('user.show', $user['id']) }}">{{ $user['id'] }}</a></td>
                                        <td>{{ $user['name_first'] }}</td>
                                        <td>{{ $user['name_last'] }}</td>
                                        <td>Yes</td>
                                        <td>{{ App\Helpers\VatsimRating::from($user['rating'])->name }}</td>
                                        <td><i class="fas fa-{{ $user['active'] ? 'check' : 'times' }}"></i> {{ $user['active'] ? 'Yes' : 'No' }}</td>
                                        <td>{{ isset($user['hours']) ? round($user['hours']) : 'N/A' }}</td>
                                        <td>{{ isset($user['lastratingchange']) ? Carbon\Carbon::create($user['lastratingchange'])->toEuropeanDate() : 'N/A' }}</td>
                                        <td>{{ Carbon\Carbon::create($user['reg_date'])->toEuropeanDate() }}</td>
                                    @else
                                        <td>{{ $user['id'] }}</td>
                                        <td>{{ $user['name_first'] }}</td>
                                        <td>{{ $user['name_last'] }}</td>
                                        <td>No</td>
                                        <td>{{ App\Helpers\VatsimRating::from($user['rating'])->name }}</td>
                                        <td>N/A</td>
                                        <td>N/A</td>
                                        <td>{{ isset($user['lastratingchange']) ? Carbon\Carbon::create($user['lastratingchange'])->toEuropeanDate() : 'N/A' }}</td>
                                        <td>{{ Carbon\Carbon::create($user['reg_date'])->toEuropeanDate() }}</td>
                                    @endif

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
