# Implement Phase 1 of the Foundation
Implement only Phase 1 of the approved implementation plan, in review-first mode.

Goal:
Create the authentication and mobile foundation required for the React Native add-on.

Scope:
1. Symfony API auth foundation only
2. React Native project foundation only

Symfony work allowed:
- Create `src/Controller/Api/ApiSecurityController.php`
- Create `src/Controller/Api/ApiUserController.php`
- Modify `config/packages/security.yaml` only if strictly necessary for the mobile API auth flow

React Native work allowed:
- Create the initial `mobile/` project structure only if it does not already exist
- Create only the minimal folders/files required for:
    - `src/context/AuthContext`
    - `src/screens/Login`
    - `src/screens/Home`
    - `src/navigation/`
    - `src/api/` base configuration
    - minimal app bootstrap wiring

Constraints:
- Do not modify existing web controllers
- Do not modify Twig templates
- Do not implement Players, Seasons, Events, Results, or Help screens yet
- Do not add endpoints beyond auth/current-user endpoints
- Keep all changes additive
- Prefer the smallest possible set of files

Before making edits:
1. Explain whether you recommend session-based or token-based authentication for this React Native add-on and why
2. List the exact files you propose to create or modify
3. Show the exact diffs
4. Wait for my approval before writing any files

Implementation targets from the approved plan:
- API auth foundation
- `ApiSecurityController`
- `ApiUserController`
- React Native auth context
- Login and Home screens only

After approval, implement only this phase.