# Phase 3: Implement Events
Implement only Phase 3 of the approved implementation plan, in review-first mode.

Precondition:
Phases 1 and 2 have been completed and approved.

Goal:
Add the Event API and React Native event screens, including registration support.

Symfony work allowed:
- Create `src/Controller/Api/ApiEventController.php`

React Native work allowed:
- Create only:
    - `src/screens/EventList`
    - `src/screens/EventDetail`
    - `src/services/EventService`
    - required additions to `src/types/`
    - required navigation updates
- Implement the mobile registration toggle only if the backend registration endpoint is included in this phase

Constraints:
- Do not modify existing web controllers
- Do not modify Twig templates
- Do not implement Results screen yet unless it is strictly required to support EventDetail navigation
- Mirror the existing ROLE_USER event flow from the Twig templates
- Keep all changes additive and low-risk

Before making edits:
1. List the exact files you propose to create or modify
2. Show the JSON response shapes for:
    - event list by season
    - event detail
    - registration response
3. Explain how the API and mobile UI will reflect the current web flow where a season leads to its sessions and events
4. Show the exact diffs
5. Wait for my approval before writing any files

Implementation targets from the approved plan:
- `ApiEventController`
- Event list and detail endpoints
- registration POST endpoint
- EventList / EventDetail screens
- required service, types, and navigation wiring

After approval, implement only this phase.