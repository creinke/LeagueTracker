# Phase 2: Implement Core Data
Implement only Phase 2 of the approved implementation plan, in review-first mode.

Precondition:
Phase 1 has been completed and approved.

Goal:
Add the core data endpoints and screens for Players and Seasons.

Symfony work allowed:
- Create `src/Controller/Api/ApiPlayerController.php`
- Create `src/Controller/Api/ApiSeasonController.php`

React Native work allowed:
- Create only:
    - `src/screens/PlayerList`
    - `src/screens/PlayerDetail`
    - `src/screens/SeasonList`
    - `src/screens/SeasonDetail`
    - `src/services/PlayerService`
    - `src/services/SeasonService`
    - required TypeScript interfaces in `src/types/`
    - required navigation additions
- Implement caching only for:
    - league info
    - player names
      if clearly justified by the approved plan

Constraints:
- Do not modify existing web controllers
- Do not modify Twig templates
- Do not implement any Event endpoints or Event screens yet
- Do not implement Results yet
- Reuse existing entities/repositories where practical
- Keep all changes additive and low-risk

Before making edits:
1. List the exact files you propose to create or modify
2. Show the JSON response shapes you will implement for:
    - player list
    - player detail
    - season list
    - season detail
3. Show the exact diffs
4. Wait for my approval before writing any files

Implementation targets from the approved plan:
- `ApiPlayerController`
- `ApiSeasonController`
- PlayerList / PlayerDetail
- SeasonList / SeasonDetail
- required services, types, and navigation

After approval, implement only this phase.