@extends('layouts.app')

@section('title', 'Feedback')

@section('content')
    <div class="row">
        <div class="col-xl-12 col-md-12 mb-12">
            <livewire:feedback-table />
        </div>
    </div>
@endsection
