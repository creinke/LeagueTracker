# Update API Tests for Scoring Flow Changes

Inspect the completed implementations from prompts 10 through 15 and update the API test suite accordingly.

Do not modify the existing web application.
Do not modify Twig templates.
Focus only on API tests and any new supporting test classes required for the new scoring-related mobile API functionality.

Scope:
- Review all Symfony API endpoints added or modified for:
    - event games list
    - game score entry detail
    - post/edit scores
    - substitute player
- Review existing API test classes and BDD/API test structure
- Add or update only the test files needed to cover the newly implemented API behavior

Goals:
1. Update existing API tests impacted by prompts 10 through 15
2. Add any new API test classes required for the new endpoints
3. Add or update fixtures, helpers, or support classes only if necessary
4. Keep changes additive and minimal
5. Reuse existing API test patterns and naming conventions where practical

Please cover the following behaviors where applicable:

## Authentication / Authorization
- authenticated ROLE_USER access succeeds where expected
- unauthenticated access is rejected where expected
- cross-league access is rejected where expected

## Event Games List
- games for a selected event can be retrieved
- only games for the selected event are returned
- the event must belong to the authenticated user's active league
- empty collections are handled correctly if applicable

## Score Entry Detail
- score-entry details for a selected game can be retrieved
- all relevant players for the game are returned
- existing scores are returned when present
- blank/empty score values are returned correctly when scores are not yet entered
- the selected game must belong to the authenticated user's active league

## Post/Edit Scores
- valid score submission succeeds
- edited scores overwrite prior values correctly
- invalid score payloads are rejected appropriately
- unauthorized or cross-league score updates are rejected
- response payloads match the implemented API contract

## Substitute Player
- substitute-player options can be retrieved if applicable
- a valid substitute-player change succeeds
- invalid substitute-player requests are rejected appropriately
- unauthorized or cross-league substitute operations are rejected
- response payloads match the implemented API contract

Deliverables:
1. Identify the existing API test files that should be updated
2. Identify the new API test files/classes that should be created
3. Identify any fixture/support/helper files that should be added or modified
4. Show the exact diffs before making changes
5. Wait for my approval before writing any files

Constraints:
- Do not generate mobile UI tests in this step
- Do not generate end-to-end React Native tests in this step
- Do not change production code unless absolutely necessary to make the tests run, and if you believe that is necessary, explain why first and wait for approval
- Prefer API-focused tests that validate JSON contracts, authorization, and behavior against known sample data

If useful, organize the new or updated tests around these areas:
- Event API tests
- Game API tests
- Score API tests
- Substitute Player API tests

Wait for my approval before making any edits.