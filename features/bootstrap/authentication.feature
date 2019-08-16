Feature: Authentication
  User can register on the application as a guest user and then be logged in to see the transactions comparator aspect

  @authentication
  Scenario: Registering with Success
    Given I visit the path "/register"
    And I fill in the form with my name "Richard Opoku" and email "richard@tutuka.com" password "5ecureP@$$" and submit the form
    Then I should see the text "Tutuka Trial Project"

  @authentication
  Scenario: Logging in with Success
    Given a user called "Richard Opoku" with email "richard@tutuka.com" and password "Pass1234$" exists
    And I am logged out
    And I visit the path "/login"
    And I fill in the form with the email "richard@tutuka.com" and password "Pass1234$" and submit the form
    Then I should see the text "Tutuka Trial Project"

  Scenario: User with wrong credentials fails
    Given a user called "Richard Opoku" with email "richard@tutuka.com" and password "Pass1234$" exists
    And I am logged out
    And I visit the path "/login"
    And I fill in the form with the email "richard@tutuka.com" and password "password" and submit the form
    Then I should see the text "These credentials do not match our records."

  Scenario: Non-Existent User cannot login
    Given I am logged out
    And I visit the path "/login"
    And I fill in the form with the email "noone@tutuka.com" and password "password" and submit the form
    Then I should see the text "These credentials do not match our records."