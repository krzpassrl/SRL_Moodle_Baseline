@mod @mod_sepl
Feature: Grant an extension to an offline student
  In order to allow students to have an accurate due date
  As a teacher
  I need to grant students extensions at any time

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |

  @javascript
  Scenario: Granting an extension to an offline seplment
    Given the following "activities" exist:
      | activity | course | idnumber | name                 | intro                       | seplsubmission_onlinetext_enabled | seplsubmission_file_enabled | duedate    |
      | sepl   | C1     | sepl1  | Test seplment name | Test seplment description | 0                                   | 0                             | 1388534400 |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test seplment name"
    When I follow "View/grade all submissions"
    And I click on "Edit" "link" in the "Student 1" "table_row"
    And I follow "Grant extension"
    And I set the field "Enable" to "1"
    And I press "Save changes"
    Then I should see "Extension granted until:" in the "Student 1" "table_row"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test seplment name"
    And I should see "Extension due date"

  @javascript @_alert
  Scenario: Granting extensions to an offline seplment (batch action)
    Given the following "activities" exist:
      | activity | course | idnumber | name                 | intro                       | seplsubmission_onlinetext_enabled | seplsubmission_file_enabled | duedate    |
      | sepl   | C1     | sepl1  | Test seplment name | Test seplment description | 0                                   | 0                             | 1388534400 |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test seplment name"
    When I follow "View/grade all submissions"
    And I set the field "selectall" to "1"
    And I set the field "operation" to "Grant extension"
    And I click on "Go" "button" confirming the dialogue
    And I set the field "Enable" to "1"
    And I press "Save changes"
    Then I should see "Extension granted until:" in the "Student 1" "table_row"
    And I should see "Extension granted until:" in the "Student 2" "table_row"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test seplment name"
    And I should see "Extension due date"
