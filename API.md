# API

## API Endpoints

### GET `/api/users`
Returns a list of users with selected data. To reduce latency and response size, this endpoint is based on providing data you explicitly ask for.

| Variable | Default value | Explanation |
| ------- | --- | --- |
| include | null | Array of relations to include |
| onlyAtcActive | false | Only return users with an active ATC rating |

#### Include relations
Parameters which filter the user selection

| Relation | Explanation |
| ------- | --- |
| allUsers | Include all users in your division who have logged into Control Center |
| endorsements | Include user's endorsements |
| roles | Include user's Control Center roles |
| training | Include user's with active training |

Parameters to add additional fields to the result

| Relation | Explanation |
| ------- | --- |
| name | Include user's full name |
| email | Include user's email |
| divisions | Include user's region, division and subdivision |


#### Example return with all parameters

```json
{
    "id": 10000001,
    "email": "auth.dev1@vatsim.net",
    "first_name": "Web",
    "last_name": "One",
    "rating": "OBS",
    "region": "APAC",
    "division": "PAC",
    "subdivision": "SCA",
    "atc_active": false,
    "endorsements": {
        "visiting": [
            {
                "valid_from": "2023-09-16T20:53:32.000000Z",
                "valid_to": null,
                "rating": "C3",
                "areas": [
                    "Finland"
                ]
            }
        ],
        "examiner": [
            {
                "valid_from": "2023-09-16T20:53:27.000000Z",
                "valid_to": null,
                "rating": "S3",
                "areas": [
                    "Finland"
                ]
            }
        ],
        "training": {
            "solo": null,
            "s1": null
        },
        "masc": [
            {
                "valid_from": "2023-09-16T20:53:00.000000Z",
                "valid_to": null,
                "rating": "MAE ENGM TWR"
            }
        ]
    },
    "roles": {
        "Denmark": [
            "Administrator",
            "Moderator",
            "Mentor"
        ],
        "Finland": null,
        "Iceland": null,
        "Norway": null,
        "Sweden": null
    },
    "training": [
        {
            "area": "Denmark",
            "type": "Transfer",
            "status": 0,
            "status_description": "In queue",
            "created_at": "2021-12-16T10:00:51.000000Z",
            "started_at": null,
            "ratings": [
                "S3"
            ]
        }
    ]
}
```

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