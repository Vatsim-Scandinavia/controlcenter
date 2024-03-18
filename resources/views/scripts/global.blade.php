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
        document.querySelectorAll('.user-search').forEach((input) => {
            input.addEventListener('keyup', function(){
                showSpinner(true)
                clearTimeout(timer);
                timer = setTimeout(fetch_users, 200, this.value)
            });
        });

        // When pressing enter on desktop, redirect directly to inputed userID if it's a number
        var userSearchFormDesktop = document.querySelector('#user-search-form-desktop')
        if(userSearchFormDesktop){
            userSearchFormDesktop.addEventListener('submit', function(e){ 
                e.preventDefault();

                var query = parseInt(document.querySelector('.user-search').value);
                if(Number.isInteger(query)){
                    location.assign("{{ route('user.show', '') }}/" + query); 
                }

            });
        }

        // When pressing enter on mobile start the search
        var userSearchFormMobile = document.querySelector('#user-search-form-mobile')
        if(userSearchFormMobile){
            userSearchFormMobile.addEventListener('submit', function(e){
                e.preventDefault() 

                var query = null
                document.querySelectorAll('.user-search').forEach((el) => {
                    if(el.value != '') query = el.value
                });

                showSpinner(true)
        
                clearTimeout(timer);
                timer = setTimeout(fetch_users, 200, query, true)
            });
        }
        

        // Close search results when clicking outside of it
        document.addEventListener("click", function(event) {
            if(event.target.closest('.search-results') == null){
                showResults(false)
                showSpinner(false)
            }
        });
    });
</script>