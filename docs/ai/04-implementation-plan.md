### Detailed File-Level Implementation Plan for React Native Add-On

This plan details the specific files and changes required to implement the Symfony API and React Native mobile application, maintaining a low-risk, additive approach that preserves the existing web application.

#### 1. Symfony API Layer (Additive Only)

**New Controllers (`src/Controller/Api`)**
*   `ApiSecurityController.php`: Handles `/api/login` (POST) and `/api/logout` (POST). Returns user and league context as JSON.
*   `ApiUserController.php`: Handles `/api/user/me` (GET) to return current user/league info.
*   `ApiPlayerController.php`: Handles `/api/player/list` (GET) and `/api/player/view/{id}` (GET).
*   `ApiSeasonController.php`: Handles `/api/season/list` (GET) and `/api/season/view/{id}` (GET).
*   `ApiEventController.php`: Handles `/api/event/list/{season_id}` (GET), `/api/event/view/{id}` (GET), `/api/event/results/{id}` (GET), and `/api/event/register/{id}` (POST).

**Modified Configuration**
*   `config/packages/security.yaml`: Define a new `api` firewall (using session or a simple token authenticator) to allow stateless-like authentication for the mobile app while keeping the `main` firewall for web users.

#### 2. React Native Application Structure

**Recommended Directory Structure (`mobile/`)**
*   `src/api/`: Axios or Fetch configuration (base URL, interceptors for auth).
*   `src/navigation/`: Navigation container, Stack and Tab navigators.
*   `src/screens/`: One folder per screen (Login, Home, PlayerList, PlayerDetail, SeasonList, SeasonDetail, EventList, EventDetail, EventResults, Help).
*   `src/services/`: Service classes for each entity (PlayerService, SeasonService, EventService) to handle API calls.
*   `src/types/`: TypeScript interfaces corresponding to Symfony response shapes.
*   `src/context/`: AuthContext for managing user login state and league context.
*   `src/components/`: Reusable UI components (CustomHeader, ListItem, ErrorMessage).

#### 3. TypeScript Interfaces (`src/types/index.ts`)

*   `User`: `id, username, roles`
*   `League`: `id, name`
*   `Player`: `id, firstname, lastname, handicapIndex, isDefunct`
*   `Season`: `id, name, startDate, endDate`
*   `Session`: `id, name, events[]`
*   `Event`: `id, eventNumber, startDateTime, description, course, nine, format, isWithHandicapping, isRegistered`
*   `Result`: `id, playerName, teamName, grossScore, netScore, points`

#### 4. React Native Services (`src/services/`)

*   `AuthService.js`: `login(username, password)`, `logout()`, `getCurrentUser()`
*   `PlayerService.js`: `getPlayers()`, `getPlayer(id)`
*   `SeasonService.js`: `getSeasons()`, `getSeason(id)`
*   `EventService.js`: `getEventsBySeason(seasonId)`, `getEvent(id)`, `getResults(id)`, `register(id)`

#### 5. Recommended Order of Implementation

**Phase 1: Foundation (Symfony & Auth)**
1.  Configure the `api` firewall in `security.yaml`.
2.  Implement `ApiSecurityController` and `ApiUserController`.
3.  Set up the React Native project and `AuthContext`.
4.  Implement the **Login** and **Home** screens in the mobile app.

**Phase 2: Core Data (Players & Seasons)**
5.  Implement `ApiPlayerController` and `ApiSeasonController`.
6.  Create `PlayerList`, `PlayerDetail`, `SeasonList`, and `SeasonDetail` screens.
7.  Implement caching for Player names and League info.

**Phase 3: Events & Interactivity**
8.  Implement `ApiEventController` for lists and details.
9.  Create `EventList` and `EventDetail` screens.
10. Implement the registration POST endpoint and the mobile registration toggle.

**Phase 4: Results & Documentation**
11. Implement the `EventResults` API endpoint and mobile screen.
12. Add the **Help** screen (reusing web content if possible).
13. Final UX polish and offline metadata support.

#### 6. Risk Mitigation
*   **Additive Only**: All new PHP code resides in `src/Controller/Api`, ensuring zero conflict with existing controllers.
*   **No Schema Changes**: Reuses existing entities and repositories without modification.
*   **Decoupled Mobile**: The React Native app is a standalone consumer of the API, allowing independent iteration.
*   **Automated Tests**: Each new API endpoint should have a corresponding PHPUnit functional test to verify JSON shapes and auth requirements.
