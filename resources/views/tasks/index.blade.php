@extends('layouts.app')

@section('title', 'Tasks')
@section('title-flex')
    <div>
        <i class="fas fa-filter"></i>&nbsp;Filter:&nbsp;
        <a class="btn btn-sm btn-primary" href="">Open</a>
        <a class="btn btn-sm btn-outline-primary" href="">Sent</a>
        <a class="btn btn-sm btn-outline-primary" href="">Archived</a>
    </div>
@endsection

@section('header')
    @vite(['resources/sass/bootstrap-table.scss', 'resources/js/bootstrap-table.js'])
@endsection

@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">Tasks</h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true"
                        data-page-size="15"
                        data-page-list=[10,15,25,50]
                        data-sort-reset="true">
                        <thead class="table-light">
                            <tr>
                                <th>Task #</th>
                                <th>Type</th>
                                <th>Regarding</th>
                                <th>Request</th>
                                <th>From</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>

                            <tr>
                                <td>14</td>
                                <td><i class="fas fa-circle-arrow-up"></i> Rating Upgrade</td>
                                <td>Sara Student (1000241)</td>
                                <td>
                                    <a href="#" style="text-decoration: underline;">Grant S1 theoretical exam for Sara Student (1000241) <i class="fas fa-arrow-up-right-from-square"></i></a>
                                </td>
                                <td>John Doe (10000010)</td>
                                
                                <td>
                                    <a href="" class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i> Complete</a>
                                    <a href="" class="btn btn-sm btn-outline-danger"><i class="fas fa-xmark"></i> Decline</a>
                                </td>
                            </tr>

                            <tr>
                                <td>15</td>
                                <td><i class="fas fa-key"></i> Theoretical Exam Access</td>
                                <td>Sara Student (1000241)</td>
                                <td>
                                    <a href="#" style="text-decoration: underline;">Upgrade Sara Student (1000241) to S1 <i class="fas fa-arrow-up-right-from-square"></i></a>
                                </td>
                                <td>John Doe (10000010)</td>
                                
                                <td>
                                    <a href="" class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i> Complete</a>
                                    <a href="" class="btn btn-sm btn-outline-danger"><i class="fas fa-xmark"></i> Decline</a>
                                </td>
                            </tr>

                            <tr>
                                <td>15</td>
                                <td><i class="fas fa-message"></i> Custom Memo</td>
                                <td>Sara Student (1000241)</td>
                                <td>
                                    Could you please add MAE to this student's training?
                                </td>
                                <td>John Doe (10000010)</td>
                                
                                <td>
                                    <a href="" class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i> Complete</a>
                                    <a href="" class="btn btn-sm btn-outline-danger"><i class="fas fa-xmark"></i> Decline</a>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
</div>

@endsection