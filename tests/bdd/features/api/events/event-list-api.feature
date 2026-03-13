Feature: API event list by season
  As an authenticated mobile client
  I want to retrieve events for a selected season
  So that the mobile app can display sessions and events correctly

  Background:
    Given I am authenticated as a ROLE_USER through the API
    And my active league context is established

  Scenario: Retrieve events for a season in the active league
    Given a season with sessions and events exists for my active league
    When the client requests the event list endpoint for that season
    Then the API response status should be 200
    And the response should be valid JSON
    And the response should contain the selected season identity
    And the response should contain sessions for that season
    And the response should contain events grouped under their sessions

  Scenario: Event list excludes data from another league
    Given a season with sessions and events exists for another league
    When the client requests the event list endpoint for that season
    Then the API response should reject access to that season's events

  Scenario: Retrieve event detail
    Given an event exists in a season for my active league
    When the client requests the event detail endpoint for that event
    Then the API response status should be 200
    And the response should be valid JSON
    And the response should contain the selected event only
    And the returned event should belong to the authenticated user's active league

  Scenario: Event detail includes only valid actions for the current event state
    Given an event exists in a season for my active league
    When the client requests the event detail endpoint for that event
    Then the API response status should be 200
    And the response should include only the actions valid for that event state

  Scenario: Empty event list is returned when a season has no events
    Given a season exists for my active league with no events
    When the client requests the event list endpoint for that season
    Then the API response status should be 200
    And the response should be valid JSON
    And the response should contain the selected season identity
    And the response should contain an empty event collection