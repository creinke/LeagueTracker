Inspect the existing Symfony ROLE_USER scoring flow related to Events, Games, and score entry.

Do not modify files yet.
Do not generate code yet.

I believe the current React Native add-on plan is missing this functionality:

1. User opens Events for a season
2. User selects View for an event
3. The Games associated with the event are displayed
4. Each game has a Post/Edit Scores action
5. Selecting Post/Edit Scores displays all players in the game
6. Existing scores are shown if already entered
7. Blank score inputs are shown if scores are not yet entered
8. User can enter or edit all scores and save
9. User can also substitute any player who played in a game

Please inspect the relevant controllers, routes, templates, and entities for this flow and provide:

1. The exact existing Symfony controllers and methods involved
2. The Twig templates involved
3. The entity relationships involved
4. The exact user flow in the existing web app
5. The data required by the React Native app to support this flow
6. Any assumptions, ambiguities, or risks
7. The minimal API additions required to support this flow in the mobile app

Constraints:
- Keep the existing web app unchanged
- Consider ROLE_USER dialogs only
- Ignore ROLE_ADMIN and ROLE_SAMPLE dialogs
- Do not invent business rules; identify unknowns explicitly

Wait for my approval before making any edits.