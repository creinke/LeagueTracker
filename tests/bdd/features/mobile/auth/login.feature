Feature: Mobile login
  As a league user
  I want to log in from the React Native app
  So that I can access league-specific mobile features

  Background:
    Given the mobile app is launched

  Scenario: Successful login establishes authenticated session and active league context
    Given I am on the Login screen
    When I enter valid credentials for a ROLE_USER account
    And I tap the "Login" button
    Then I should be taken to the Home screen
    And I should see the post-login menu items:
      | Home    |
      | Logout  |
      | Players |
      | Seasons |
      | Events  |
      | Help    |
    And my active league context should be loaded
    And my active league context should be cached on the device

  Scenario: Invalid login shows an error and does not authenticate the user
    Given I am on the Login screen
    When I enter invalid credentials
    And I tap the "Login" button
    Then I should remain on the Login screen
    And I should see a login error message
    And I should not have an active league context

  Scenario: Logout clears cached authenticated state
    Given I am logged in as a ROLE_USER
    And my active league context is cached on the device
    When I tap the "Logout" menu item
    Then I should be taken to the Home screen
    And I should see the pre-login menu items:
      | Home  |
      | Login |
      | Help  |
    And my authenticated session should be cleared
    And my cached active league context should be cleared