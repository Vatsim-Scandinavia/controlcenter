@extends('layouts.app')

@section('title', 'Training overview')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Training overview</h1>

<div class="row">

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Open training requests</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="thead-dark">
                            <tr>
                                <th>State</th>
                                <th>Vatsim ID</th>
                                <th>Name</th>
                                <th>Level</th>
                                <th>Type</th>
                                <th>Period</th>
                                <th>Country</th>
                                <th class="text-center">English only</th>
                                <th>Application date</th>
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
                                <td class="text-center"><i class="fas fa-times"></i></td>
                                <td>24.01.2020</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-book-open text-success"></i>&ensp;<a href="/training/2">In progress</a></td>
                                <td><a href="/user/1300001">1300024</a></td>
                                <td><a href="/user/1300001">Test Riperino</a></td>
                                <td>S3</td>
                                <td><i class="fas fa-sync"></i>&ensp;Refresh</td>
                                <td>25.01.20 - now</td>
                                <td>Norway</td>
                                <td class="text-center"><i class="fas fa-times"></i></td>
                                <td>24.01.2020</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-hourglass text-warning"></i>&ensp;<a href="/training/2">In queue</a></td>
                                <td><a href="/user/1300001">1300412</a></td>
                                <td><a href="/user/1300001">Squeeker Kidsen</a></td>
                                <td>C1</td>
                                <td><i class="fas fa-fast-forward"></i>&ensp;Fast-track</td>
                                <td>Not started</td>
                                <td>Norway</td>
                                <td class="text-center"><i class="fas fa-times"></i></td>
                                <td>24.01.2020</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-hourglass text-warning"></i>&ensp;<a href="/training/2">In queue</a></td>
                                <td><a href="/user/1300001">1300123</a></td>
                                <td><a href="/user/1300001">Norman Virus</a></td>
                                <td>ENOB Oceanic</td>
                                <td><i class="fas fa-circle"></i>&ensp;Standard</td>
                                <td>Not started</td>
                                <td>Norway</td>
                                <td class="text-center"><i class="fas fa-check text-danger"></i></td>
                                <td>24.01.2020</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-pause"></i>&ensp;<a href="/training/2">Paused</a></td>
                                <td><a href="/user/1300001">1300031</a></td>
                                <td><a href="/user/1300001">Test Testersen</a></td>
                                <td>S2</td>
                                <td><i class="fas fa-exchange"></i>&ensp;Transfer</td>
                                <td>Not started</td>
                                <td>Norway</td>
                                <td class="text-center"><i class="fas fa-times"></i></td>
                                <td>24.01.2020</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Closed training requests</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="thead-dark">
                            <tr>
                                <th>State</th>
                                <th>Vatsim ID</th>
                                <th>Name</th>
                                <th>Level</th>
                                <th>Type</th>
                                <th>Period</th>
                                <th>Country</th>
                                <th class="text-center">English only</th>
                                <th>Application date</th>
                                <th>Closed date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><i class="fas fa-file-certificate text-success"></i>&ensp;<a href="/training/2">Completed</a></td>
                                <td><a href="/user/1300001">1300001</a></td>
                                <td><a href="/user/1300001">Test Testersen</a></td>
                                <td>S2 + S2 MAE ENGM</td>
                                <td><i class="fas fa-circle"></i>&ensp;Standard</td>
                                <td>24.01.20 - 26.02.20</td>
                                <td>Norway</td>
                                <td class="text-center"><i class="fas fa-times"></i></td>
                                <td>24.01.2020</td>
                                <td>24.01.2020</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-file-certificate text-success"></i>&ensp;<a href="/training/2">Completed</a></td>
                                <td><a href="/user/1300001">1300024</a></td>
                                <td><a href="/user/1300001">Test Riperino</a></td>
                                <td>S3</td>
                                <td><i class="fas fa-sync"></i>&ensp;Refresh</td>
                                <td>24.01.20 - 26.02.20</td>
                                <td>Norway</td>
                                <td class="text-center"><i class="fas fa-times"></i></td>
                                <td>24.01.2020</td>
                                <td>24.01.2020</td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="fas fa-ban text-danger"></i>&ensp;<a href="/training/2">Closed by TA</a>
                                    &nbsp;<div class="fa fa-comment-alt-lines text-info" data-placement="right" data-html="true" title="Closed due to the member breaking CoC multiple times during training."></div>
                                </td>
                                <td><a href="/user/1300001">1300412</a></td>
                                <td><a href="/user/1300001">Squeeker Kidsen</a></td>
                                <td>C1</td>
                                <td><i class="fas fa-fast-forward"></i>&ensp;Fast-track</td>
                                <td>Never started</td>
                                <td>Norway</td>
                                <td class="text-center"><i class="fas fa-times"></i></td>
                                <td>24.01.2020</td>
                                <td>24.01.2020</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-ban text-danger"></i>&ensp;<a href="/training/2">Closed by student</a></td>
                                <td><a href="/user/1300001">1300123</a></td>
                                <td><a href="/user/1300001">Norman Virus</a></td>
                                <td>ENOB Oceanic</td>
                                <td><i class="fas fa-circle"></i>&ensp;Standard</td>
                                <td>Never started</td>
                                <td>Norway</td>
                                <td class="text-center"><i class="fas fa-check text-danger"></i></td>
                                <td>24.01.2020</td>
                                <td>24.01.2020</td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="fas fa-ban text-danger"></i>&ensp;<a href="/training/2">Closed by system</a>
                                    &nbsp;<div class="fa fa-comment-alt-lines text-info" data-placement="right" data-html="true" title="Did not confirm training interest within 2 weeks."></div>
                                </td>
                                <td><a href="/user/1300001">1300031</a></td>
                                <td><a href="/user/1300001">Test Testersen</a></td>
                                <td>S2</td>
                                <td><i class="fas fa-exchange"></i>&ensp;Transfer</td>
                                <td>Never started</td>
                                <td>Norway</td>
                                <td class="text-center"><i class="fas fa-times"></i></td>
                                <td>24.01.2020</td>
                                <td>24.01.2020</td>
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