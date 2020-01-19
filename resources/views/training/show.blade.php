@extends('layouts.app')

@section('title', 'Training')

@section('content')
<h1 class="h3 mb-4 text-gray-800">
    {{ $training->user->handover->firstName }}'s training for
    @if ( is_iterable($ratings = $training->ratings->toArray()) )
        @for( $i = 0; $i < sizeof($ratings); $i++ )
            @if ( $i == (sizeof($ratings) - 1) )
                {{ $ratings[$i]["name"] }}
            @else
                {{ $ratings[$i]["name"] . " + " }}
            @endif
        @endfor
    @else
        {{ $ratings["name"] }}
    @endif
</h1>

<div class="row">

    <div class="col-xl-12 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Details
                </h6> 
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-leftpadded mb-0" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>State</th>
                                <th>Vatsim ID</th>
                                <th>Name</th>
                                <th>Level</th>
                                <th>Type</th>
                                <th>Period</th>
                                <th>Country</th>
                                <th>Applied</th>
                                <th>Closed</th>
                                <th>Mentor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    @switch($training->status)
                                        @case(0)
                                            <i class="fas fa-hourglass text-warning"></i>&ensp;<a href="/training/{{ $training->id }}">In queue</a>
                                            @break
                                        @case(1)
                                            <i class="fas fa-book-open text-success"></i>&ensp;<a href="/training/{{ $training->id }}">In progress</a>
                                            @break
                                        @case(2)
                                            <i class="fas fa-graduation-cap text-success"></i>&ensp;<a href="/training/{{ $training->id }}">Awaiting exam</a>
                                            @break
                                    @endswitch
                                </td>
                                <td><a href="/user/{{ $training->user->id }}">{{ $training->user->id }}</a></td>
                                <td><a href="/user/{{ $training->user->id }}">{{ $training->user->handover->firstName }} {{ $training->user->handover->lastName }}</a></td>
                                <td>
                                    @if ( is_iterable($ratings = $training->ratings->toArray()) )
                                        @for( $i = 0; $i < sizeof($ratings); $i++ )
                                            @if ( $i == (sizeof($ratings) - 1) )
                                                {{ $ratings[$i]["name"] }}
                                            @else
                                                {{ $ratings[$i]["name"] . " + " }}
                                            @endif
                                        @endfor
                                    @else
                                        {{ $ratings["name"] }}
                                    @endif
                                </td>
                                <td>
                                    @switch($training->type)
                                        @case(1)
                                            <i class="fas fa-circle"></i>&ensp;Standard
                                            @break
                                        @case(2)
                                            <i class="fas fa-sync"></i>&ensp;Refresh
                                            @break
                                        @case(3)
                                            <i class="fas fa-exchange"></i>&ensp;Transfer
                                            @break
                                        @case(4)
                                            <i class="fas fa-fast-forward"></i>&ensp;Fast-track
                                            @break
                                        @case(5)
                                            <i class="fas fa-compress-arrows-alt"></i>&ensp;Familiarisation
                                            @break
                                    @endswitch
                                    
                                </td>
                                <td>
                                    @if ($training->started_at == null && $training->finished_at == null)
                                        Training not started
                                    @elseif ($training->finished_at == null)
                                        {{ $training->started_at->toFormattedDateString() }} -
                                    @else
                                        {{ $training->started_at->toFormattedDateString() }} - {{ $training->finished_at->toFormattedDateString() }}
                                    @endif
                                </td>
                                <td>{{ $training->country->name }}</td>
                                <td>{{ $training->created_at->toFormattedDateString() }}</td>
                                <td>
                                    @if ($training->finished_at != null)
                                        {{ $training->finished_at->toFormattedDateString() }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ( is_iterable($mentors = $training->mentors->toArray()) )
                                        @if (sizeof($mentors) == 0)
                                            -
                                        @else
                                            @for( $i = 0; $i < sizeof($mentors); $i++ )
                                                @if ( $i == (sizeof($mentors) - 1) )
                                                    {{ $mentors[$i]["name"] }}
                                                @else
                                                    {{ $mentors[$i]["name"] . ", " }}
                                                @endif
                                            @endfor
                                        @endif
                                    @else
                                        {{ $mentors[$i]["name"] }}
                                    @endif
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="row">

    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Options 
                </h6> 
            </div>
            <div class="card-body">
                <form action="{!! action('TrainingController@update') !!}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="trainingStateSelect">Select training state</label>
                        <select class="form-control" id="trainingStateSelect">
                            @foreach($statuses as $id => $data)
                                @if($data["assignableByStaff"])
                                    @if($id == $training->status)
                                        <option value="{{ $id }}" selected>{{ $data["text"] }}</option>
                                    @else
                                        <option value="{{ $id }}">{{ $data["text"] }}</option>
                                    @endif
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="trainingStateSelect">Select training type</label>
                        <select class="form-control" id="trainingStateSelect">
                            @foreach($types as $id => $text)
                                @if($id == $training->type)
                                    <option value="{{ $id }}" selected>{{ $text }}</option>
                                @else
                                    <option value="{{ $id }}">{{ $text }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="internalTrainingComments">Internal training comments</label>
                        <textarea class="form-control" id="internalTrainingComments" rows="8" placeholder="Write internal training notes here">{{ $training->notes }}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="assignMentors">Assigned mentors: <span class="badge badge-dark">Ctrl/Cmd+Click</span> to select multiple</label>
                        <select multiple class="form-control" id="assignMentors">
                            @php
                                var_dump($mentors);
                            @endphp
                            @foreach($mentors as $mentor)
                                <option>{{ $mentor->handover->firstName }} {{ $mentor->handover->lastName }}</option>
                            @endforeach
                            <option>Test</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Save</button>

                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Application 
                </h6> 
            </div>
            <div class="report-overflow-scroll">
                <div class="card-body">
                    <div class="card bg-light mb-3">
                        <div class="card-header text-primary">Language</div>
                        <div class="card-body">
                            @if($training->english_only_training)
                                <p class="card-text text-warning">
                                    The student wishes to receive training in English.
                                </p>
                            @else
                                <p class="card-text">
                                    The student is able to receive training in local and English language.
                                </p>
                            @endif
                        
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card bg-light mb-3">
                        <div class="card-header text-primary">Letter of motivation</div>
                        <div class="card-body">
                        <p class="card-text">
                            {{ $training->motivation }}
                        </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    

    <div class="col-xl-4 col-md-12 mb-12">
        <div class="card shadow mb-4 ">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">
                    Reports 
                </h6> 
            </div>
            <div class="report-overflow-scroll">
                <div class="card-body">
                    <div class="card bg-light mb-3">
                        <div class="card-header text-primary">Training report 12. des 2019</div>
                            <div class="card-body">
                            <p class="card-text">
                                First session, lost notes due to technical issues, but from what I can recall, you need to practice some on your phraseology.
                                Mainly "Cleared to", "Climb FL/xxx feet" -- never "Fly direct" or "Climb to FL/xxx feet".
                                <br><br>
                                Remember that separation by altitude or vectors are always made to create separation. (Vertical) speed control is only used to
                                _maintain_ separation.
                                <br><br>
                                When giving vectors, always explain why: For example "Turn left/right hdg xxx, vectors ILS rwy 17".
                                <br><br>
                                Upon giving vectors to aircraft previously flying on own navigation (ie. on a STAR or direct a NAVAID) it's generally a good idea
                                to say "fly heading xxx" instead of "turn left/right hdg xxx", because you don't know which heading he's flying. Remember the
                                difference between heading and track.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="card bg-light mb-3">
                        <div class="card-header text-primary">Training report 12. des 2019</div>
                            <div class="card-body">
                            <p class="card-text">
                                When someone checks in with their altitude, you don't need to state the altitude you see them at in your scope when identifying
                                them.
                                <br><br>
                                Deconfliction: We received LNFTQ, a DA40, with SAS253, a B737 just 5.6 nmi and decreasing behind, from Tower. Not a good spot to
                                be in, but that was Tower's fault. In this situation, your number one priority is to get these
                                <br><br>
                                away from each other. The most effective way of doing this is giving them vectors away from each other. Relevant phraseology here
                                is: "SAS253, essential traffic, 12'o'clock, 4000 feet, 4 nmi, light aircraft, turn left HDG 090 NOW".
                                <br><br>
                                Remember to look at the TL. If it's at level 95, you cannot descend people to FL80, because you should never clear people to fly
                                in the transition layer.
                                <br><br>
                                Remember to use the lists actively (CFL etc).
                                <br><br>
                                You can give resume own nav, you don't need to vector planes, if it gets stressful
                                <br><br>
                                Don't just say yes to everything a pilot asks for. On the contrary, don't micro manage them either
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection