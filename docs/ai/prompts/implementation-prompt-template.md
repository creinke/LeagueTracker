# [Short Task Title]

## Context

Describe the feature area and where this behavior lives.

Example:
This task concerns the React Native mobile add-on scoring workflow for Games.
The relevant flow is:
Season List -> Event List -> Event View -> Game List -> Enter Scores -> Substitute Players

## Current Behavior

Describe exactly what the app does now.

Be concrete and observable.

Example:
- The Substitute Players screen opens.
- The current player rows are displayed.
- Tapping "Tap to Change Player" does not correctly present a selectable player list.
- Saving substitutions does not fully update the game state and return to Enter Scores with the updated player list.

## Desired Behavior

Describe exactly what should happen, step by step.

Example:
1. User opens the Substitute Players screen for a Game.
2. User taps "Tap to Change Player" for a player position.
3. A selectable list of valid replacement players appears.
4. User selects a player.
5. That player becomes the new player in that position in the UI.
6. User may repeat this for multiple positions.
7. User taps "Save Substitutions".
8. The updated player assignments are saved for the Game.
9. Any existing scores for that Game are deleted.
10. The app returns to the Enter Scores screen for the same Game.
11. The updated player list is shown.
12. Score inputs are empty because prior scores were deleted.

## Business Rules

List explicit business rules that must be preserved.

Example:
- Preserve Game ID.
- Preserve player position ordering.
- Only valid available players may be selected.
- Existing scores for the Game must be deleted when substitutions are saved.
- The web application must remain unchanged.

## Files / Components in Scope

List the files or areas Junie should inspect first.

Example:
- React Native screen for Substitute Players
- React Native Enter Scores screen
- React Native Game service / API client
- Symfony API controller for substitute-player updates
- Symfony score update/delete logic
- Relevant TypeScript types/interfaces
- Relevant navigation wiring

## Constraints

State what must not happen.

Example:
- Do not modify Twig templates.
- Do not change existing web controller behavior.
- Do not invent API endpoints or business rules without identifying them as assumptions.
- Keep changes additive and minimal.
- Reuse existing API patterns where practical.

## What I Want You To Do First

Force analysis before edits.

Example:
Before making edits, analyze the current implementation and provide:
1. The exact files involved
2. The current behavior you found
3. The gap between current and desired behavior
4. The exact files you propose to modify
5. Any API changes required
6. Any risks or assumptions

## Deliverables Before Editing

Require review-first output.

Example:
Provide:
- Architecture explanation
- File change list
- Proposed API request/response shape
- Proposed navigation/state-flow changes
- Exact diffs or code snippets
- Assumptions and risks

Wait for my approval before making any edits.