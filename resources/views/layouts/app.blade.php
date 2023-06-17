
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

                    <div class="d-flex justify-content-between">
                        <h3 class="mb-4 text-gray-800">
                            @yield('title', 'Page Title')
                        </h3>
                        @yield('title-flex')
                    </div>

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
            var requestController = new AbortController()

            // Search for a user with query
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

                // Check if the request is not aborted or blank
                if(request && request != ''){
                    data = JSON.parse(request)
                }

                // Only update search if the request didn't error
                if(request !== null){
                    if(data && data.length > 0){
                        var html = '';
                        var baseUrl = '{{ URL::to('/user') }}\/'

                        for(var i=0; i < data.length; i++){
                            html +='<a href="'+ baseUrl + data[i]['id'] +'">'+ data[i]['id'] + ": "+ data[i]['name'] +'</a>'
                        }

                        showSpinner(false)
                        showResults(true, html)

                    } else {
                        showSpinner(false)
                        showResults(true, "<a href='#''>No results</a>")
                    }
                }


            }

            function showSpinner(boolean){
                document.querySelectorAll('.search-spinner').forEach((el) => {
                    boolean ? el.classList.add('search-spinner-visible') : el.classList.remove('search-spinner-visible')
                });
            }

            function showResults(boolean, html = null){
                document.querySelectorAll('.search-results').forEach((el) => {
                    if(html) el.innerHTML = html;
                    boolean ? el.style.display = 'block' : el.style.display = 'none'
                });
            }

            // Start search when typing in search bar with a 200ms delay
            var timer = null
            document.querySelectorAll('.search-input').forEach((input) => {
                input.addEventListener('keyup', function(){
                    showSpinner(true)
                    clearTimeout(timer);
                    timer = setTimeout(fetch_users, 200, this.value)
                });
            });

            // When pressing enter on desktop, redirect directly to inputed userID if it's a number
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

                showSpinner(true)
        
                clearTimeout(timer);
                timer = setTimeout(fetch_users, 200, query, true)
            });

            // Close search results when clicking outside of it
            document.addEventListener("click", function(event) {
                if(event.target.closest('.search-results') == null){
                    showResults(false)
                    showSpinner(false)
                }
            });
        });
    </script>

    @yield('js')
    </body>
</html>
