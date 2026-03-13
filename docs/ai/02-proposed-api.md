### Proposed Symfony API Layer for React Native Add-On

This proposal outlines the minimal, additive API layer required to support the React Native mobile add-on, reusing existing business logic and ensuring the current web application remains unchanged.

#### 1. Authentication & League Context
The API will use session-based authentication (reusing the existing Symfony security configuration) or can be extended with JWT if preferred. The league context is established upon login.

| Endpoint | Method | Pattern | Purpose | Authentication |
| :--- | :--- | :--- | :--- | :--- |
| **Login** | `POST` | `/api/login` | Authenticate user and return user/league info. | None |
| **Logout** | `POST` | `/api/logout` | Invalidate the session. | `ROLE_USER` |
| **User Info** | `GET` | `/api/user/me` | Return current user details and active league. | `ROLE_USER` |

#### 2. Core API Endpoints (ROLE_USER)

| Endpoint | Method | Pattern | Purpose |
| :--- | :--- | :--- | :--- |
| **Player List** | `GET` | `/api/player/list` | List all active players in the league. |
| **Player Detail** | `GET` | `/api/player/view/{id}`| Detailed profile and handicap info for a player. |
| **Season List** | `GET` | `/api/season/list` | List all seasons in the league. |
| **Season Detail** | `GET` | `/api/season/view/{id}`| Details of a season, including sessions and events. |
| **Event List** | `GET` | `/api/event/list/{season_id}` | List all events for a specific season. |
| **Event Detail** | `GET` | `/api/event/view/{id}` | Detailed configuration for a specific event. |
| **Event Results** | `GET` | `/api/event/results/{id}` | Scores and rankings for a completed event. |
| **Event Register**| `POST`| `/api/event/register/{id}`| Register the logged-in user for an event. |

---

#### 3. JSON Response Shapes (Examples)

**Login / User Info (`/api/user/me`)**
```json
{
  "user": {
    "id": 1,
    "username": "jdoe",
    "roles": ["ROLE_USER"]
  },
  "league": {
    "id": 10,
    "name": "Friday Night Hackers"
  }
}
```

**Player List (`/api/player/list`)**
```json
[
  {
    "id": 101,
    "firstname": "John",
    "lastname": "Smith",
    "handicapIndex": 12.4,
    "isDefunct": false
  }
]
```

**Season List (`/api/season/list`)**
```json
[
  {
    "id": 5,
    "name": "2024 Spring Season",
    "startDate": "2024-03-01",
    "endDate": "2024-05-31"
  }
]
```

**Event Detail (`/api/event/view/{id}`)**
```json
{
  "id": 50,
  "eventNumber": 1,
  "startDateTime": "2024-03-15T17:00:00Z",
  "description": "Season Opener",
  "course": "Oak Ridge",
  "nine": "Front 9",
  "format": "Stroke Play",
  "isWithHandicapping": true,
  "isRegistered": false
}
```

---

#### 4. Implementation Details & Constraints
*   **Active League Context**: The `/api/login` and `/api/user/me` endpoints return the `league_id`. This ID must be cached by the mobile app and potentially sent as a header or used to filter subsequent requests if necessary (though the server-side `User` entity already provides the league context).
*   **Mobile Caching**:
    *   **League Info**: Cache indefinitely until logout.
    *   **Player List**: Cache with a short TTL (e.g., 1 hour) or refresh on demand.
    *   **Season/Event Metadata**: Cache until the next login or manual refresh.
*   **Reuse Strategy**: New `ApiController` classes will be created (e.g., `src/Controller/Api/PlayerApiController.php`). These will call the same repositories and services used by the existing controllers but return `JsonResponse` instead of rendering Twig templates.
*   **Additive Only**: No changes to existing `@Route` definitions in `PlayerController`, `SeasonController`, etc. New routes will be prefixed with `/api`.
