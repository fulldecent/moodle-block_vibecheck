@block @block_vibecheck
Feature: Add the study companion block
  In order to use focus tools while studying
  As a teacher
  I need to add the study companion block to a course page

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1 |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on

  Scenario: The block shows the focus and zen tools
    When I add the "Study companion" block
    Then I should see "Start Pomodoro Timer"
    And I should see "Start Breathing Exercise"
    And I should see "I need a boost!"
