### Phase 4b Implementation Summary: Post/Edit Scores

Implemented the ability for the React Native app to display and save scores for all players in a selected game, mirroring the logical intent of the Symfony web workflow.

#### Symfony API Additions
- **`ApiGameController`**:
    - `GET /api/game/scores/{gameId}`: Retrieves full score-entry details including:
        - Hole definitions (number, par, handicap) for the Nines played.
        - Player details with current strokes, "played" status, and available tees.
    - `POST /api/game/scores/{gameId}`: Saves submitted scores by:
        - Mapping the JSON payload back to `GameScoresFormBean`.
        - Updating `ScoreBean` state and syncing with `ScoreDE`, `PlayermatchDE`, and `GameDE` entities.
        - Marking the game as "Recorded".

#### React Native Mobile Add-On
- **TypeScript Types**: Defined `ScoreEntryDetails`, `PlayerScore`, `NineInfo`, `TeeInfo`, and `HoleInfo` to ensure data consistency.
- **`GameService`**: Added `getGameScores` and `saveGameScores` methods.
- **`ScoreEntryScreen`**: A new interactive screen featuring:
    - A scrollable interface showing all players in the game.
    - An editable grid for hole-by-hole stroke entry (supports integers 0-15 and nulls).
    - A "Played" toggle to mark attendance.
    - A Tee selection picker to change the active tee for any player.
    - Dynamic calculation of total strokes (gross).
    - Save/Update functionality with validation and user feedback.
- **Navigation**: Updated `AppNavigator` and `GameListScreen` to enable seamless transitions to the scoring interface.

#### Verification & Constraints
- Ran Symfony `lint` on `ApiGameController.php` (no errors).
- Verified that all changes are strictly additive and do not affect the existing Symfony web application or Twig templates.
- Ensured that "Substitute Player" functionality remains unimplemented in this slice as per constraints.
