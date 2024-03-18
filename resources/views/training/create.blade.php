@extends('layouts.app')

@section('title', 'Add training request')
@section('content')

<div class="row">
    <div class="col-xl-4 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Create
                </h6>
            </div>
            <div class="card-body" id="training-selector">
                <form action="{{ route('training.store') }}" method="post">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="student">Student</label>
                        <input
                            id="student"
                            class="form-control @error('student') is-invalid @enderror"
                            type="text"
                            name="user_id"
                            list="students"
                            value="{{ isset($prefillUserId) ? $prefillUserId : old('student') }}"
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

                    <div class="mb-3">
                        <label class="form-label my-1 me-2" for="areaSelect">Training area</label>
                        <select id="areaSelect" name="training_area" class="form-select my-1 me-sm-2 @error('training_area') is-invalid @enderror" @change="showTrainingLevels">
                            <option selected disabled>Choose training area</option>
                            @foreach($ratings as $areaId => $area)
                                <option data-id="{{ $areaId }}" value="{{ $area['id'] }}">{{ $area['name'] }}</option>
                            @endforeach
                        </select>
                        @error('training_area')
                            <span class="text-danger">{{ $errors->first('training_area') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label my-1 me-2" for="typeSelect">Training type</label>
                        <select id="typeSelect" name="type" class="form-select my-1 me-sm-2 @error('type') is-invalid @enderror">
                            <option selected disabled>Choose training type</option>
                            @foreach($types as $id => $data)
                                <option value="{{ $id }}">{{ $data["text"] }}</option>
                            @endforeach
                        </select>
                        @error('type')
                            <span class="text-danger">{{ $errors->first('type') }}</span>
                        @enderror
                    </div>

                    <div class="mb-3 form-check">
                        <input value="true" type="checkbox" class="form-check-input" id="englishOnly" name="englishOnly">
                        <label class="form-check-label" for="englishOnly">English only training</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label my-1 me-2" for="ratingSelect">Training level <span class="badge bg-secondary">Ctrl/Cmd+Click</span> to select multiple</label>
                        <select multiple id="ratingSelect" name="ratings[]" class="form-select @error('ratings') is-invalid @enderror" size="5">
                            <option v-for="rating in ratings" :value="rating.id">@{{ rating.endorsement_type }} @{{ rating.name }}</option>
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
@vite('resources/js/vue.js')
<script>
    document.addEventListener("DOMContentLoaded", function () {

        var payload = {!! json_encode($ratings, true) !!}

        const app = createApp({
            data(){
                return {
                    ratings: [],
                }
            },
            methods: {
                showTrainingLevels: function(event) {
                    const selectedTrainingArea = event.srcElement.options[event.srcElement.selectedIndex];
                    this.ratings = payload[selectedTrainingArea.getAttribute('data-id')].ratings;
                },
            },
        })
        app.mount('#training-selector');

    });
</script>
@endsection
