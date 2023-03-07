@user @smoke @ci
Feature: As a visitor I should be able to load the user page
Scenario: User page loads
Given I am on "/user"
Given I see the login form
Then I should see "Log in"
