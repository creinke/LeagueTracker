Implement only the next approved slice of the missing scoring functionality, in review-first mode.

Goal:
Add the ability for the React Native app to substitute a player in a selected game.

Allowed Symfony work:
- Create only the API endpoint(s) needed to:
    - retrieve substitute-player options for a selected game
    - submit a substitute-player change

Allowed React Native work:
- Create only:
    - the Substitute Player screen or dialog
    - the service methods needed to load substitute options and submit the change
    - the TypeScript types required for the substitute flow
- Add only the navigation wiring strictly required to open the substitute-player flow from the Game List or score-entry screen

Requirements:
1. The user must be able to see the currently assigned player for the relevant game slot
2. The user must be able to choose a substitute player from the allowed options
3. The change must be saved through the API
4. The updated game/player information must be reflected in the mobile flow after save

Constraints:
- Do not modify Twig templates
- Do not alter existing web controller behavior
- Keep changes additive and minimal
- Reuse existing Player-related data structures where practical

Before making edits:
1. List the exact files you propose to create or modify
2. Show the JSON request and response shapes for the substitute-player flow
3. Show the exact diffs
4. Wait for my approval before writing files