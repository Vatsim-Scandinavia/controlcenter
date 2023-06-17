@extends('layouts.app')

@section('title', 'Edit training request')
@section('content')

<div class="row">
    <div class="col-xl-4 col-md-12 mb-12">

        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Edit {{ $training->user->name }}'s training
                </h6> 
            </div>
            <div class="card-body">
                <form action="{{ route('training.update.request', $training->id) }}" method="post">
                    @method('PATCH')
                    @csrf

                    <div class="mb-3">
                        <label class="form-label my-1 me-2" for="typeSelect">Training type</label>
                        <select id="typeSelect" name="type" class="form-select my-1 me-sm-2 @error('type') is-invalid @enderror" @change="onChange($event)">
                            <option selected disabled>Choose training type</option>
                            @foreach($types as $id => $data)
                                @if( $id == $training->type )
                                    <option value="{{ $id }}" selected>{{ $data["text"] }}</option>
                                @else
                                    <option value="{{ $id }}">{{ $data["text"] }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('type')
                            <span class="text-danger">{{ $errors->first('type') }}</span>
                        @enderror
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input value="true" type="checkbox" class="form-check-input" id="englishOnly" name="englishOnly" {{ $training->english_only_training ? 'checked' : '' }}>
                        <label class="form-check-label" for="englishOnly">English only training</label>
                    </div>
 
                    <div class="mb-3">
                        <label class="form-label my-1 me-2" for="ratingSelect">Training level <span class="badge bg-secondary">Ctrl/Cmd+Click</span> to select multiple</label>
                        <select multiple id="ratingSelect" name="ratings[]" class="form-select @error('ratings') is-invalid @enderror" size="5">
                            @foreach($ratings as $rating)
                                @if($training->ratings->where('id', $rating->id)->count())
                                    <option value="{{ $rating->id }}" selected>{{ $rating->name }}</option>
                                @else
                                    <option value="{{ $rating->id }}">{{ $rating->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        
                        @error('ratings')
                            <span class="text-danger">{{ $errors->first('ratings') }}</span>
                        @enderror
                    </div>
                    
                    <button type="submit" class="btn btn-success">Update request</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
