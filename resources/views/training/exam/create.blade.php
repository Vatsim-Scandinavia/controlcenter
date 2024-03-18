@extends('layouts.app')

@section('title', 'New Exam Report')
@section('content')

    <div class="row" id="examreport">
        <div class="col-xl-5 col-lg-12 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">
                        {{ $training->user->name }}
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                            <thead class="table-light">
                                <tr>
                                    <th>Vatsim ID</th>
                                    <th>Current Rating</th>
                                    <th>Training Rating</th>
                                    <th>Subdivision</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $training->user->id }}</td>
                                    <td>{{ $training->user->rating_short }}</td>
                                    <td>
                                        @foreach($training->ratings as $rating)
                                            @if ($loop->last)
                                                {{ $rating->name }}
                                            @else
                                                {{ $rating->name . " + " }}
                                            @endif
                                        @endforeach
                                    </td>
                                    <td>{{ $training->user->subdivision }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">
                        New Examination Report
                    </h6>
                    @if(Setting::get('trainingExamTemplate') != "")
                        <a class="btn btn-sm btn-light" href="{{ Setting::get('trainingExamTemplate') }}" target="_blank"><i class="fas fa-download"></i>&nbsp;Download Exam Template</a>
                    @endif
                </div>
                <div class="card-body">
                    <form action="{{ route('training.examination.store', ['training' => $training->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="examination_date">Date</label>
                                <input id="examination_date" class="datepicker form-control" type="text" name="examination_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="position">Position</label>
                                <input
                                    id="position"
                                    class="form-control @error('position') is-invalid @enderror"
                                    type="text"
                                    name="position"
                                    list="positions"
                                    value="{{ old('position') }}"
                                    required>

                                <datalist id="positions">
                                    @foreach($positions as $position)
                                        @browser('isFirefox')
                                            <option>{{ $position->callsign }}</option>
                                        @else
                                            <option value="{{ $position->callsign }}">{{ $position->name }}</option>
                                        @endbrowser
                                    @endforeach
                                </datalist>

                                @error('position')
                                    <span class="text-danger">{{ $errors->first('position') }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xl-12 col-xxl-6 mb-4">
                                <label class="form-label" for="result">Result</label>
                                <select class="form-select" name="result" id="result" v-model="result" required>
                                    <option disabled selected>Choose a result</option>
                                    <option value="FAILED">Failed</option>
                                    <option value="PASSED">Passed</option>
                                    <option value="INCOMPLETE">Incomplete</option>
                                    <option value="POSTPONED">Postponed</option>
                                </select>
                            </div>
                            <div class="col-xl-12 col-xxl-6 mb-4">
                                <label class="form-label" for="attachments">Attachments</label>
                                <div>
                                    <input type="file" name="files[]" id="add-file" class="@error('file') is-invalid @enderror" accept=".pdf" multiple>
                                </div>
                                @error('files')
                                    <span class="text-danger">{{ $errors->first('files') }}</span>
                                @enderror
                            </div>
                        </div>

                        <div v-if="result === 'PASSED'">
                            <div class="border-top mb-4"></div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label" for="user">Request rating upgrade from</label>
                                    <input 
                                        id="user"
                                        class="form-control"
                                        type="text"
                                        name="request_task_user_id"
                                        list="userList"
                                        autocomplete="off"
                                    >
                                    <datalist id="userList">
                                        @foreach($taskRecipients as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </datalist>
                                    <div>
                                        @foreach($taskPopularAssignees as $user)
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-1 me-1" onclick="document.getElementById('user').value = '{{ $user->id }}'">
                                                <i class="fas fa-bolt"></i>
                                                {{ $user->name }}
                                            </button>
                                        @endforeach
                                    </div>
                                    @error('request_task_user_id')
                                        <span class="text-danger">{{ $errors->first('request_task_user_id') }}</span>
                                    @enderror

                                    
                                </div>  
                                <div class="col-md-6">
                                    @if($training->ratings->whereNotNull('vatsim_rating')->count() > 1)
                                        <label class="form-label" for="chooseRating">Upgrade to rating</label>
                                        <select class="form-select" id="chooseRating" name="subject_training_rating_id" required>
                                            @foreach($training->ratings->whereNotNull('vatsim_rating')->sortByDesc('id') as $rating)
                                                <option value="{{ $rating->id }}">{{ $rating->name }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <button type="submit" id="training-submit-btn" class="btn btn-success mt-3">Publish examination report</button>
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
        const application = createApp({
            data(){
                return {
                    result: null,
                }
            }
        }).mount('#examreport')
    })
</script>

<!-- Flatpickr -->
@vite(['resources/js/flatpickr.js', 'resources/sass/flatpickr.scss'])
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var defaultDate = "{{ old('date') }}"
        document.querySelector('.datepicker').flatpickr({ disableMobile: true, minDate: "{!! date('Y-m-d', strtotime('-1 months')) !!}", maxDate: "{!! date('Y-m-d') !!}", dateFormat: "d/m/Y", defaultDate: defaultDate, locale: {firstDayOfWeek: 1 } });
    })
</script>

@endsection
