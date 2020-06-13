@extends('layouts.app')

@section('title', 'Member overview')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Member overview</h1>

<div class="row">

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">VATSCA Members</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>Vatsim ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>ATC Rating</th>
                                <th>Country</th>
                                <th>Last login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><a href="#">1300001</a></td>
                                <td>Test</td>
                                <td>Testersen</td>
                                <td>None</td>
                                <td>NO</td>
                                <td>3 minutes ago</td>
                            </tr>
                            <tr>
                                <td><a href="#">1300002</a></td>
                                <td>Testorino</td>
                                <td>Ripperino</td>
                                <td>S1</td>
                                <td>NO</td>
                                <td>3 hours ago</td>
                            </tr>
                            <tr>
                                <td><a href="#">1300003</a></td>
                                <td>Pizza</td>
                                <td>Napoli</td>
                                <td>S2</td>
                                <td>NO</td>
                                <td>5 days ago</td>
                            </tr>
                            <tr>
                                <td><a href="#">1300004</a></td>
                                <td>Adam</td>
                                <td>Banana</td>
                                <td>S3</td>
                                <td>NO</td>
                                <td>3 minutes ago</td>
                            </tr>
                            <tr>
                                <td><a href="#">1300005</a></td>
                                <td>Test</td>
                                <td>Testersen</td>
                                <td>C1</td>
                                <td>NO</td>
                                <td>2 weeks ago</td>
                            </tr>
                            <tr>
                                <td><a href="#">1300006</a></td>
                                <td>Eve</td>
                                <td>Appelina</td>
                                <td>C3</td>
                                <td>NO</td>
                                <td>One year ago</td>
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
                <h6 class="m-0 font-weight-bold text-white">Registered Non-VATSCA Users</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>Vatsim ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>ATC Rating</th>
                                <th>Visiting Controller</th>
                                <th>Division</th>
                                <th>Subdivision</th>
                                <th>Country</th>
                                <th>Last login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><a href="#">1300001</a></td>
                                <td>Test</td>
                                <td>Testersen</td>
                                <td>None</td>
                                <td><i class="fas fa-check"></i></td>
                                <td>EUD</td>
                                <td>ITA</td>
                                <td>ITA</td>
                                <td>3 minutes ago</td>
                            </tr>
                            <tr>
                                <td><a href="#">1300002</a></td>
                                <td>Testorino</td>
                                <td>Ripperino</td>
                                <td>S1</td>
                                <td><i class="fas fa-check"></i></td>
                                <td>EUD</td>
                                <td>ITA</td>
                                <td>ITA</td>
                                <td>3 hours ago</td>
                            </tr>
                            <tr>
                                <td><a href="#">1300003</a></td>
                                <td>Pizza</td>
                                <td>Napoli</td>
                                <td>S2</td>
                                <td><i class="fas fa-times"></i></td>
                                <td>EUD</td>
                                <td>ITA</td>
                                <td>ITA</td>
                                <td>5 days ago</td>
                            </tr>
                            <tr>
                                <td><a href="#">1300004</a></td>
                                <td>Adam</td>
                                <td>Banana</td>
                                <td>S3</td>
                                <td><i class="fas fa-times"></i></td>
                                <td>EUD</td>
                                <td>GER</td>
                                <td>GER</td>
                                <td>3 minutes ago</td>
                            </tr>
                            <tr>
                                <td><a href="#">1300005</a></td>
                                <td>Test</td>
                                <td>Testersen</td>
                                <td>C1</td>
                                <td><i class="fas fa-times"></i></td>
                                <td>EUD</td>
                                <td>ITA</td>
                                <td>ITA</td>
                                <td>2 weeks ago</td>
                            </tr>
                            <tr>
                                <td><a href="#">1300006</a></td>
                                <td>Eve</td>
                                <td>Appelina</td>
                                <td>C3</td>
                                <td><i class="fas fa-times"></i></td>
                                <td>UK</td>
                                <td>UK</td>
                                <td>UK</td>
                                <td>One year ago</td>
                            </tr>
                        </tbody>
                    </table>
                </div>              
            </div>
        </div>
    </div>

</div>
@endsection