# API

## API Endpoints

### GET `/api/users`
Returns a list of users with selected data. To reduce latency and response size, this endpoint is based on providing data you explicitly ask for.

| Variable | Default value | Explanation |
| ------- | --- | --- |
| include | null | Array of relations to include |
| onlyAtcActive | false | Only return users with an active ATC rating |

#### Include relations
| Relation | Explanation |
| ------- | --- |
| divisionUsers | Include all division users |
| name | Include user's full name |
| email | Include user's email |
| divisions | Include user's region, division and subdivision |
| endorsements | Include user's endorsements |
| roles | Include user's roles |
| training | Include user's training |


### GET, POST, PATCH and DELETE `/api/bookings`
Will be documented one day.

## API Endpoints (deprecated)
Older endpoints that will be replaced with the `api/users` endpoint. Do not use these if you're creating a new integration.

- GET users assigned roles and their area `/api/roles`
- GET users holding Major Airport / Special Center endorsements `/api/endorsements/masc`
- GET users holding Training endorsements `/api/endorsements/training/solo` & `/api/endorsements/training/s1`
- GET users holding Examiner endorsements `/api/endorsements/examiner`
- GET users holding Visiting endorsements `/api/endorsements/visiting`

## Authentication
To create an API key use `php artisan create:apikey`, use the returned token as authentication bearer.