### Phase 2 Implementation Proposal Summary

This document summarizes the proposed implementation details for **Phase 2: Core Data** of the React Native add-on, focusing on Player and Season data.

#### 1. Proposed Symfony API Changes
- **New Controllers**:
    - `src/Controller/Api/ApiPlayerController.php`: Endpoints for `GET /api/player/list` and `GET /api/player/view/{id}`.
    - `src/Controller/Api/ApiSeasonController.php`: Endpoints for `GET /api/season/list` and `GET /api/season/view/{id}`.
- **Security**: All endpoints are protected by `ROLE_USER` and scoped to the authenticated user's league.

#### 2. Proposed React Native Changes
- **Type Definitions**: Added `Player`, `PlayerDetail`, `Season`, and `SeasonDetail` interfaces to `mobile/src/types/index.ts`.
- **Services**:
    - `mobile/src/services/PlayerService.ts`: Axios calls for player data.
    - `mobile/src/services/SeasonService.ts`: Axios calls for season data.
- **Screens**:
    - `PlayerListScreen`: List of active players in the league.
    - `PlayerDetailScreen`: Detailed profile, contact, and handicap info.
    - `SeasonListScreen`: Chronological list of seasons.
    - `SeasonDetailScreen`: Season summary and session listing.
- **Navigation**: Updated `AppNavigator.tsx` to include routes for the new screens.

#### 3. API Response Shapes
- **Player List**: Array of basic player info (ID, Name, Status, Seed Handicap).
- **Player Detail**: Full profile including contact info and nested address object.
- **Season List**: Array of season metadata (Name, Dates).
- **Season Detail**: Season info plus an array of associated sessions.

#### 4. Implementation Strategy
- **Additive-Only**: No modifications to existing web controllers or Twig templates.
- **Reuse**: Leveraging existing `PlayerRepository` and `SeasonRepository` methods.
- **Security Isolation**: Using the `api` firewall established in Phase 1.
