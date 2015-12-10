@mod @mod_sepl
Feature: In an seplment, students start a new attempt based on their previous one
  In order to improve my submission
  As a student
  I need to submit my seplment editing an online form, receive feedback, and then improve my submission.

  @javascript
  Scenario: Submit a text online and edit the submission
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test seplment name |
      | Description | Submit your online text |
      | seplsubmission_onlinetext_enabled | 1 |
      | seplsubmission_file_enabled | 0 |
      | Attempts reopened | Manually |
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test seplment name"
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student first submission |
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test seplment name"
    And I follow "View/grade all submissions"
    And I click on "Grade Student 1" "link" in the "Student 1" "table_row"
    And I set the following fields to these values:
      | Allow another attempt | 1 |
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test seplment name"
    And I press "Add a new attempt based on previous submission"
    And I press "Save changes"
    Then I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test seplment name"
    And I follow "View/grade all submissions"
    And I click on "Grade Student 1" "link" in the "Student 1" "table_row"
    And I should see "I'm the student first submission"
