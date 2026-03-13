### Summary
- Implemented Phase 4c (Player Substitution) for the React Native mobile add-on.
- Added Symfony API support for fetching the substitution roster and performing player swaps in a specific game.
- Developed a dedicated substitution screen in React Native.

### Changes
- **Symfony API**:
  - Updated `ApiGameController` with two new endpoints:
    - `GET /api/game/roster/{gameId}`: Returns the current players and the full league roster of active players.
    - `POST /api/game/substitute/{gameId}`: Performs the substitution. If players are changed, it resets the `isRecorded` status and removes any existing scores for that game (matching legacy web logic).
- **React Native Application**:
  - Implemented `SubstitutionScreen.tsx` which allows selecting replacement players from the league roster.
  - Updated `GameService` to include `getRoster` and `substitutePlayers` methods.
  - Added "Change Players" buttons to each item in the `GameListScreen`.
  - Registered the `Substitution` route in `AppNavigator`.

### Verification
- Ran Symfony `lint` on `ApiGameController.php` (passed).
- Verified that all changes are additive and maintain isolation from the existing web application's controllers and templates.
- Confirmed that the substitution logic correctly resets scores when players are modified, ensuring data integrity.

### Notes
- This completes the scoring and substitution functionality as originally planned.
- The next step is to perform a final review of the documentation and prepare for final delivery.