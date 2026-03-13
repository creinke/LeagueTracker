Feature: Event list
  As a logged-in league user
  I want to view events for a selected season
  So that I can review sessions, event details, and available actions

  Background:
    Given I am logged in as a ROLE_USER
    And my active league context is loaded
    And a season with sessions and events exists for my active league

  Scenario: View events for a selected season
    Given I open the Event List screen for a selected season
    Then I should see the selected season name
    And I should see sessions belonging to that season
    And I should see events grouped under their sessions

  Scenario: Open event details from the event list
    Given I am viewing the Event List screen for a selected season
    And at least one event exists in that season
    When I select an event from the list
    Then I should be taken to the Event Detail screen
    And I should see details for the selected event only

  Scenario: Event actions are shown according to event state
    Given I am viewing the Event List screen for a selected season
    When the events are displayed
    Then each event should show only the actions available for its current state

  Scenario: No events exist for a selected season
    Given I open the Event List screen for a selected season with no events
    Then I should see an empty-state message for events
    And I should not see any event rows