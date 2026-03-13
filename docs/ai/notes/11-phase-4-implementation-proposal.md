### Phase 4: Scoring and Player Substitution Implementation Proposal

Based on the analysis of the existing web scoring flow, this proposal outlines the additive Symfony API endpoints and React Native screens required to support game listing, score entry, and player substitution.

---

### 1. API Proposal

The following endpoints will be added to a new `ApiGameController.php`.

| Endpoint | Method | Purpose | Read/Write |
| :--- | :--- | :--- | :--- |
| `/api/game/list/{event_id}` | `GET` | View all games for a specific event. | Read-only |
| `/api/game/scores/{game_id}` | `GET` | Retrieve score-entry details (players, current scores, available tees). | Read-only |
| `/api/game/scores/{game_id}` | `POST` | Post or edit scores for all players in a game. | Write |
| `/api/game/roster/{game_id}` | `GET` | Get players eligible for substitution in a specific game. | Read-only |
| `/api/game/substitute/{game_id}`| `POST` | Substitute a player in a game. | Write |

#### JSON Request and Response Shapes

**Game List (`GET /api/game/list/{event_id}`)**
```json
[
  {
    "id": 201,
    "gameNumber": 1,
    "isRecorded": false,
    "players": [
      { "id": 101, "name": "John Smith" },
      { "id": 102, "name": "Jane Doe" }
    ]
  }
]
```

**Score Entry Details (`GET /api/game/scores/{game_id}`)**
```json
{
  "gameId": 201,
  "isRecorded": false,
  "eventFormat": "Stroke Play",
  "nines": [
    {
      "id": 1,
      "name": "Front 9",
      "holes": [1, 2, 3, 4, 5, 6, 7, 8, 9],
      "tees": [
        { "id": 10, "name": "Blue", "color": "#0000FF" },
        { "id": 11, "name": "White", "color": "#FFFFFF" }
      ]
    }
  ],
  "playerScores": [
    {
      "playerId": 101,
      "playerName": "John Smith",
      "teeId": 10,
      "isPlayed": true,
      "strokes": [4, 5, 4, 3, 5, 4, 4, 5, 4]
    }
  ]
}
```

**Post Scores (`POST /api/game/scores/{game_id}`)**
```json
{
  "playerScores": [
    {
      "playerId": 101,
      "teeId": 10,
      "isPlayed": true,
      "strokes": [4, 5, 4, 3, 5, 4, 4, 5, 4]
    }
  ]
}
```

**Substitution Roster (`GET /api/game/roster/{game_id}`)**
```json
{
  "currentGamePlayers": [
    { "id": 101, "name": "John Smith" },
    { "id": 102, "name": "Jane Doe" }
  ],
  "availableSubstitutes": [
    { "id": 105, "name": "Bob Brown" },
    { "id": 106, "name": "Alice Green" }
  ]
}
```

**Substitute Player (`POST /api/game/substitute/{game_id}`)**
```json
{
  "playerIdToReplace": 101,
  "newPlayerId": 105
}
```

---

### 2. React Native Proposal

#### Screens and Dialogs
1.  **GameListScreen**: Displays a list of games for an event.
2.  **ScoreEntryScreen**: A grid/form for entering strokes for all players in a game.
3.  **SubstitutionScreen**: A selection interface for swapping players.

#### Navigation Flow
*   **Season List** → **Event List** (Phase 2/3)
*   **Event List** → **Event View** (Phase 3)
*   **Event View** → **Game List** (New)
*   **Game List** → **Score Entry** (New)
*   **Score Entry** → **Substitution** (New - can also be linked from Game List)

#### Data Requirements
*   **Game List**: Event ID, Game IDs, Player Names, "Recorded" status.
*   **Score Entry**: Game ID, Player IDs, Names, Tee IDs/Names, Nine Info (Holes), Current Strokes, "Played" status.
*   **Substitution**: Game ID, Current Player IDs/Names, Full League Roster (filtered for substitution context).

#### Reuse Opportunities
*   **Player List UI**: The `SubstitutionScreen` can reuse the list component from `PlayerListScreen` for selecting the new player.
*   **Loading/Error States**: Shared components across all screens.

---

### 3. Constraints Check
*   **Additive**: All new controllers and screens.
*   **No Twig/Web Changes**: Reuses `GameScoresFormBean` logic internally within the new `ApiGameController` without affecting existing web routes.
*   **Minimal Implementation**: Focused strictly on the data needed for mobile score entry.

**Wait for my approval before making any edits.**
