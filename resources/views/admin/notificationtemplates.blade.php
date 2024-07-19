@extends('layouts.app')

@section('title', 'Notification Templates')
@section('title-flex')
    <div>
        <i class="fas fa-filter"></i>&nbsp;Filter:&nbsp;
        @foreach($areas as $area)
            <a class="btn btn-sm {{ $currentArea == $area ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.templates.area', $area->id) }}">{{ $area->name }}</a>
        @endforeach
    </div>
@endsection

@section('content')

<form action="{{ route('admin.templates.update') }}" method="POST">
    @csrf
    <input type="hidden" name="area" value="{{ $currentArea->id }}">

    <div class="row">
        <div class="col-xl-12 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">{{ $currentArea->name }}'s Notifications</h6> 
                </div>        
                <div class="card-body">
                    <p>These editors give you the possiblity to append your FIR-specific text to the templates available, for the e-mail notifications. The notification text must be in English.</p>

                    <div class="row row-cols-auto g-1">
                        <div class="col">
                            <select class="form-select" onchange="selectedNotification(this.value)" id="notification">
                                <option selected disabled>Choose Notification</option>
                                <option value="1">New Request</option>
                                <option value="2">New Mentor</option>
                                <option value="3">Pre-Training</option>
                            </select>
                        </div>

                        <div class="col">
                            @can('modifyAreaTemplate', [App\Notification::class, $currentArea])
                                <button class="btn btn-success ms-2" type="submit">Save {{ $currentArea->name }}'s notifications</button>
                            @else
                                <button class="btn btn-success ms-2" disabled>Save {{ $currentArea->name }}'s notifications</button>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="newreqrow">

        <div class="col-xl-6 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">New Request Notification</h6> 
                </div>        
                <div class="card-body">
                    <div class="mb-3">
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
                    <h6 class="m-0 fw-bold text-white">Preview</h6> 
                </div>        
                <div class="card-body">
                    <h4>Hello (NAME),</h4>
                    <p>We hereby confirm that we have received your training request for (RATINGS) in {{ $currentArea->name }}.</p>
                    <p>The request is now in queue. Expected waiting time is {{ $currentArea->waiting_time ?? 'Unknown' }}</p>
                    <p>We will periodically ask you to confirm your continued interest for your application with us, it's your responsibility to check your email for these requests and reply within the deadline.</p>

                    <div id="newrequestaddition-preview"></div>

                    <hr>
                    <p>For questions regarding your training, contact <a href=#">(area){{ '@'.Setting::get('linkDomain') }}</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="newmentorrow">
        <div class="col-xl-6 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">New Mentor Notification</h6> 
                </div>        
                <div class="card-body">
                    <div class="mb-3">
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
                    <h6 class="m-0 fw-bold text-white">Preview</h6> 
                </div>        
                <div class="card-body">
                    <h4>Hello (NAME),</h4>
                    <p>It's your turn! You've been assigned a mentor for your training: (RATINGS) in (FIR).</p>
                    <p>Your mentor is: (MENTOR NAME). You can contact them through the message system at forums or search them up on Discord.</p>
                    <p>If you do not contact your mentor within 7 days, your training request will be closed and you lose the place in the queue.</p>

                    <div id="newmentoraddition-preview"></div>

                    <hr>
                    <p>For questions regarding your training, contact <a href=#">(area){{ '@'.Setting::get('linkDomain') }}</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="pretrainingrow">
        <div class="col-xl-6 col-md-12 mb-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">Pre-Training Notification</h6> 
                </div>        
                <div class="card-body">
                    <div class="mb-3">
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
                    <h6 class="m-0 fw-bold text-white">Preview</h6> 
                </div>        
                <div class="card-body">
                    <h4>Hello (NAME),</h4>
                    <p>We would like to inform you that your training request for (RATINGS) in (FIR) has now been assigned to pre-training.</p>

                    <div id="pretrainingaddition-preview"></div>

                    <hr>
                    <p>For questions regarding your training, contact <a href=#">(area){{ '@'.Setting::get('linkDomain') }}</a></p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('js')

<script>
    function selectedNotification(el){
        if(el == 1){
            document.getElementById("newreqrow").style.display = "block";
            document.getElementById("newmentorrow").style.display = "none";
            document.getElementById("pretrainingrow").style.display = "none";
        } else if(el == 2){
            document.getElementById("newreqrow").style.display = "none";
            document.getElementById("newmentorrow").style.display = "block";
            document.getElementById("pretrainingrow").style.display = "none";
        } else if(el == 3){
            document.getElementById("newreqrow").style.display = "none";
            document.getElementById("newmentorrow").style.display = "none";
            document.getElementById("pretrainingrow").style.display = "block";
        }
    }
</script>

<!-- Markdown Editor -->
@vite(['resources/js/easymde.js', 'resources/sass/easymde.scss'])
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var opts = {
            insertTexts: {
                link: ["[","](link)"],
            },
            status: false,
            toolbar: ["bold", "italic", "heading-3", "|", "quote", "unordered-list", "ordered-list", "|", "link", "preview", "side-by-side", "fullscreen", "|", "guide"]
        }
        var newRequestMde = new EasyMDE({
            element: document.getElementById("newrequestaddition"),
            ...opts
        });

        var newMentorMde = new EasyMDE({
            element: document.getElementById("newmentoraddition"),
            ...opts
        });

        var preTrainingMde = new EasyMDE({
            element: document.getElementById("pretrainingaddition"),
            ...opts
        });

        var newRequestPreview = setInterval(function(){

            document.getElementById("newrequestaddition-preview").innerHTML = newRequestMde.markdown(newRequestMde.value());
            document.getElementById("newmentoraddition-preview").innerHTML = newMentorMde.markdown(newMentorMde.value());
            document.getElementById("pretrainingaddition-preview").innerHTML = preTrainingMde.markdown(preTrainingMde.value());

        }, 1000);
    });
</script>

@endsection