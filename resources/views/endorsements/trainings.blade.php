@extends('layouts.app')

@section('title', 'Training Endorsements')
@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">Training Endorsements</h6> 
            </div>        
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-page-size="25"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-sort-reset="true"
                        >
                        <thead class="thead-light">
                            <tr>
                                <th data-field="student" class="w-50" data-sortable="true" data-filter-control="input">Member</th>
                                <th data-field="status" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml">Status</th>
                                <th data-field="type" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml">Type</th>
                                <th data-field="position" data-sortable="true" data-filter-control="select">Position</th>
                                <th data-field="validfrom" data-sortable="true" data-filter-control="select">Created</th>
                                <th data-field="validto" data-sortable="true" data-filter-control="select">Expires</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    Valdy Daddy (12345678)
                                </td>
                                <td class="text-center bg-success text-white">
                                    <i class="fas fa-check-circle"></i> Active
                                </td>
                                <td>
                                    <i class="fas fa-graduation-cap text-warning"></i>
                                    Solo
                                </td>
                                <td>ENBR_W_APP</td>
                                <td>16/02/2022</td>
                                <td>16/03/2022</td>
                            </tr>

                            <tr>
                                <td>
                                    Valdy Daddy (12345678)
                                </td>
                                <td class="text-center text-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Expired
                                </td>
                                <td>
                                    <i class="fas fa-book-open text-info"></i>
                                    S1
                                </td>
                                <td>ENBR_W_APP</td>
                                <td>16/02/2022</td>
                                <td>16/03/2022</td>
                            </tr>

                            <tr>
                                <td>
                                    Valdy Daddy (12345678)
                                </td>
                                <td class="text-center bg-success text-white">
                                    <i class="fas fa-check-circle"></i> Active
                                </td>
                                <td>
                                    <i class="fas fa-book-open text-info"></i>
                                    S1
                                </td>
                                <td>ENBR_W_APP</td>
                                <td>16/02/2022</td>
                                <td>16/03/2022</td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
    
</div>

@endsection