# Testing Guide: League Tracker React Native Mobile Add-On

This guide provides step-by-step instructions for testing all features of the React Native mobile application and its corresponding Symfony API layer.

---

## 1. Environment Setup & Prerequisites

### API Configuration
Before starting the mobile app, ensure it can communicate with your local Symfony server:
1.  **Identify your Local IP**: Run `ipconfig` (Windows) to find your IPv4 address (e.g., `192.168.1.5`).
2.  **Update Mobile Client**: Open `mobile/src/api/client.ts` and update the `baseURL` to use your IP:
    ```typescript
    const apiClient = axios.create({
      baseURL: 'http://192.168.1.5/leaguetracker7/public/index.php',
    });
    ```
3.  **Symfony Server**: Ensure your WAMP/LAMP stack is running and the `.env` file points to the correct database.

### Mobile App Launch
1.  Navigate to the `mobile/` directory: `cd mobile`
2.  Install dependencies: `npm install`
3.  Start Expo: `npx expo start`
4.  **Connect Device**: Scan the QR code with **Expo Go** (iOS/Android).

---

## 2. Feature-by-Feature Testing

### Phase 1: Authentication & Identity
*   **Login**:
    *   Enter valid credentials (e.g., `member` / `password`).
    *   Verify redirection to the **Home** screen.
    *   **Negative Test**: Enter an incorrect password and verify the "Invalid credentials" error message.
*   **Persistence**:
    *   Close the app and reopen it. Verify you remain logged in.
*   **Logout**:
    *   Tap "Logout" in the menu. Verify you are returned to the **Login** screen.

### Phase 2: Core Data (Players & Seasons)
*   **Player List**:
    *   Navigate to the **Players** tab.
    *   Verify that all active players for your league are listed.
*   **Player Detail**:
    *   Tap a player in the list.
    *   Verify that their full profile (Name, Handicap Index, Contact Info) is displayed correctly.
*   **Season List**:
    *   Navigate to the **Seasons** tab.
    *   Verify the list of seasons with their respective start/end dates.

### Phase 3: Events & Registration
*   **Season Detail**:
    *   Tap a season from the list.
    *   Verify the list of **Sessions** within that season.
*   **Event List**:
    *   From the Season Detail, tap "View Events".
    *   Verify that events are grouped by their respective sessions.
*   **Event Detail**:
    *   Tap an event. Verify details like Course, Nine, Format, and Start Time.
*   **Registration**:
    *   Tap the **Register** / **Unregister** toggle button.
    *   Verify that the status updates immediately and the button label changes.

### Phase 4: Scoring & Substitution
*   **Game List**:
    *   From the Event Detail screen, tap **View Games**.
    *   Verify the list of games/matches for that event, showing the assigned players.
*   **Player Substitution**:
    *   Tap **Change Players** on a specific game.
    *   Select a player to replace and pick a new player from the full league roster.
    *   **Note**: Substitution will reset any existing scores for that game (matching web behavior).
*   **Score Entry**:
    *   Tap **Post Scores** or **Edit Scores** on a game.
    *   **Attendance**: Toggle the "Played" status for a player.
    *   **Tee Selection**: Change the tee for a player and verify it saves.
    *   **Hole-by-Hole Scores**: Enter strokes for each hole.
    *   **Submit**: Tap "Save Scores" and verify the success message. Returning to the Game List should show the game as "Recorded".

---

## 3. API Automated Testing (Symfony)
To verify the backend logic independently of the mobile UI, run the PHPUnit test suite:

```bash
# Run all API tests
php bin/phpunit tests/Controller/Api/

# Run specific feature tests
php bin/phpunit tests/Controller/Api/ApiSecurityControllerTest.php
php bin/phpunit tests/Controller/Api/ApiPlayerControllerTest.php
php bin/phpunit tests/Controller/Api/ApiSeasonControllerTest.php
php bin/phpunit tests/Controller/Api/ApiEventControllerTest.php
php bin/phpunit tests/Controller/Api/ApiGameControllerTest.php
```

---

## 4. Troubleshooting
*   **Network Error**: Ensure your phone is on the same Wi-Fi network as your computer. Verify that your firewall allows connections on the web server port (usually 80 or 8080).
*   **401 Unauthorized**: Ensure the `apiToken` in the `user` table is correctly populated after login.
*   **Data Mismatch**: Compare mobile data against the web application views at `http://localhost/leaguetracker7/public/index.php`.
