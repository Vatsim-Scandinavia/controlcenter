@extends('layouts.app')

@section('title', 'MA/SC Endorsements')
@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">Major Airport and Special Center Endorsements</h6> 
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
                                <th data-field="student" class="w-50" data-sortable="true" data-filter-control="select">ATC Active</th>
                                @foreach($endorsements as $e)
                                    <th data-field="{{ $e->id }}" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml">{{ $e->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    Valdy Daddy (12345678)
                                </td>
                                <td>Yes</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="text-center bg-success text-white">
                                    <i class="fas fa-check-circle"></i><span class="d-none">Approved</span>
                                </td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>

                            <tr>
                                <td>
                                    Valdy Daddy (12345678)
                                </td>
                                <td>Yes</td>
                                <td></td>
                                <td></td>
                                <td class="text-center bg-success text-white">
                                    <i class="fas fa-check-circle"></i><span class="d-none">Approved</span>
                                </td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>

                            <tr>
                                <td>
                                    Valdy Daddy (12345678)
                                </td>
                                <td>Yes</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="text-center bg-success text-white">
                                    <i class="fas fa-check-circle"></i><span class="d-none">Approved</span>
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