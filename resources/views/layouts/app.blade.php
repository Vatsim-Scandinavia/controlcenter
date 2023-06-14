
<!DOCTYPE html>
<html lang="en">
    <head>
        @include('layouts.header')
    </head>

    <body>
    <div id='app'></div>

    <!-- Page Wrapper -->
    <div id="wrapper">

        @auth
            @include('layouts.sidebar')
        @endauth

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            @auth
                @include('layouts.topbar')
            @endauth

            @yield('content-master') <!-- For special things to be done outside the container -->

            <!-- Begin Page Content -->
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
            <!-- /.container-fluid -->

        </div>
        <!-- End of Main Content -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Bootstrap core JavaScript-->
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
        $(document).ready(function(){

            var currentRequest = null;
            
            function fetch_users(query = '')
            {
                
                if(currentRequest != null){
                    currentRequest.abort();
                }

                currentRequest = $.ajax({
                    url:"{{ route('user.search') }}",
                    method:'GET',
                    data:{query:query},
                    dataType:'json',
                    success: function(data)
                    {
                        if(data.length > 0){

                            var html = '';
                            var baseUrl = '{{ URL::to('/user') }}\/'

                            for(var i=0; i < data.length; i++){
                                html +='<a href="'+ baseUrl + data[i]['id'] +'">'+ data[i]['id'] + ": "+ data[i]['name'] +'</a>'
                            }

                            $('.search-results').html(html);
                        } else {
                            $('.search-results').html("<a href='#''>No results</a>");
                        }

                        $('.search-results').slideDown("fast");
                        $('.search-spinner').removeClass('search-spinner-visible');
                    },
                    error: function(){
                        $('.search-results').html("<a href='#''>No results</a>");
                        $('.search-results').slideDown("fast");
                        $('.search-spinner').removeClass('search-spinner-visible');
                    }
                })
            }
    
            var timer = null
            $('.search .search-input').keyup(function(){
                var query = $(this).val();
                $('.search-spinner').addClass('search-spinner-visible');
                
                clearTimeout(timer);
                timer = setTimeout(fetch_users, 200, query)
            });

            // When pressing enter on desktop, redirect directly to inputed userID if it's a number
            // otherwise just prevent and let ajax do its thing.
            $('#user-search-form-desktop').on('submit', function(e){ 
                e.preventDefault();

                var query = parseInt($('.search-input').val());
                if(Number.isInteger(query)){
                    location.assign("{{ route('user.show', '') }}/" + query); 
                }

            });

            // When pressing enter on mobile start the search
            $('#user-search-form-mobile').on('submit', function(e){
                $('.search-spinner').addClass('search-spinner-visible');
                
                var query = $('.search-input').val();
                clearTimeout(timer);
                timer = setTimeout(fetch_users, 200, query)

                e.preventDefault() 
            });

            $(document).on("click", function(event) {
                var obj = $(".search-results");
                if (!$(event.target).closest(obj).length) {
                    
                    $('.search-results').slideUp("fast");
                    $('.search-spinner').removeClass('search-spinner-visible');
                
                }
            });

        });
    </script>

    @yield('js')
    </body>
</html>
