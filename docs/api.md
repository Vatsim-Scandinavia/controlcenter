---
icon: material/api
---

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
        "facility": [
            {
                "valid_from": "2023-09-16T20:53:00.000000Z",
                "valid_to": null,
                "rating": "T1 ENGM TWR"
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

### GET `/api/bookings`
Returns an array of bookings.

#### Example return as authenticated with API key
```json
{
    "id": 10,
    "source": "CC",
    "vatsim_booking": null,
    "callsign": "EKBI_TWR",
    "position_id": 4,
    "name": "Web Two",
    "time_start": "2024-02-15 12:00:00",
    "time_end": "2024-02-15 13:00:00",
    "user_id": 10000002,
    "training": 1,
    "event": 0,
    "exam": 0,
    "deleted": 0,
    "created_at": "2024-02-04T15:18:44.000000Z",
    "updated_at": "2024-02-04T15:18:44.000000Z"
},
{
    "id": 11,
    "source": "CC",
    "vatsim_booking": null,
    "callsign": "EKCH_DEL",
    "position_id": 10,
    "name": "Web Two",
    "time_start": "2024-02-15 12:00:00",
    "time_end": "2024-02-15 13:00:00",
    "user_id": 10000002,
    "training": 0,
    "event": 0,
    "exam": 0,
    "deleted": 0,
    "created_at": "2024-02-13T20:23:36.000000Z",
    "updated_at": "2024-02-13T20:23:36.000000Z"
}
```

#### Example return as unauthenticated without API key

```json
{
    "id": 10,
    "callsign": "EKBI_TWR",
    "time_start": "2024-02-15 12:00:00",
    "time_end": "2024-02-15 13:00:00",
    "training": 1,
    "event": 0,
    "exam": 0,
    "created_at": "2024-02-04T15:18:44.000000Z",
    "updated_at": "2024-02-04T15:18:44.000000Z"
},
{
    "id": 11,
    "callsign": "EKCH_DEL",
    "time_start": "2024-02-15 12:00:00",
    "time_end": "2024-02-15 13:00:00",
    "training": 0,
    "event": 0,
    "exam": 0,
    "created_at": "2024-02-13T20:23:36.000000Z",
    "updated_at": "2024-02-13T20:23:36.000000Z"
}
```

### GET `/api/positions`
Return an array of positions. No authentication required.

#### Example return
```
{
  "data": [
    {
      "callsign": "EKAH_APP",
      "name": "Aarhus Approach",
      "frequency": "119.280"
    },
    {
      "callsign": "EKAH_TWR",
      "name": "Aarhus Tower",
      "frequency": "118.530"
    },
    {
      "callsign": "EKBI_APP",
      "name": "Billund Approach",
      "frequency": "127.580"
    },
    {
      "callsign": "EKBI_TWR",
      "name": "Billund Tower",
      "frequency": "119.005"
    },
    {
      "callsign": "EKBI_F_APP",
      "name": "Billund Arrival",
      "frequency": "119.250"
    }
}
```

### POST `/api/bookings/create`
Create a booking.

| Variable | Default value | Explanation |
| ------- | --- | --- |
| cid | null | User ID |
| date | null | Date of the booking in `d/m/Y` format |
| start_at | null | Start time of the booking in `24:00` format |
| end_at | null | End time of the booking in `24:00` format |
| position | null | Position callsign |
| tag | null | Tag of the booking, `1` for training, `2` for exam, `3` for event |
| source | null | Source of the booking, `CC` for Control Center bookings |

### PATCH `/api/bookings/{id}`

Update a booking.

| Variable | Default value | Explanation |
| ------- | --- | --- |
| cid | null | User ID |
| date | null | Date of the booking in `d/m/Y` format |
| start_at | null | Start time of the booking in `24:00` format |
| end_at | null | End time of the booking in `24:00` format |
| position | null | Position callsign |
| tag | null | Tag of the booking |

### DELETE `/api/bookings/{id}`
Delete a booking

## Authentication
To create an API key use `php artisan create:apikey`, use the returned token as authentication bearer.

--8<-- "exec-in-container.md"
