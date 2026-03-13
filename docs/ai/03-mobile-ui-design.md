### React Native Mobile Application Screen Architecture

This proposal defines the screen structure, navigation flow, and data requirements for the React Native mobile add-on, based on the approved Symfony API design and the existing ROLE_USER web flow.

#### 1. List of Screens Required

| Screen Name | Type | Purpose | Corresponding Twig Template |
| :--- | :--- | :--- | :--- |
| **Home** | Tab/Stack | General league info and landing point. | `home/index.html.twig` |
| **Login** | Modal/Stack | User authentication. | `security/login.html.twig` |
| **Player List**| Tab/Stack | List of all players in the active league. | `player/list.html.twig` |
| **Player Detail**| Stack | Detailed profile and handicap index. | `player/view.html.twig` |
| **Season List**| Tab/Stack | List of all seasons in the league. | `season/list.html.twig` |
| **Season Detail**| Stack | Summary of a season and its sessions. | `season/view.html.twig` |
| **Event List** | Stack | List of events for a specific season. | `event/list.html.twig` |
| **Event Detail**| Stack | Detailed event configuration and registration. | `event/view.html.twig` |
| **Event Results**| Stack | Scores and rankings for a completed event. | `event/results.html.twig` |
| **Help** | Tab/Stack | Application documentation. | `help/help.html.twig` |

#### 2. Navigation Flow

*   **Initial Route**: **Home** screen.
*   **Authentication Flow**:
    *   From any screen, if not authenticated, the **Login** screen can be accessed via the menu or triggered by a 401 response.
    *   Successful login redirects to **Home** (or the previous screen) and refreshes the league context.
*   **Data Navigation**:
    *   **Player List** → (Select Player) → **Player Detail**
    *   **Season List** → (Select Season) → **Season Detail**
    *   **Season Detail** → (Select Session/View Events) → **Event List**
    *   **Event List** → (Select Event) → **Event Detail**
    *   **Event Detail** → (If Recorded) → **Event Results**
*   **Menu/Tabs**: A bottom tab bar or side drawer for top-level navigation (Home, Players, Seasons, Help).

#### 3. Menu Behavior

| State | Menu Items |
| :--- | :--- |
| **Before Login** | Home, Login, Help |
| **After Login** | Home, Players, Seasons, Help, Logout |

#### 4. API Endpoint & Data Mapping

| Screen | API Endpoint | Minimal Data Required |
| :--- | :--- | :--- |
| **Home** | `/api/user/me` | League Name, Welcome message. |
| **Login** | `/api/login` | Username, Password (POST). |
| **Player List**| `/api/player/list` | Player ID, First/Last Name, Handicap Index. |
| **Player Detail**| `/api/player/view/{id}`| ID, Full Name, Handicap Info, Scoring History. |
| **Season List**| `/api/season/list` | ID, Name, Start/End Dates. |
| **Season Detail**| `/api/season/view/{id}`| ID, Name, List of Sessions (IDs, Names). |
| **Event List** | `/api/event/list/{season_id}`| ID, Event Number, Date/Time, Description. |
| **Event Detail**| `/api/event/view/{id}` | ID, Format, Course/Nine, Start Time, `isRegistered`. |
| **Event Results**| `/api/event/results/{id}` | Player/Team names, Scores (Gross/Net), Points. |

#### 5. Recommended Simplifications for Mobile

1.  **Tab-Based Navigation**: Instead of the web's header menu, use a bottom tab bar for the most frequent actions (Home, Players, Seasons) to improve thumb-reachability.
2.  **Flat List Views**: Replace complex tables (like in the Player List) with card-based layouts or simple list items with secondary text for handicap info.
3.  **Registration Toggle**: In **Event Detail**, use a simple "Register" button or toggle switch instead of a multi-step form.
4.  **Condensed Results**: For **Event Results**, prioritize the leaderboard (Name, Net Score, Points) and allow expanding a row to see hole-by-hole details if necessary, rather than showing a full scorecard by default.
5.  **Offline Metadata**: Cache the League ID and Player Names locally to allow the app to feel responsive even with slow connectivity.
6.  **Context Persistence**: The "Active League" should be persistent across app restarts until the user logs out.
