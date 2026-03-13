### Analysis of Symfony ROLE_USER Scoring & Player Substitution Flow

#### 1. Exact Symfony Controllers and Methods Involved
*   **Scoring (Post/Edit)**:
    *   `GameController::postScores(Request $request, $gamenumber, $event_id, $game_id)`: Primary entry point for display and processing.
    *   `GameController::buildGameScoresForm(...)`: Form construction using `GameScoresFormBean`.
*   **Player Substitution**:
    *   `GameController::changePlayers(Request $request, $event_id, $game_id, $gamenumber)`: Handles logic for substituting players.
    *   `GameController::buildChangeGamePlayersForm(...)`: Form construction using `ChangeGamePlayersFormBean`.

#### 2. Twig Templates Involved
*   `game/post.scores.html.twig`: Stroke entry, tee selection, and "played" status toggle.
*   `game/change.players.html.twig`: Substitute player selection.

#### 3. Entity Relationships Involved
*   **EventDE** → Has many **GameDE**.
*   **GameDE** → Has many **PlayermatchDE** or direct link to **PlayerDE**.
*   **PlayermatchDE** → Links two **PlayerDE** and holds their **ScoreDE** records in a `ManyToMany` relationship (`playermatch_scores`).
*   **ScoreDE** → Contains strokes (packed string), tee, handicap, and `duplicateScore` flag.
*   **GameDE** → Tracks if the game is recorded via the `recorded` flag.

#### 4. Exact User Flow in Existing Web App
1.  **Event View**: Select a game.
2.  **Navigation**: Click "Post Scores" or "Edit Scores".
3.  **Substitution**: (Optional) Use "Change Players" to swap players before score entry.
4.  **Score Entry**: Enter strokes per hole and select tees. Toggle "Played" status (if false, system penalty or partner copy logic applies).
5.  **Submission**: System validates, calculates, packs strokes, and saves `ScoreDE` records.

#### 5. Data Required by React Native App
*   **Game Context**: Event ID, Game ID, Game Number, and Event Type.
*   **Player Metadata**: Names and current IDs.
*   **Scoring Data**: Nines list, existing strokes (unpacked), available tees, "Played" status.
*   **League Roster**: Active players for substitutions.

#### 6. Assumptions, Ambiguities, or Risks
*   **Score Packing**: API must handle the `pack`/`unpack` conversion to simple integer arrays for mobile.
*   **Substitution Risk**: `changePlayers` clears existing scores for the game.
*   **Handicap Logic**: Server-side calculation is preferred over mobile-side math.
*   **User-Player Link**: A robust link between `UserDE` and `PlayerDE` is required for permissions/registration logic.

#### 7. Minimal API Additions Required
| Endpoint | Method | Purpose |
| :--- | :--- | :--- |
| `GET /api/game/scores/{game_id}` | `GET` | Retrieve current scores, players, and available tees. |
| `POST /api/game/scores/{game_id}`| `POST`| Submit/Update scores for all players in the game. |
| `GET /api/game/roster/{game_id}` | `GET` | Get players eligible for substitution. |
| `POST /api/game/substitute/{game_id}`| `POST`| Perform player substitution. |
