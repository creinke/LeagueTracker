Feature: API login
  As a mobile client
  I want to authenticate through the Symfony API
  So that I can access protected league data

  Scenario: Successful login returns authenticated user context
    Given a valid ROLE_USER account exists
    When the client submits valid login credentials to the authentication endpoint
    Then the API response status should be 200
    And the response should indicate the user is authenticated
    And the response should include the authenticated user identity
    And the response should include the active league context
    And the response should be valid JSON

  Scenario: Invalid login is rejected
    Given a valid ROLE_USER account exists
    When the client submits invalid login credentials to the authentication endpoint
    Then the API response status should indicate authentication failure
    And the response should indicate the user is not authenticated
    And the response should not include an active league context
    And the response should be valid JSON

  Scenario: Current authenticated user context can be retrieved
    Given I am authenticated as a ROLE_USER through the API
    When the client requests the current user endpoint
    Then the API response status should be 200
    And the response should include the authenticated user identity
    And the response should include the active league context
    And the response should be valid JSON

  Scenario: Current user endpoint rejects unauthenticated access
    Given I am not authenticated through the API
    When the client requests the current user endpoint
    Then the API response status should indicate unauthorized access
    And the response should be valid JSON