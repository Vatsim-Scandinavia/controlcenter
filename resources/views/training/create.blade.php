@extends('layouts.app')

@section('title', 'Apply')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Add training request</h1>

<div class="row">

    <form action="{{ route('training.store') }}" method="post">
        @csrf

        <div class="col-xl-12 col-md-12 mb-12">

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">General information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <label class="my-1 mr-2" for="user_id">Trainee CID</label>
                        <input name="user_id" id="user_id" class="custom-select my-1 mr-sm-2" type="text">
                    </div>
                    <div class="row">
                        <label class="my-1 mr-2" for="countrySelect">Training country</label>
                        <select id="countrySelect" name="training_country" class="custom-select my-1 mr-sm-2" @change="onChange($event)">
                            <option selected disabled>Choose training country</option>
                            @foreach($ratings as $countryId => $country)
                                <option data-id="{{ $countryId }}" value="{{ $country['id'] }}">{{ $country['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <label class="my-1 mr-2" for="ratingSelect">Training type</label>
                        <select id="ratingSelect" name="training_level" class="custom-select my-1 mr-sm-2">
                            <option selected disabled>Choose</option>
                            <option v-for="rating in ratings" :value="rating.id">@{{ rating.name }}</option>
                        </select>
                    </div>
                    <div class="row">
                        <input type="submit" class="btn btn-success" value="Create training!">
                    </div>
                </div>
            </div>

        </div>

    </form>

</div>

@endsection

@section('js')
<script>
    var payload = {!! json_encode($ratings, true) !!}

    const country = new Vue({
            el: '#countrySelect',
            methods: {
                onChange(event) {
                    rating.update(event.srcElement.options[event.srcElement.selectedIndex])
                }
            }
        });

    const rating = new Vue({
        el: '#ratingSelect',
        data: {
            ratings: '',
        },
        methods: {
            update: function(value){
                console.log(payload);
                this.ratings = payload[value.getAttribute('data-id')].ratings
            }
        }
    });
</script>
@endsection
