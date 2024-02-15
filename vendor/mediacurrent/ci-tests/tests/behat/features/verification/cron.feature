@api @smoke @ci
Feature: Drupal cron
  When I run cron
  As an administrator
  I should see that cron has run successfully

  Scenario: Run cron
    Given I am logged in as a user with the "administrator" role
    When I run cron
    And am on "admin/reports/dblog"
    Then I should see the link "Cron run completed"
