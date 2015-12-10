@mod @mod_sepl
Feature: In an seplment, students can comment in their submissions
  In order to refine seplment submissions
  As a student
  I need to add comments about submissions

  Background:
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

  @javascript
  Scenario: Student comments an seplment submission
    Given the following "activities" exist:
      | activity | course | idnumber | name                 | intro                       | seplsubmission_onlinetext_enabled |
      | sepl   | C1     | sepl1  | Test seplment name | Test seplment description | 1 |
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test seplment name"
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student submission |
    And I press "Save changes"
    And I click on ".comment-link" "css_element"
    And I set the field "content" to "First student comment"
    And I follow "Save comment"
    Then I should see "First student comment"
    And the field "content" matches value "Add a comment..."
    And I follow "Delete this comment"
    # Wait for the animation to finish.
    And I wait "2" seconds
    And I set the field "content" to "Second student comment"
    And I follow "Save comment"
    And I should see "Second student comment"
    And I should not see "First student comment"
    And I follow "Test seplment name"
    And I click on ".comment-link" "css_element"
    And I should see "Second student comment"
    And I should not see "First student comment"

  @javascript
  Scenario: Teacher can comment on an offline seplment
    Given the following "activities" exist:
      | activity | course | idnumber | name                 | intro                       | seplsubmission_onlinetext_enabled | seplmentsubmission_file_enabled | seplfeedback_comments_enabled |
      | sepl   | C1     | sepl1  | Test seplment name | Test seplment description | 0 | 0 | 1 |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test seplment name"
    And I follow "View/grade all submissions"
    And I click on "Grade Student 1" "link" in the "Student 1" "table_row"
    When I set the following fields to these values:
      | Grade out of 100 | 50 |
      | Feedback comments | I'm the teacher feedback |
    And I press "Save changes"
    And I press "Continue"
    Then I should see "50.00" in the "Student 1" "table_row"
    And I should see "I'm the teacher feedback" in the "Student 1" "table_row"

  Scenario: Teacher can comment on seplments with a zero grade
    Given the following "activities" exist:
      | activity | course | idnumber | name                 | intro                       | seplsubmission_onlinetext_enabled | seplmentsubmission_file_enabled | seplfeedback_comments_enabled |
      | sepl   | C1     | sepl1  | Test seplment name | Test seplment description | 0 | 0 | 1 |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test seplment name"
    And I follow "View/grade all submissions"
    And I click on "Grade Student 1" "link" in the "Student 1" "table_row"
    And I set the following fields to these values:
      | Grade out of 100 | 0 |
    And I press "Save changes"
    And I should see "The grade changes were saved"
    And I press "Continue"
    When I click on "Grade Student 1" "link" in the "Student 1" "table_row"
    And I set the following fields to these values:
      | Feedback comments | I'm the teacher feedback |
    And I press "Save changes"
    Then I should see "The grade changes were saved"
