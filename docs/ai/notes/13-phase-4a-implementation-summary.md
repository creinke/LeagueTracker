### Summary
- Implemented Phase 4a (Event Games List) of the React Native mobile add-on.
- Added a Symfony API endpoint to retrieve games for a specific event and developed the mobile UI to display them.

### Changes
- **Symfony API**:
    - Created `ApiGameController` with the `GET /api/game/list/{eventId}` endpoint.
    - Implemented logic to extract player information from `playermatches` (Match Play) or directly from `players` (fallback).
- **Mobile Add-On**:
    - Defined the `Game` interface in `mobile/src/types/index.ts`.
    - Created `GameService` to fetch games from the new API endpoint.
    - Developed `GameListScreen` to display a list of games with starting times, recording status, and player names.
    - Integrated `GameList` into the navigation stack in `AppNavigator.tsx`.
    - Added a "View Games" button to the `EventDetailScreen` to allow navigation to the game list.

### Verification
- Ran Symfony `lint` on `ApiGameController.php`; no errors found.
- Verified that all changes are additive and maintain isolation from the existing web application.
- Confirmed that the mobile UI correctly handles navigation and displays the expected game data structure.

### Notes
- Score entry and player substitution buttons in `GameListScreen` currently show a "Coming Soon" alert as they are planned for subsequent phases.
