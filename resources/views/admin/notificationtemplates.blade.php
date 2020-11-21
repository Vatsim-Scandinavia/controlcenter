@extends('layouts.app')

@section('title', 'Notification Templates')
@section('title-extension')
    <div class="dropdown show" style="display: inline;">
        <a class="btn btn-sm btn-primary dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            {{ $filterName }}
        </a>
    
        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
            @foreach($countries as $country)
                <a class="dropdown-item" href="{{ route('admin.templates.country', $country->id) }}">{{ $country->name }}</a>
            @endforeach 
        </div>
    </div>
@endsection

@section('content')

<form action="{{ route('admin.templates.update') }}" method="POST">
    @csrf
    <input type="hidden" name="country" value="1">

    <div class="row">
        <div class="col-xl-12 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-white">{{ $filterName }} 's Notifications</h6> 
                </div>        
                <div class="card-body">
                    <p>These editors give you the possiblity to append your FIR-specific text to the templates available, for the e-mail notifications. The notification text must be in English.</p>

                    <div class="form-inline">
                        <select class="form-control" onchange="selectedNotification(this.value)" style="width: 200px" id="notification">
                            <option selected disabled>Choose Notification</option>
                            <option value="1">New Request</option>
                            <option value="2">New Mentor</option>
                            <option value="3">Pre-Training</option>
                        </select>

                        <button class="btn btn-success ml-2" type="submit">Save notifications</button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row" id="newreqrow" style="display: none;">

        <div class="col-xl-6 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-white">New Request Notification</h6> 
                </div>        
                <div class="card-body">
                    <div class="form-group">
                        <textarea class="form-control @error('newrequestaddition') is-invalid @enderror" name="newrequestaddition" id="newrequestaddition" rows="8" placeholder="Append text here or leave blank for no FIR-specific text.">{{ $template_newreq }}</textarea>
                        @error('newrequestaddition')
                            <span class="text-danger">{{ $errors->first('newrequestaddition') }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        

        <div class="col-xl-6 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-white">Preview</h6> 
                </div>        
                <div class="card-body">
                    <h4>Hello (NAME),</h4>
                    <p>We herby confirm that we have received your training request for (RATINGS) in (FIR).</p>
                    <p>The request is now in queue. Expected waiting time: {{ Setting::get('trainingQueue') }}</p>
                    <p>We will periodically ask you to confirm your continued interest for your application with us, it's your responsibility to check your email for these requests and reply within the deadline.</p>

                    <div id="newrequestaddition-preview"></div>

                    <hr>
                    <p>For questions regarding your training, contact <a href="#">training-(FIR)@vatsim-scandinavia.org</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="newmentorrow" style="display: none;">
        <div class="col-xl-6 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-white">New Mentor Notification</h6> 
                </div>        
                <div class="card-body">
                    <div class="form-group">
                        <textarea class="form-control @error('newmentoraddition') is-invalid @enderror" name="newmentoraddition" id="newmentoraddition" rows="8" placeholder="Append text here or leave blank for no FIR-specific text.">{{ $template_newmentor }}</textarea>
                        @error('newmentoraddition')
                            <span class="text-danger">{{ $errors->first('newmentoraddition') }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-white">Preview</h6> 
                </div>        
                <div class="card-body">
                    <h4>Hello (NAME),</h4>
                    <p>It's your turn! You've been assigned a mentor for you training: (RATINGS) in (FIR).</p>
                    <p>Your mentor is: (MENTOR NAME). You can contact them through the message system at forums or search them up on Discord.</p>
                    <p>If you do not contact your mentor within 7 days, your training request will be closed and you loose the place in the queue.</p>

                    <div id="newmentoraddition-preview"></div>

                    <hr>
                    <p>For questions regarding your training, contact <a href=#">training-(FIR)@vatsim-scandinavia.org</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="pretrainingrow" style="display: none;">
        <div class="col-xl-6 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-white">Pre-Training Notification</h6> 
                </div>        
                <div class="card-body">
                    <div class="form-group">
                        <textarea class="form-control @error('pretrainingaddition') is-invalid @enderror" name="pretrainingaddition" id="pretrainingaddition" rows="8" placeholder="Append text here or leave blank for no FIR-specific text.">{{ $template_pretraining }}</textarea>
                        @error('pretrainingaddition')
                            <span class="text-danger">{{ $errors->first('pretrainingaddition') }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-white">Preview</h6> 
                </div>        
                <div class="card-body">
                    <h4>Hello (NAME),</h4>
                    <p>We would like to inform you that your training request for (RATINGS) in (FIR) has now been assigned to pre-training.</p>

                    <div id="pretrainingaddition-preview"></div>

                    <hr>
                    <p>For questions regarding your training, contact <a href=#">training-(FIR)@vatsim-scandinavia.org</a></p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('js')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
<script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
<script>

    function selectedNotification(el){
        if(el == 1){
            $('#newreqrow').show();
            $('#newmentorrow').hide();
            $('#pretrainingrow').hide();

            newRequestMde.codemirror.refresh();
        } else if(el == 2){
            $('#newreqrow').hide();
            $('#newmentorrow').show();
            $('#pretrainingrow').hide();

            newMentorMde.codemirror.refresh();
        } else if(el == 3){
            $('#newreqrow').hide();
            $('#newmentorrow').hide();
            $('#pretrainingrow').show();

            preTrainingMde.codemirror.refresh();
        }
    }

    var newRequestMde = new SimpleMDE({ 
        element: document.getElementById("newrequestaddition"), 
        status: false, 
        toolbar: ["bold", "italic", "heading-3", "|", "quote", "unordered-list", "ordered-list", "|", "link", "preview", "side-by-side", "fullscreen", "|", "guide"],
        insertTexts: {
            link: ["[","text](link)"],
        },
    });

    var newMentorMde = new SimpleMDE({ 
        element: document.getElementById("newmentoraddition"), 
        status: false, 
        toolbar: ["bold", "italic", "heading-3", "|", "quote", "unordered-list", "ordered-list", "|", "link", "preview", "side-by-side", "fullscreen", "|", "guide"],
        insertTexts: {
            link: ["[","text](link)"],
        },
    });

    var preTrainingMde = new SimpleMDE({ 
        element: document.getElementById("pretrainingaddition"), 
        status: false, 
        toolbar: ["bold", "italic", "heading-3", "|", "quote", "unordered-list", "ordered-list", "|", "link", "preview", "side-by-side", "fullscreen", "|", "guide"],
        insertTexts: {
            link: ["[","text](link)"],
        },
    });

    var newRequestPreview = setInterval(function(){

        document.getElementById("newrequestaddition-preview").innerHTML = newRequestMde.markdown(newRequestMde.value());
        document.getElementById("newmentoraddition-preview").innerHTML = newMentorMde.markdown(newMentorMde.value());
        document.getElementById("pretrainingaddition-preview").innerHTML = preTrainingMde.markdown(preTrainingMde.value());
    
    }, 1000);

</script>

@endsection