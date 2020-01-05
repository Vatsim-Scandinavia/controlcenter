@extends('layouts.app')

@section('title', 'Training overview')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Training overview</h1>

<div class="row">

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">Open training requests</h6> 
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
                                <th data-sortable="true" data-filter-control="select">State</th>
                                <th data-sortable="true" data-filter-control="input" data-visible-search="true">Vatsim ID</th>
                                <th data-sortable="true" data-filter-control="input">Name</th>
                                <th data-sortable="true" data-filter-control="select" data-filter-strict-search="true">Level</th>
                                <th data-sortable="true" data-filter-control="select">Type</th>
                                <th data-sortable="true" data-filter-control="input">Period</th>
                                <th data-sortable="true" data-filter-control="select">Country</th>
                                <th data-sortable="true" data-filter-control="input">Applied</th>
                                <th data-sortable="true" data-filter-control="select">Mentor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><i class="fas fa-graduation-cap text-success"></i>&ensp;<a href="/training/2">Awaiting exam</a></td>
                                <td><a href="/user/1300001">1300001</a></td>
                                <td><a href="/user/1300001">Test Testersen</a></td>
                                <td>S2 + S2 MAE ENGM</td>
                                <td><i class="fas fa-circle"></i>&ensp;Standard</td>
                                <td>25.01.20 - now</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td><a href="/user/1300001">Mentor Mentorsen</a></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-book-open text-success"></i>&ensp;<a href="/training/2">In progress</a></td>
                                <td><a href="/user/1300001">1300024</a></td>
                                <td><a href="/user/1300001">Test Riperino</a></td>
                                <td>S3</td>
                                <td><i class="fas fa-play"></i>&ensp;Prioritised</td>
                                
                                <td>25.01.20 - now</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td><a href="/user/1300001">Mentor Mentorsen</a></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-hourglass text-warning"></i>&ensp;<a href="/training/2">In queue</a></td>
                                <td><a href="/user/1300001">1300412</a></td>
                                <td><a href="/user/1300001">Squeeker Kidsen</a></td>
                                <td>C1</td>
                                <td><i class="fas fa-fast-forward"></i>&ensp;Fast-track</td>
                                <td>Not started</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-hourglass text-warning"></i>&ensp;<a href="/training/2">In queue</a></td>
                                <td><a href="/user/1300001">1300123</a></td>
                                <td><a href="/user/1300001">Norman Virus</a></td>
                                <td>ENOB Oceanic</td>
                                <td><i class="fas fa-sync"></i>&ensp;Refresh</td>
                                <td>Not started</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-pause"></i>&ensp;<a href="/training/2">Paused</a></td>
                                <td><a href="/user/1300001">1300031</a></td>
                                <td><a href="/user/1300001">Test Testersen</a></td>
                                <td>S2</td>
                                <td><i class="fas fa-exchange"></i>&ensp;Transfer</td>
                                <td>Not started</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-pause"></i>&ensp;<a href="/training/2">Paused</a></td>
                                <td><a href="/user/1300001">1300031</a></td>
                                <td><a href="/user/1300001">Test Testersen</a></td>
                                <td>S2</td>
                                <td><i class="fas fa-compress-arrows-alt"></i>&ensp;Familiarisation</td>
                                <td>Not started</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-graduation-cap text-success"></i>&ensp;<a href="/training/2">Awaiting exam</a></td>
                                <td><a href="/user/1300001">1300001</a></td>
                                <td><a href="/user/1300001">Test Testersen</a></td>
                                <td>S2 + S2 MAE ENGM</td>
                                <td><i class="fas fa-circle"></i>&ensp;Standard</td>
                                <td>25.01.20 - now</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td><a href="/user/1300001">Mentor Mentorsen</a></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-book-open text-success"></i>&ensp;<a href="/training/2">In progress</a></td>
                                <td><a href="/user/1300001">1300024</a></td>
                                <td><a href="/user/1300001">Test Riperino</a></td>
                                <td>S3</td>
                                <td><i class="fas fa-play"></i>&ensp;Prioritised</td>
                                
                                <td>25.01.20 - now</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td><a href="/user/1300001">Mentor Mentorsen</a></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-hourglass text-warning"></i>&ensp;<a href="/training/2">In queue</a></td>
                                <td><a href="/user/1300001">1300412</a></td>
                                <td><a href="/user/1300001">Squeeker Kidsen</a></td>
                                <td>C1</td>
                                <td><i class="fas fa-fast-forward"></i>&ensp;Fast-track</td>
                                <td>Not started</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-hourglass text-warning"></i>&ensp;<a href="/training/2">In queue</a></td>
                                <td><a href="/user/1300001">1300123</a></td>
                                <td><a href="/user/1300001">Norman Virus</a></td>
                                <td>ENOB Oceanic</td>
                                <td><i class="fas fa-sync"></i>&ensp;Refresh</td>
                                <td>Not started</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-pause"></i>&ensp;<a href="/training/2">Paused</a></td>
                                <td><a href="/user/1300001">1300031</a></td>
                                <td><a href="/user/1300001">Test Testersen</a></td>
                                <td>S2</td>
                                <td><i class="fas fa-exchange"></i>&ensp;Transfer</td>
                                <td>Not started</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-pause"></i>&ensp;<a href="/training/2">Paused</a></td>
                                <td><a href="/user/1300001">1300031</a></td>
                                <td><a href="/user/1300001">Test Testersen</a></td>
                                <td>S2</td>
                                <td><i class="fas fa-compress-arrows-alt"></i>&ensp;Familiarisation</td>
                                <td>Not started</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-graduation-cap text-success"></i>&ensp;<a href="/training/2">Awaiting exam</a></td>
                                <td><a href="/user/1300001">1300001</a></td>
                                <td><a href="/user/1300001">Test Testersen</a></td>
                                <td>S2 + S2 MAE ENGM</td>
                                <td><i class="fas fa-circle"></i>&ensp;Standard</td>
                                <td>25.01.20 - now</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td><a href="/user/1300001">Mentor Mentorsen</a></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-book-open text-success"></i>&ensp;<a href="/training/2">In progress</a></td>
                                <td><a href="/user/1300001">1300024</a></td>
                                <td><a href="/user/1300001">Test Riperino</a></td>
                                <td>S3</td>
                                <td><i class="fas fa-play"></i>&ensp;Prioritised</td>
                                
                                <td>25.01.20 - now</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td><a href="/user/1300001">Mentor Mentorsen</a></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-hourglass text-warning"></i>&ensp;<a href="/training/2">In queue</a></td>
                                <td><a href="/user/1300001">1300412</a></td>
                                <td><a href="/user/1300001">Squeeker Kidsen</a></td>
                                <td>C1</td>
                                <td><i class="fas fa-fast-forward"></i>&ensp;Fast-track</td>
                                <td>Not started</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-hourglass text-warning"></i>&ensp;<a href="/training/2">In queue</a></td>
                                <td><a href="/user/1300001">1300123</a></td>
                                <td><a href="/user/1300001">Norman Virus</a></td>
                                <td>ENOB Oceanic</td>
                                <td><i class="fas fa-sync"></i>&ensp;Refresh</td>
                                <td>Not started</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-pause"></i>&ensp;<a href="/training/2">Paused</a></td>
                                <td><a href="/user/1300001">1300031</a></td>
                                <td><a href="/user/1300001">Test Testersen</a></td>
                                <td>S2</td>
                                <td><i class="fas fa-exchange"></i>&ensp;Transfer</td>
                                <td>Not started</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-pause"></i>&ensp;<a href="/training/2">Paused</a></td>
                                <td><a href="/user/1300001">1300031</a></td>
                                <td><a href="/user/1300001">Test Testersen</a></td>
                                <td>S2</td>
                                <td><i class="fas fa-compress-arrows-alt"></i>&ensp;Familiarisation</td>
                                <td>Not started</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">Closed training requests</h6> 
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
                                <th data-sortable="true" data-filter-control="select">State</th>
                                <th data-sortable="true" data-filter-control="input" data-visible-search="true">Vatsim ID</th>
                                <th data-sortable="true" data-filter-control="input">Name</th>
                                <th data-sortable="true" data-filter-control="select" data-filter-strict-search="true">Level</th>
                                <th data-sortable="true" data-filter-control="select">Type</th>
                                <th data-sortable="true" data-filter-control="input">Period</th>
                                <th data-sortable="true" data-filter-control="select">Country</th>
                                <th data-sortable="true" data-filter-control="input">Applied</th>
                                <th data-sortable="true" data-filter-control="input">Closed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><i class="fas fa-check text-success"></i>&ensp;<a href="/training/2">Completed</a></td>
                                <td><a href="/user/1300001">1300001</a></td>
                                <td><a href="/user/1300001">Test Testersen</a></td>
                                <td>S2 + S2 MAE ENGM</td>
                                <td><i class="fas fa-circle"></i>&ensp;Standard</td>
                                <td>24.01.20 - 26.02.20</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>24.01.20</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-check text-success"></i>&ensp;<a href="/training/2">Completed</a></td>
                                <td><a href="/user/1300001">1300024</a></td>
                                <td><a href="/user/1300001">Test Riperino</a></td>
                                <td>S3</td>
                                <td><i class="fas fa-sync"></i>&ensp;Refresh</td>
                                <td>24.01.20 - 26.02.20</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>24.01.20</td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="fas fa-ban text-danger"></i>&ensp;<a href="/training/2">Closed by staff</a>
                                    &nbsp;<div class="fa fa-comment-alt-lines text-primary" data-placement="right" data-html="true" title="Closed due to the member breaking CoC multiple times during training."></div>
                                </td>
                                <td><a href="/user/1300001">1300412</a></td>
                                <td><a href="/user/1300001">Squeeker Kidsen</a></td>
                                <td>C1</td>
                                <td><i class="fas fa-fast-forward"></i>&ensp;Fast-track</td>
                                <td>Never started</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>24.01.20</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-ban text-danger"></i>&ensp;<a href="/training/2">Closed by student</a></td>
                                <td><a href="/user/1300001">1300123</a></td>
                                <td><a href="/user/1300001">Norman Virus</a></td>
                                <td>ENOB Oceanic</td>
                                <td><i class="fas fa-circle"></i>&ensp;Standard</td>
                                <td>Never started</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>24.01.20</td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="fas fa-ban text-danger"></i>&ensp;<a href="/training/2">Closed by system</a>
                                    &nbsp;<div class="fa fa-comment-alt-lines text-primary" data-placement="right" data-html="true" title="Did not confirm training interest within 2 weeks."></div>
                                </td>
                                <td><a href="/user/1300001">1300031</a></td>
                                <td><a href="/user/1300001">Test Testersen</a></td>
                                <td>S2</td>
                                <td><i class="fas fa-exchange"></i>&ensp;Transfer</td>
                                <td>Never started</td>
                                <td>Norway</td>
                                <td>24.01.20</td>
                                <td>24.01.20</td>
                            </tr>
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
        $('div').tooltip();
    })
</script>
@endsection