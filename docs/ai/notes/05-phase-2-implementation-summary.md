### Phase 2 Implementation Summary: Core Data (Players and Seasons)

Phase 2 of the React Native add-on implementation has been completed, focusing on the core data entities: Players and Seasons.

#### Symfony API Changes
- **ApiPlayerController**:
  - `GET /api/player/list`: Returns all active players for the authenticated user's league.
  - `GET /api/player/view/{id}`: Returns detailed information for a specific player, including address and contact info.
- **ApiSeasonController**:
  - `GET /api/season/list`: Returns all seasons associated with the user's league.
  - `GET /api/season/view/{id}`: Returns season details and the list of sessions within that season.

#### React Native Mobile Changes
- **Types**: Added `Player`, `PlayerDetail`, `Season`, and `SeasonDetail` TypeScript interfaces.
- **Services**:
  - `PlayerService`: Handles API calls for player list and details.
  - `SeasonService`: Handles API calls for season list and details.
- **Screens**:
  - `PlayerListScreen`: Displays a list of players with navigation to details.
  - `PlayerDetailScreen`: Shows full profile, contact, and address info for a player.
  - `SeasonListScreen`: Displays a list of seasons with navigation to details.
  - `SeasonDetailScreen`: Shows season dates and its associated sessions.
- **Navigation**:
  - Updated `AppNavigator` with new routes.
  - Updated `HomeScreen` with menu buttons to access Players and Seasons.

#### Verification
- All new PHP controllers passed `lint` checks.
- API endpoints follow the additive-only constraint and reuse existing repositories.
- React Native components use the established `AuthContext` and Axios client for authenticated requests.
