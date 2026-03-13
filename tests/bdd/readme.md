# Behavior Driven Development (BDD) Test Structure

This directory contains the **Behavior Driven Development (BDD)** test framework used to validate the Symfony API and React Native mobile add-on.

BDD tests focus on **observable system behavior**, expressed in business-readable scenarios written in **Gherkin**.

These tests complement traditional unit tests by validating **end-to-end behavior** of the application.

---

# Directory Structure

- tests/
    - bdd/
        - features/
        - contexts/
        - fixtures/
        - support/

Each directory has a specific responsibility.

---

# features/

The `features/` directory contains **Gherkin feature files**.

These files describe **business behavior scenarios** using the Given / When / Then format.

Example:
```
Feature: Season list
    Scenario: Retrieve seasons for active league
        Given I am authenticated as a ROLE_USER
        When the client requests the season list endpoint
        Then the API response status should be 200
        And the response should contain seasons for the active league
```
Feature files should:

- describe **behavior**, not implementation
- remain **human readable**
- avoid hardcoding specific database values
- focus on **business outcomes**

Feature files act as the **executable specification** of the system.

---

# contexts/

The `contexts/` directory contains **Behat context classes**.

These classes implement the step definitions referenced in the feature files.

Each step in a feature file is mapped to a PHP method inside a context class.

Example:
```
/**
  * @Given I am authenticated as a ROLE_USER
*/
public function iAmAuthenticated() {
  // authentication logic for tests
}
```
Responsibilities of context classes:

- translate Gherkin steps into executable PHP code
- interact with the Symfony application
- call API endpoints
- validate responses

Context classes should **not contain business logic**, only test orchestration.

---

# fixtures/

The `fixtures/` directory contains **sample data helpers** used during testing.

Fixtures ensure that tests run against **predictable data**.

Examples include:

- sample users
- leagues
- seasons
- events
- players

Fixtures may:

- load predefined database records
- reset database state
- provide helper functions to retrieve known test entities

Example:

$season = Fixtures::seasonWithEvents();

Keeping fixtures centralized prevents test scenarios from depending on fragile real database data.

---

# support/

The `support/` directory contains **shared testing utilities**.

These helpers support multiple test scenarios and contexts.

Typical utilities include:

- API request helpers
- authentication helpers
- JSON response validation
- database reset helpers
- league context helpers

Example helpers:

- ApiClient.php  
- AuthHelper.php  
- JsonAssertions.php  
- LeagueContextHelper.php

These utilities keep context classes small and focused.

---

# Relationship Between BDD and PHPUnit Tests

BDD tests verify **system behavior**.

They answer questions like:

- Can a user log in?
- Does the API return seasons for the correct league?
- Are events correctly grouped by session?

PHPUnit tests verify **code logic**.

They answer questions like:

- Does event grouping logic work?
- Does scoring logic produce correct results?
- Do repository queries return the correct records?

Both types of tests are important.

BDD acts as the **outer safety net**, while PHPUnit protects the **internal implementation**.

---

# Design Principles

The BDD framework follows several principles.

## Behavior over implementation

Feature files should describe **what the system does**, not how it does it.

## Stable scenarios

Feature files should avoid referencing specific sample data values whenever possible.

Example:
```
Good:
    Given a season exists for my active league

Less ideal:
    Given season "Spring 2026" exists
```
## Deterministic data

Fixtures should ensure tests run against **predictable datasets**.

## Isolation

Tests should not rely on:

- production data
- developer-specific environments
- manually created database records

---

# Typical Testing Flow

1. Write BDD feature scenarios describing desired behavior.
2. Implement Symfony API endpoints.
3. Implement step definitions in context classes.
4. Run tests against known fixtures.
5. Extend features as new capabilities are added.

---

# Example Feature Categories

Typical feature groups include:

- features/
    - api/ 
      - auth/
      -seasons/
      - events/
      - players/
      - results/

    - mobile/
      - login/
      - seasons/
      - events/

API tests validate backend behavior. Mobile tests validate React Native application flows.

---

# Summary

This BDD structure allows us to:

- document system behavior
- validate API contracts
- support automated end-to-end testing
- maintain stable and readable test scenarios

BDD tests form the **behavioral contract** for both the Symfony backend and the React Native mobile application.