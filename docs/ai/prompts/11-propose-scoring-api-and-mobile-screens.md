Based on the approved analysis of the existing scoring flow, propose the additive Symfony API endpoints and React Native screens needed to support this missing functionality.

Do not modify files yet.
Do not generate code yet.

Please propose:

## API
1. The minimal API endpoints required for:
    - viewing games for an event
    - viewing score-entry details for a game
    - posting or editing scores for a game
    - substituting a player in a game
2. The HTTP methods and route patterns
3. The JSON request and response shapes
4. Which endpoints should be read-only versus write endpoints

## React Native
5. The screens or dialogs required
6. The navigation flow from:
    - Season List
    - Event List
    - Event View
    - Game List
    - Post/Edit Scores
    - Substitute Player
7. The exact data needed on each screen
8. Any reuse opportunities with existing Event or Player screens

Constraints:
- Keep all changes additive
- Do not modify existing Twig templates
- Do not alter existing web controller behavior
- Reuse existing Symfony logic where practical
- Prefer the smallest possible implementation

Wait for my approval before making any edits.