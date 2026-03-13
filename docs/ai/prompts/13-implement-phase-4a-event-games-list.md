Implement only the first approved slice of the missing scoring functionality, in review-first mode.

Goal:
Add the ability for the React Native app to display the Games associated with a selected Event.

Allowed Symfony work:
- Create only the API endpoint(s) needed to retrieve games for an event

Allowed React Native work:
- Create only the screen and service code needed to display games for a selected event
- Add only the navigation wiring strictly required to open the Game List from Event View

Constraints:
- Do not implement score posting yet
- Do not implement player substitution yet
- Do not modify Twig templates
- Do not alter existing web controller behavior
- Keep changes additive and minimal

Before making edits:
1. List the exact files you propose to create or modify
2. Show the JSON response shape for the games-by-event endpoint
3. Show the exact diffs
4. Wait for my approval before writing files