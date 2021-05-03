@extends('layouts.app')

@section('title', 'Add training request')
@section('content')

<div class="row">
    <div class="col-xl-4 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Create 
                </h6> 
            </div>
            <div class="card-body">
                <form action="{{ route('training.store') }}" method="post">
                    @csrf

                    <div class="form-group">
                        <label for="student">Student</label>
                        <input 
                            id="student"
                            class="form-control @error('student') is-invalid @enderror"
                            type="text"
                            name="user_id"
                            list="students"
                            value="{{ old('student') }}"
                            required>

                        <datalist id="students">
                            @foreach($students as $student)
                                @browser('isFirefox')
                                    <option>{{ $student->id }}</option>
                                @else
                                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                                @endbrowser
                            @endforeach
                        </datalist>

                        @error('student')
                            <span class="text-danger">{{ $errors->first('student') }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="my-1 mr-2" for="areaSelect">Training area</label>
                        <select id="areaSelect" name="training_area" class="custom-select my-1 mr-sm-2 @error('training_area') is-invalid @enderror" @change="onChange($event)">
                            <option selected disabled>Choose training area</option>
                            @foreach($ratings as $areaId => $area)
                                <option data-id="{{ $areaId }}" value="{{ $area['id'] }}">{{ $area['name'] }}</option>
                            @endforeach
                        </select>
                        @error('training_area')
                            <span class="text-danger">{{ $errors->first('training_area') }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="my-1 mr-2" for="typeSelect">Training type</label>
                        <select id="typeSelect" name="type" class="custom-select my-1 mr-sm-2 @error('type') is-invalid @enderror" @change="onChange($event)">
                            <option selected disabled>Choose training type</option>
                            @foreach($types as $id => $data)
                                <option value="{{ $id }}">{{ $data["text"] }}</option>
                            @endforeach
                        </select>
                        @error('type')
                            <span class="text-danger">{{ $errors->first('type') }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="my-1 mr-2" for="englishOnly">English only training</label>
                        <select id="englishOnly" name="englishOnly" class="custom-select my-1 mr-sm-2">
                            <option selected disabled>English only training</option>
                                <option selected>No</option>
                                <option value="true">Yes</option>
                        </select>
                    </div>
 
                    <div class="form-group">
                        <label class="my-1 mr-2" for="ratingSelect">Training level <span class="badge badge-dark">Ctrl/Cmd+Click</span> to select multiple</label>
                        <select multiple id="ratingSelect" name="ratings[]" class="form-control @error('ratings') is-invalid @enderror" size="5">
                            <option v-for="rating in ratings" :value="rating.id">@{{ rating.name }}</option>
                        </select>
                        
                        @error('ratings')
                            <span class="text-danger">{{ $errors->first('ratings') }}</span>
                        @enderror
                    </div>
                    
                    <button type="submit" class="btn btn-success">Create training</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
    var payload = {!! json_encode($ratings, true) !!}

    const area = new Vue({
        el: '#areaSelect',
        methods: {
            onChange(event) {
                rating.update(event.srcElement.options[event.srcElement.selectedIndex])
            }
        }
    });

    const type = new Vue({
        el: '#typeSelect',
        methods: {
            onChange(event) {
                type.update(event.srcElement.options[event.srcElement.selectedIndex])
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
                this.ratings = payload[value.getAttribute('data-id')].ratings
            }
        }
    });
</script>
@endsection
