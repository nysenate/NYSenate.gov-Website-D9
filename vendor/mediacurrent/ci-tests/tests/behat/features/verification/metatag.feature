@smoke @ci
Feature: Meta tag
  When I am on "/user"
  As an anonymous user
  I should be able to a validate title Meta tag

  Scenario: View test title meta tag
    Given I am on "/user"
    And I should see an "title" element
    Then the "title" element should contain "Log in"
