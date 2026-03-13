Feature: API season list
  As an authenticated mobile client
  I want to retrieve seasons for the active league
  So that the mobile app can display the correct season list

  Background:
    Given I am authenticated as a ROLE_USER through the API
    And my active league context is established
    And season data exists for my active league

  Scenario: Retrieve season list for active league
    When the client requests the season list endpoint
    Then the API response status should be 200
    And the response should be valid JSON
    And the response should contain a collection of seasons
    And every returned season should belong to the authenticated user's active league

  Scenario: Season list excludes seasons from other leagues
    Given season data exists for another league
    When the client requests the season list endpoint
    Then the API response status should be 200
    And the response should not contain seasons from a different league

  Scenario: Retrieve season detail
    Given a season exists for my active league
    When the client requests the season detail endpoint for that season
    Then the API response status should be 200
    And the response should be valid JSON
    And the response should contain the selected season only
    And the returned season should belong to the authenticated user's active league

  Scenario: Season detail rejects access to a season from another league
    Given a season exists for another league
    When the client requests the season detail endpoint for that season
    Then the API response should reject access to that season

  Scenario: Empty season list is returned when no seasons exist for active league
    Given no seasons exist for my active league
    When the client requests the season list endpoint
    Then the API response status should be 200
    And the response should be valid JSON
    And the response should contain an empty season collection