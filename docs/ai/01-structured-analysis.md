### Structured Analysis of ROLE_USER Web Flow

#### 1. User Flow Before Login
*   **Home Page (`/`)**: Displays general information about the application.
*   **Login Page (`/login`)**: Allows users to enter credentials (username and password).
*   **Help Page (`/help`)**: Provides general assistance and documentation.

#### 2. User Flow After Login
*   **Home Page**: Remains accessible, often as a landing point.
*   **Players (`/player/list`)**: Displays a list of all players associated with the active league.
    *   **Player View (`/player/view/{id}`)**: Detailed information about a specific player.
*   **Seasons (`/season/list`)**: Shows all seasons within the active league.
    *   **Season View (`/season/view/{id}`)**: Displays details for a season, including its sessions and events.
*   **Events (`/event/list/{season_id}`)**: Lists events for a specific season.
    *   **Event View (`/event/view/{id}`)**: Details for a specific event (date, time, course, format).
    *   **Event Results (`/event/results/{id}`)**: Shows the scores and rankings for a completed event.
    *   **Event Registration (`/event/register/{id}`)**: Allows the user to register for an upcoming event.
*   **Help (`/help`)**: Accessible throughout the session.
*   **Logout (`/logout`)**: Ends the session and returns the user to the "Before Login" state.

#### 3. Main Dialogs/Screens (Implied by Twig & Controllers)
1.  **Home Screen**: Simple informational landing page.
2.  **Login Dialog**: Form with username, password, and error feedback.
3.  **Player List**: Table/list of names, potentially with contact info or status.
4.  **Player Detail**: Profile-style view with all player entity fields.
5.  **Season List**: Chronological list of seasons (names, start/end dates).
6.  **Season Detail**: Summary of the season and a navigation point to its events.
7.  **Event List**: Grouped by session, showing event numbers and dates.
8.  **Event Detail**: Detailed event configuration (Course, Nine, Format, Start Time).
9.  **Event Results**: Complex data display (Stroke Play, Match Play, or Team results) including scores and points.
10. **Event Registration**: Form for confirming attendance at an event.
11. **Help Screen**: Rich text documentation.

#### 4. Entity Relationships
*   **UserDE** → Belongs to exactly one **LeagueDE**.
*   **LeagueDE** → Contains multiple **Seasons**, **Players**, and **Teams**.
*   **SeasonDE** → Belongs to a **League**; contains multiple **Sessions**.
*   **SessionDE** → Belongs to a **Season**; contains multiple **Events**.
*   **EventDE** → Belongs to a **Session**; associated with a **Course** and **Nine**; contains multiple **Games** (Singles) or **Teamgames** (Team events).
*   **GameDE / TeamgameDE** → Belongs to an **Event**; involves multiple **Players** (via scores or direct association).
*   **TeamDE** → Belongs to a **League**; contains a collection of **Players**.

#### 5. Minimum Data Requirements
*   **Login**: Username (string), Password (string).
*   **League Context**: `id` (long), `name` (string).
*   **Player List**: `id`, `firstname`, `lastname`, `defunct` status.
*   **Season List**: `id`, `name`, `startdate`, `enddate`.
*   **Event List**: `id`, `eventnumber`, `startdateandtime`, `description`.
*   **Event Result**: Event ID, Game/Teamgame records, Player/Team names, scores (gross/net), points awarded.
*   **Registration**: User ID, Event ID, registration status.

#### 6. Unclear or Ambiguous Business Rules
*   **Handicapping Logic**: The `PlayerController` has a `handicap` method, and `EventDE` has a `withhandicapping` flag. The exact calculation or data source for mobile display is unclear.
*   **Event Formats**: There are many formats (Stroke Play, Match Play, Scramble, etc.). The mobile app needs to know which result template to use dynamically.
*   **League Switch**: The spec implies a user has one active league, but the schema allows a user to belong to a league. It's unclear if a user could ever belong to multiple leagues and need a "Switch League" feature.

#### 7. Confirmed Business Rules & Assumptions
*   **Active League**: Once a user logs in, their `league_id` is immutable for that session and acts as a global filter for all other data.
*   **Real-time Results**: "Results" are only viewable after an event is marked as "Recorded" or "Completed."
*   **Mobile Registration**: The mobile app only needs to allow the logged-in user to register themselves, not others.
*   **Offline Access**: The mobile app will primarily be online, but the "cached league context" implies some level of offline metadata persistence.
