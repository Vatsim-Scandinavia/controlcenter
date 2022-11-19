@extends('layouts.app')

@section('title', 'Member Overview')
@section('content')

<div class="row">

    <div class="col-xl-12 col-md-12 mb-12">

        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle"></i>&nbsp;&nbsp;This list only shows division members who have logged into Handover, and therefore might differ from VATSIM CERT.
        </div>

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">VAT{{ Config::get('app.owner_short') }} Members</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-page-size="15"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true">
                        <thead class="thead-light">
                            <tr>
                                <th data-field="id" data-sortable="true" data-filter-control="input" data-visible-search="true">Vatsim ID</th>
                                <th data-field="firstname" data-sortable="true" data-filter-control="input">First Name</th>
                                <th data-field="lastname" data-sortable="true" data-filter-control="input">Last Name</th>
                                <th data-field="rating" data-sortable="true" data-filter-control="select" data-filter-strict-search="true">ATC Rating</th>
                                <th data-field="atcactive" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">ATC Active</th>
                                <th>ATC Hours</th>
                                <th>Last login</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                @if($user->subdivision == Config::get('app.owner_short') && $user->isVisiting() == false)
                                    <tr>
                                        <td><a href="{{ route('user.show', $user->id) }}">{{ $user->id }}</a></td>
                                        <td>{{ $user->first_name }}</td>
                                        <td>{{ $user->last_name }}</td>
                                        <td>{{ $user->rating_short }}</td>
                                        <td><i class="fas fa-{{ $user->active ? 'check' : 'times' }}"></i> {{ $user->active ? 'Yes' : 'No' }}</td>
                                        <td>{{ isset($userHours->where('user_id', $user->id)->first()->atc_hours) ? $userHours->where('user_id', $user->id)->first()->atc_hours : 'N/A' }}</td>
                                        <td>{{ $user->last_login }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection