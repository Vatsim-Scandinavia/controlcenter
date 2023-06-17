
<!DOCTYPE html>
<html lang="en">
    <head>
        @include('layouts.header')
    </head>

    <body>
    <div id='app'></div>

    {{-- Page Wrapper --}}
    <div id="wrapper">

        @auth
            @include('layouts.sidebar')
        @endauth

        {{-- Content Wrapper --}}
        <div id="content-wrapper" class="d-flex flex-column">

        {{-- Main Content --}}
        <div id="content">

            @auth
                @include('layouts.topbar')
            @endauth

            @yield('content-master') {{-- For special things to be done outside the container --}}
            
            <div class="container-fluid">

                @if(!Route::is('front'))
                    <h3 class="mb-4 text-gray-800">
                        @yield('title', 'Page Title')
                        @yield('title-extension')
                    </h3>

                    @if(Session::has('success') OR isset($success))
                        <div class="alert alert-success" role="alert">
                            {!! Session::pull("success") !!}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @if(count($errors) > 1)
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @else
                                {{ $errors->first() }}
                            @endif
                        </div>
                    @endif
                @endif

                @yield('content')
            </div>

        </div>
        {{-- End of Main Content --}}

        </div>
        {{-- End of Content Wrapper --}}

    </div>
    {{-- End of Page Wrapper --}}

    {{-- Bootstrap core JavaScript--}}
    <script src="{{ asset('js/app.js') }}"></script>
    <script>
        // Bootstrap-table: Filter function to strip html from bootstrap table column filters
        window.tableFilterStripHtml = function (value) {
            return value.replace(/<[^>]+>/g, '').trim();
        }

        // Bootstrap-table: Sort dates according to timetamp and not alphabethically
        window.tableSortDates = function(a, b, rowA, rowB){
            var a = moment(window.tableFilterStripHtml(a), "DD/MM/YYYY");
            var b = moment(window.tableFilterStripHtml(b), "DD/MM/YYYY");

            if (a.isAfter(b)) return 1;
            if (a.isBefore(b)) return -1;
            return 0;
        }

        // Bootstrap-table: Filter function to strip badge from bootstrap table column filters
        window.tableFilterStripBadge = (value) => {
            return value.replace(/.*>/g, '').trim();
        }

        // Search bar
        window.addEventListener('load', function(event) {

            var requestController = new AbortController() // abort as well when you close the window
            var currentRequest = null

            var searchResults = document.querySelectorAll('.search-results')
            var searchSpinner = document.querySelectorAll('.search-spinner')

            async function fetch_users(query = ''){

                requestController.abort()
                requestController = new AbortController()

                var data = null
                const request = await fetch('{{ route('user.search') }}?query='+query, {signal: requestController.signal})
                    .then(res => {
                        if (res.ok) return res.text()
                        return Promise.reject(res)
                    })
                    .catch(err => {
                        if (err.name !== 'AbortError') console.error(err)
                        return null
                    })

                
                if(request && request != ''){
                    data = JSON.parse(request)
                }

                if(request !== null){
                    if(data && data.length > 0){
                        var html = '';
                        var baseUrl = '{{ URL::to('/user') }}\/'

                        for(var i=0; i < data.length; i++){
                            html +='<a href="'+ baseUrl + data[i]['id'] +'">'+ data[i]['id'] + ": "+ data[i]['name'] +'</a>'
                        }

                        searchResults.forEach((el) => {
                            el.innerHTML = html;
                        });     
                        
                        searchSpinner.forEach((el) => {
                            el.classList.remove('search-spinner-visible')
                        });

                        searchResults.forEach((el) => {
                            el.style.display = 'block'
                        });

                    } else {
                        searchResults.forEach((el) => {
                            el.innerHTML = "<a href='#''>No results</a>";
                        });

                        searchSpinner.forEach((el) => {
                            el.classList.remove('search-spinner-visible')
                        });

                        searchResults.forEach((el) => {
                            el.style.display = 'block'
                        });
                    }
                }


            }

            var timer = null
            document.querySelectorAll('.search-input').forEach((input) => {
                input.addEventListener('keyup', function(){
                    var query = this.value;

                    searchSpinner.forEach((el) => {
                        el.classList.add('search-spinner-visible')
                    });
                    
                    clearTimeout(timer);
                    timer = setTimeout(fetch_users, 200, query)
                });
            });

            // When pressing enter on desktop, redirect directly to inputed userID if it's a number
            // otherwise just prevent and let ajax do its thing.
            document.querySelector('#user-search-form-desktop').addEventListener('submit', function(e){ 
                e.preventDefault();

                var query = parseInt(document.querySelector('.search-input').value);
                if(Number.isInteger(query)){
                    location.assign("{{ route('user.show', '') }}/" + query); 
                }

            });

            // When pressing enter on mobile start the search
            document.querySelector('#user-search-form-mobile').addEventListener('submit', function(e){
                e.preventDefault() 

                var query = null
                document.querySelectorAll('.search-input').forEach((el) => {
                    if(el.value != '') query = el.value
                });

                searchSpinner.forEach((el) => {
                    el.classList.add('search-spinner-visible')
                });
                
                console.log(query)
                clearTimeout(timer);
                timer = setTimeout(fetch_users, 200, query, true)
            });

            document.addEventListener("click", function(event) {
                if(event.target.closest('.search-results') == null){

                    searchResults.forEach((el) => {
                        el.style.display = 'none'
                    });

                    searchSpinner.forEach((el) => {
                        el.classList.remove('search-spinner-visible')
                    });
                }
                });
            });
    </script>

    @yield('js')
    </body>
</html>
