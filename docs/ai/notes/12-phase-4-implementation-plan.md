### Phase 4: Scoring Flow Implementation Plan

This document outlines the file-level implementation plan for the final phase of the mobile add-on: **Game Listing, Score Entry, and Player Substitution**.

---

### 1. New Symfony API Files to Create

#### **Controllers**
- `src/Controller/Api/ApiGameController.php`
  - `GET /api/game/list/{event_id}`: List all games for an event.
  - `GET /api/game/scores/{game_id}`: Retrieve players, scores, and available tees for a game.
  - `POST /api/game/scores/{game_id}`: Submit/Update scores for all players in a game.
  - `GET /api/game/roster/{game_id}`: Get players eligible for substitution in the game context.
  - `POST /api/game/substitute/{game_id}`: Perform player substitution.

---

### 2. Existing Symfony Files to Modify
- **No modifications expected.** All logic will be encapsulated in the new `ApiGameController`, reusing existing repositories and entities.

---

### 3. New React Native Files to Create

#### **Screens**
- `mobile/src/screens/GameList/GameListScreen.tsx`
- `mobile/src/screens/ScoreEntry/ScoreEntryScreen.tsx`
- `mobile/src/screens/Substitution/SubstitutionScreen.tsx`

#### **Services**
- `mobile/src/services/GameService.ts`

---

### 4. Existing React Native Files to Modify
- `mobile/src/types/index.ts`: Add `Game`, `Score`, `Tee`, and `Substitution` interfaces.
- `mobile/src/navigation/AppNavigator.tsx`: Add routes for `GameList`, `ScoreEntry`, and `Substitution`.
- `mobile/src/screens/EventDetail/EventDetailScreen.tsx`: Add a "View Games" button/link.

---

### 5. TypeScript Types / Interfaces Needed

```typescript
export interface Game {
  id: number;
  gameNumber: number;
  description: string;
  isRecorded: boolean;
  players: {
    id: number;
    name: string;
    isPlayed: boolean;
  }[];
}

export interface ScoreData {
  playerId: number;
  strokes: (number | null)[]; // 9 or 18 holes
  teeId: number;
  isPlayed: boolean;
}

export interface GameScoreState {
  gameId: number;
  nineName: string;
  availableTees: { id: number; name: string }[];
  playerScores: ScoreData[];
}
```

---

### 6. Service Modules Needed
- **`GameService`**:
  - `fetchGames(eventId: number)`
  - `fetchGameScores(gameId: number)`
  - `submitScores(gameId: number, scores: ScoreData[])`
  - `fetchSubstitutionRoster(gameId: number)`
  - `performSubstitution(gameId: number, playerOutId: number, playerInId: number)`

---

### 7. Navigation Changes Needed
- **EventDetail** → **GameList**
- **GameList** → **ScoreEntry** (or **EditScores** if `isRecorded: true`)
- **ScoreEntry** → **Substitution** (via a "Substitute Player" button on the scoring screen)

---

### 8. Recommended Low-Risk Implementation Order

1.  **Phase 4a: Foundation & Listing**
    - Implement `GET /api/game/list/{event_id}`.
    - Create `GameListScreen.tsx` and `GameService.fetchGames`.
    - Link `EventDetail` to `GameList`.

2.  **Phase 4b: Score Entry API**
    - Implement `GET /api/game/scores/{game_id}` and `POST /api/game/scores/{game_id}`.
    - Handle score packing/unpacking in Symfony.
    - Add functional tests for scoring API.

3.  **Phase 4c: Score Entry UI**
    - Create `ScoreEntryScreen.tsx`.
    - Implement dynamic hole input grid and tee selection.
    - Integrate `GameService.submitScores`.

4.  **Phase 4d: Player Substitution**
    - Implement `GET /api/game/roster/{game_id}` and `POST /api/game/substitute/{game_id}`.
    - Create `SubstitutionScreen.tsx`.
    - Integrate substitution into the scoring flow.

---

### Constraints Check
- **Web App Integrity**: All API logic is additive; no shared controllers modified.
- **Minimal Surface**: Reuses existing `GameScoresFormBean` logic where possible without modifying the bean itself.
- **Phased Approach**: Each sub-phase is independently testable.

**Waiting for approval before proceeding to implementation.**
