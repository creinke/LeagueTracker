Implement only the next approved slice of the missing scoring functionality, in review-first mode.

Goal:
Add the ability for the React Native app to display and save scores for all players in a selected game.

Allowed Symfony work:
- Create only the API endpoint(s) needed to:
    - retrieve the score-entry details for a selected game
    - save posted or edited scores for that game

Allowed React Native work:
- Create only:
    - the Post/Edit Scores screen
    - the service methods needed to load and save scores
    - the TypeScript types required for score entry
- Add only the navigation wiring strictly required to open the score-entry screen from the Game List

Requirements:
1. The score-entry screen must display all players in the selected game
2. Existing scores must be shown if already present
3. Blank score inputs must be shown if scores are not yet entered
4. User must be able to edit all scores and tap Save
5. The mobile UI should mirror the logical intent of the current Twig workflow

Constraints:
- Do not implement substitute-player functionality yet
- Do not modify Twig templates
- Do not alter existing web controller behavior
- Keep changes additive and minimal

Before making edits:
1. List the exact files you propose to create or modify
2. Show the JSON request and response shapes for:
    - score-entry detail
    - save scores
3. Show the exact diffs
4. Wait for my approval before writing files