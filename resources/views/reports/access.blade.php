@extends('layouts.app')

@section('title', 'Access Report')

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">User's access</h6>
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
                                <th data-field="id" data-sortable="true" data-filter-control="input" data-visible-search="true">Vatsim ID</th>
                                <th data-field="name" data-sortable="true" data-filter-control="input">Name</th>
                                @foreach($areas as $area)
                                    <th data-field="access-{{ $area->id }}" data-sortable="true" data-filter-control="input">Access {{ $area->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td><a href="{{ route('user.show', $user->id) }}">{{ $user->id }}</a></td>
                                <td><a href="{{ route('user.show', $user->id) }}">{{ $user->name }}</a></td>
                                @foreach($areas as $area)
                                    <td>
                                        @foreach($user->groups as $key => $group)
                                            @if($group->pivot->area_id == $area->id)
                                                {{ $group->name }}<br>
                                            @endif
                                        @endforeach
                                    </td>
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
