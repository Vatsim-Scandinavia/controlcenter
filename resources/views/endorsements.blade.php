@extends('layouts.app')

@section('title', 'Member Endorsements')
@section('content')

<div class="row">
    <div class="col-xl-8 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">Member Endorsements</h6> 
            </div>        
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm table-hover table-leftpadded mb-0" width="100%" cellspacing="0"
                        data-page-size="25"
                        data-toggle="table"
                        data-pagination="true"
                        data-filter-control="true">
                        <thead class="thead-light">
                            <tr>
                                <th data-field="student" class="w-50" data-sortable="true" data-filter-control="input">Member</th>
                                <th data-field="endorsements" class="w-50" data-sortable="false" data-filter-control="input">Endorsements</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($members as $member)
                            <tr>
                                <td>
                                    @if(Auth::user()->isModeratorOrAbove())
                                        <a href="{{ route('user.show', $member->id) }}">{{ $member->name }} ({{ $member->id }})</a>
                                    @else 
                                        {{ $member->name }} ({{ $member->id }})
                                    @endif  
                                </td>
                                <td>
                                    @foreach ($member->ratings as $rating)
                                        {{ $rating->name }}@if(!$loop->last),&nbsp;@endif
                                    @endforeach   
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