@extends('layouts.app')

@section('title', 'ATC Activity')
@section('content')

<div class="row">

    <div class="col-xl-6 col-md-12 mb-12">
        <p>Coming soon</p>
    </div>

    <!--
    <div class="col-xl-6 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">Qualified controllers</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-toggle="table"
                        data-pagination="true"
                        data-strict-search="true"
                        data-filter-control="true">
                        <thead class="thead-light">
                            <tr>
                                <th data-sortable="true" data-filter-control="input" data-visible-search="true">Vatsim ID</th>
                                <th data-sortable="true" data-filter-control="input">Name</th>
                                <th data-sortable="true" data-filter-control="select" data-filter-strict-search="true">Rating</th>
                                <th data-sortable="true" data-filter-control="select" data-filter-strict-search="true">Active</th>
                                <th data-sortable="true" data-filter-control="select">Hours this calendar year</th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach($controllers as $controller)
                                <tr>
                                    <td><a href="/user/{{ $controller->id }}">{{ $controller->id }}</a></td>
                                    <td><a href="/user/{{ $controller->id }}">{{ $controller->name }}</a></td>
                                    <td>{{ $controller->ratingShort }}</td>
                                    <td>Yes</td>
                                    <td>10 hours</td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">Not qualified controllers</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-toggle="table"
                        data-pagination="true"
                        data-strict-search="true"
                        data-filter-control="true">
                        <thead class="thead-light">
                            <tr>
                                <th data-sortable="true" data-filter-control="input" data-visible-search="true">Vatsim ID</th>
                                <th data-sortable="true" data-filter-control="input">Name</th>
                                <th data-sortable="true" data-filter-control="select" data-filter-strict-search="true">Rating</th>
                                <th data-sortable="true" data-filter-control="select" data-filter-strict-search="true">Active</th>
                                <th data-sortable="true" data-filter-control="select">Hours this calendar year</th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach($controllers as $controller)
                                <tr>
                                    <td><a href="/user/{{ $controller->id }}">{{ $controller->id }}</a></td>
                                    <td><a href="/user/{{ $controller->id }}">{{ $controller->name }}</a></td>
                                    <td>{{ $controller->ratingShort }}</td>
                                    <td>Yes</td>
                                    <td>10 hours</td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    -->

</div>
@endsection

@section('js')
<script>
    //Activate bootstrap tooltips
    $(document).ready(function() {
        $('div').tooltip();
    })
</script>
@endsection