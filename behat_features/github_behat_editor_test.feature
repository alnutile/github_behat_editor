 @javascript @behat @behat_full_test
 Feature: Add Page
   
   @test_modules
   Scenario: User views a module file
     Given I am on "/admin/behat/view/behat_editor/behat_features/wikipedia.feature"
     Then I should see "Scenario: WikiPedia"

   @test_modules
   Scenario: User views a module file and runs a test
     Given I am on "/admin/behat/view/behat_editor/behat_features/wikipedia.feature"
     And I wait
     And I click "Run Test"
     And I wait
     And I wait
     Then I should see "File wikipedia.feature tested."
     Then I should see "Test successful"
     Then I should see "Results"
     Then I should see "was not found anywhere"

   @not_done
   Scenario: User views a module file and runs and looks for new Past Results
     Given I am on "/admin/behat/view/behat_editor/behat_features/wikipedia.feature"
     And I press "Run Test"
     And I wait
     And I wait

   @test_behat_tests_folder
   Scenario: User views a file in behat_tests folder
     Given I am on "/admin/behat/view/behat_tests/wikipedia.feature"
     Then I should see "Scenario: WikiPedia"

   @test_behat_tests_folder
   Scenario: User views a file in behat_tests folder and runs a test
     Given I am on "/admin/behat/view/behat_tests/wikipedia.feature"
     And I wait
     And I click "Run Test"
     And I wait
     And I wait
     Then I should see "File wikipedia.feature tested."
     Then I should see "Test successful"
     Then I should see "Results"
     Then I should see "was not found anywhere"

   @not_done
   Scenario: User views a file in behat_tests and runs and looks for new Past Results
     Given I am on "/admin/behat/view/behat_tests/wikipedia.feature"
     And I press "Run Test"
     And I wait
     And I wait

   @test_behat_github_folder
   Scenario: User views a file in behat_github folder but not in group
     Given I am on "/admin/behat/view/behat_github/groups/1000/blog/test/wikipedia.feature"
     And I wait
     And I wait
     Then the url should match "/admin/behat/index"
     Then I should see "You are not in this group"

   @test_behat_github_folder
   Scenario: User views a file in behat_github folder
     Given I am on "/admin/behat/view/behat_github/groups/3/blog/test/wikipedia.feature"
     Then I should see "Scenario: WikiPedia"

   @test_behat_github_folder
   Scenario: User views a file in behat_github folder and runs a test
     Given I am on "/admin/behat/view/behat_github/groups/3/blog/test/wikipedia.feature"
     And I wait
     And I click "Run Test"
     And I wait
     And I wait
     Then I should see "File wikipedia.feature tested."
     Then I should see "Test successful"
     Then I should see "Results"
     Then I should see "was not found anywhere"

   @not_done
   Scenario: User views a file in behat_github and runs and looks for new Past Results
     Given I am on "/admin/behat/view/behat_github/groups/3/blog/test/wikipedia.feature"
     And I press "Run Test"
     And I wait
     And I wait

   @test_behat_tests_folder_edit
   Scenario: User edits a file in behat_tests folder
     Given I am on "/admin/behat/edit/behat_tests/wikipedia.feature"
     Then I should see "Scenario: WikiPedia"


   @test_behat_tests_folder_edit
   Scenario: User edits a file in behat_tests folder and runs a test
     Given I am on "/admin/behat/edit/behat_tests/wikipedia.feature"
     And I wait
     Then I fill in "see_not_see_some_text" with "Test4"
     And I press "see_not_see"
     And I click "Run Test"
     And I wait
     And I wait
     Then I should see "Test successful"
     Then I should see "Results"
     Then I should see "was not found anywhere"

   @test_behat_tests_folder_edit @not_done
   Scenario: User edits a file in behat_tests folder and runs test then removes edit
     Given I am on "/admin/behat/edit/behat_tests/wikipedia.feature"
     And I wait
     Then I fill in "see_not_see_some_text" with "Test4"
     And I press "see_not_see"
     And I click "Run Test"
     And I wait
     And I wait
     Then I should see "Test successful"
     Then I should see "Results"
     Then I should see "was not found anywhere"
     Then I should see "File wikipedia.feature tested"
     And I follow "wikipedia.feature"
     And I wait
     Then I should see "Test4"
     Then I switch back to original window
     And I wait
     And I should see "Test successful!"
     And I click the element located at "li.then_i_should_see_group > i:nth-child(2)"
     And I click "Run Test"
     And I wait
     And I wait
     Then I follow "wikipedia.feature"
     And I wait
     Then I should not see "Test4"

   @test_behat_github_edit
   Scenario: Make sure the user is redirected from groups to users github repo
     Given I am on "/admin/behat/edit/behat_github/groups/3/blog/test/wikipedia.feature"
     And I wait
     And I wait
     Then the url should match "/admin/behat/edit/behat_github/users/1/blog/test/wikipedia.feature"
     Then I should see "Editing: wikipedia.feature"

   @test_behat_github_edit
   Scenario: Make sure the user is redirected to admin/index if they or their group are not in the repo list
     Given I am on "/admin/behat/edit/behat_github/groups/3/test_fake_repo_name/test/wikipedia.feature"
     And I wait
     And I wait
     Then the url should match "/admin/behat/index"
     Then I should not see "Editing: wikipedia.feature"

   @test_behat_github_edit
   Scenario: User Edits a behat_github_repo and runs a test
     Given I am on "/admin/behat/edit/behat_github/users/1/blog/test/wikipedia.feature"
     And I wait
     Then I fill in "see_not_see_some_text" with "Test4"
     And I press "see_not_see"
     And I click "Run Test"
     And I wait
     And I wait
     Then I should see "Test successful"
     Then I should see "Results"
     Then I should see "was not found anywhere"
     Then I should see "File wikipedia.feature tested"
     And I follow "wikipedia.feature"
     And I wait
     Then I should see "Test4"
     Then I switch back to original window
     And I wait
     And I should see "Test successful!"
     And I click the element located at "li.then_i_should_see_group > i:nth-child(2)"
     And I click "Run Test"
     And I wait
     And I wait
     Then I follow "wikipedia.feature"
     And I wait
     Then I should not see "Test4"

   @not_done
   Scenario: User views a file in behat_github and runs and looks for new Past Results
     Given I am on "/admin/behat/view/behat_github/groups/3/blog/test/wikipedia.feature"
     And I press "Run Test"
     And I wait
     And I wait

   @not_done
   Scenario: User Deletes the new test then makes it to start this suite

   @test_behat_tests_save
   Scenario: User Saves a Behat Edit File
     Given I am on "/admin/behat/edit/behat_tests/wikipedia.feature"
     And I wait
     Then I fill in "see_not_see_some_text" with "Test4"
     And I press "see_not_see"
     And I click "Save Test"
     And I wait
     And I wait
     Then I should see "File created click here to download"
     And I follow "click here"
     And I wait
     Then I should see "Test4"
     Then I switch back to original window
     And I wait
     And I should see "File created click here to download"
     And I click the element located at "li.then_i_should_see_group > i:nth-child(2)"
     And I click "Run Test"
     And I wait
     And I wait
     Then I follow "click here"
     And I wait
     Then I should not see "Test4"

   @test_behat_github_save
   Scenario: User Saves a Behat Edit File
     Given I am on "/admin/behat/edit/behat_github/users/1/blog/test/wikipedia.feature"
     And I wait
     Then I fill in "see_not_see_some_text" with "Test4"
     And I press "see_not_see"
     And I click "Save Test"
     And I wait
     And I wait
     Then I should see "File created click here to download"
     And I follow "click here"
     And I wait
     Then I should see "Test4"
     Then I switch back to original window
     And I wait
     And I should see "File created click here to download"
     And I click the element located at "li.then_i_should_see_group > i:nth-child(2)"
     And I click "Run Test"
     And I wait
     And I wait
     Then I follow "click here"
     And I wait
     Then I should not see "Test4"

     @not_done
     Scenario: User goes to path where repo does not exists
