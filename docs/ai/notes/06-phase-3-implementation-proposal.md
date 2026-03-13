### Phase 3: Events Implementation Proposal

This document outlines the planned changes for **Phase 3: Events**, including the Symfony API endpoints and React Native components.

#### 1. Proposed Files to Create or Modify

**Symfony (API)**
- `src/Controller/Api/ApiEventController.php` (New): Handles event listing, viewing details, and registration.

**React Native (Mobile)**
- `mobile/src/types/index.ts` (Modify): Add `Event`, `SessionWithEvents`, and `EventDetail` interfaces.
- `mobile/src/services/EventService.ts` (New): API interaction service for events.
- `mobile/src/screens/EventList/EventListScreen.tsx` (New): Grouped list of events by session.
- `mobile/src/screens/EventDetail/EventDetailScreen.tsx` (New): Detailed view with registration toggle.
- `mobile/src/navigation/AppNavigator.tsx` (Modify): Wiring of new event routes.

---

#### 2. JSON Response Shapes

**Event List by Season (`GET /api/event/list/{season_id}`)**
```json
[
  {
    "id": 10,
    "name": "Session 1",
    "events": [
      {
        "id": 50,
        "eventNumber": 1,
        "startDateTime": "2024-03-15T17:00:00Z",
        "description": "Season Opener",
        "format": "Stroke Play"
      }
    ]
  }
]
```

**Event Detail (`GET /api/event/view/{id}`)**
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

**Registration Response (`POST /api/event/register/{id}`)**
```json
{
  "success": true,
  "isRegistered": true,
  "message": "Successfully registered for the event."
}
```

---

#### 3. API and Mobile UI Alignment
- **Web Flow Mirror**: The mobile app will use a grouped list structure to reflect the Season -> Session -> Event hierarchy found in the web application.
- **Additive Strategy**: New `ApiEventController` will be created to avoid touching existing web controllers, using established repositories for data access.
- **Registration Logic**: Implementation will allow the logged-in user to toggle their registration status for specific events.

---

#### 4. Implementation Status
- **Plan Created**: 2026-03-11
- **Status**: Awaiting Approval.
