# API

## API Endpoints
- GET, POST, PATCH and DELETE bookings `/api/bookings` and more
- GET users assigned roles and their area `/api/roles`
- GET users holding Major Airport / Special Center endorsements `/api/endorsements/masc`
- GET users holding Training endorsements `/api/endorsements/training/solo` & `/api/endorsements/training/s1`
- GET users holding Examiner endorsements `/api/endorsements/examiner`
- GET users holding Visiting endorsements `/api/endorsements/visiting`

## Authentication
To create an API key use `php artisan create:apikey`, use the returned token as authentication bearer.