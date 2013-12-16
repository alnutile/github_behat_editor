@javascript
Feature: Github Editor Tests
  Add ability to test and add to github repo tests
  User of the behat editor system
  Users can pull in repos, run tests and add tests

  Scenario: User should be able to add repo to their User account
    Given I am on "/admin/behat/github_settings/user"
    And I follow "Add repo"
    Then I should see "Add Repo for your account"
    And I check "repo-one"
    And I press "submit"
    Then I should see "Repo One added"
    Then I see this class exists "edit-repo-one"

  Scenario: User should be able to add repo to their Group account
    Given I am on "/admin/behat/github_settings/group"
    And I follow "Add repo"
    Then I should see "Add Repo for group"
    And I check "repo-one"
    And I select "Group 1"
    And I press "submit"
    Then I should see "Repo One added to Group 1"
    Then I see this class exists "edit-repo-one"

  Scenario: User should be able to remove user repo
    Given I am on "/admin/behat/github_settings/user"
    And I check "repo-one"
    And I select "delete repo"
    And I press "submit"
    Then I should see "Repo Deleted"
    And I should not see class "repo-one"

  Scenario: User should be able to remove group repo
    Given I am on "/admin/behat/github_settings/group"
    And I check "repo-one"
    And I select "delete repo"
    And I press "submit"
    Then I should see "Repo Deleted"
    And I should not see class "repo-one"

  Scenario: User should be able to see repos on admin index
  Scenario: User should be able to add test to github
  Scenario: User should be able to edit test and commit to github
  Scenario: If github is down tests should commit after cron run