Feature: Season list
  As a logged-in league user
  I want to view seasons for my active league
  So that I can navigate to season-specific details and events

  Background:
    Given I am logged in as a ROLE_USER
    And my active league context is loaded

  Scenario: View season list for active league
    When I open the "Seasons" screen
    Then I should see a list of seasons for my active league
    And each listed season should display its basic summary information
    And I should not see seasons from a different league

  Scenario: Open a season from the season list
    Given I am on the "Seasons" screen
    And at least one season exists for my active league
    When I select a season from the list
    Then I should be taken to the Season Detail screen
    And I should see details for the selected season only

  Scenario: Open events from a listed season
    Given I am on the "Seasons" screen
    And a season with events exists for my active league
    When I tap the "Events" action for that season
    Then I should be taken to the Event List screen
    And I should see events for the selected season only

  Scenario: No seasons available for active league
    Given no seasons exist for my active league
    When I open the "Seasons" screen
    Then I should see an empty-state message for seasons
    And I should not see any season rows