@extends('layouts.app')

@section('title', 'ATC Roster')
@section('title-flex')
@endsection
@section('content')

<div class="row">
    <div class="col-xl-12 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">ATC Roster</h6> 
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
                        <thead class="table-light">
                            <tr>
                                <th data-field="student" class="w-50" data-sortable="true" data-filter-control="input">Member</th>
								<th data-field="rating" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">Rating</th>
								<th data-field="subdivision" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">Subdivision</th>
								<th data-field="active" data-sortable="true" data-filter-control="select" data-filter-data-collector="tableFilterStripHtml" data-filter-strict-search="false">ATC Active</th> 
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $u)
									<tr>
										<td>
											@can('view', $u)
												<a href="{{ route('user.show', $u->id) }}">{{ $u->name }} ({{ $u->id }})</a>
											@else
												{{ $u->name }} ({{ $u->id }})
											@endcan
										</td>
										<td>{{ $u->rating_short }} {{ $u->rating_long }}</td>
										<td>{{ $u->subdivision}}</td>
										<td class="text-center text-white {{ $u->active ? 'bg-success' : 'bg-danger' }}">
											@if($u->active)
												<i class="fas fa-check-circle"></i><span class="d-none">Yes</span>
											@else
												<i class="fas fa-times-circle"></i><span class="d-none">Inactive</span>
											@endif
										</td>   
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
