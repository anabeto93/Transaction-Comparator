Feature: Transaction Reconciliation
  An authenticated user should be able to upload valid Transaction CSV files and get a report on both

  @upload
  Scenario: First CSV file not supplied
    Given I am an authenticated user
    When I fill the form field "csv_file2" with data "file2.csv"
    When I submit the comparator form
    Then I should see the validation error "The csv file1 field is required."

  @upload
  Scenario: Second CSV file not supplied
    Given I am an authenticated user
    When I fill the form field "csv_file1" with data "file1.csv"
    When I submit the comparator form
    Then I should see the validation error "The csv file2 field is required."

  @upload
  Scenario: Upload invalid File
    Given I am an authenticated user
    When I attach the file "invalid_file.csv" to "csv_file1"
    And I attach the file "invalid_file.csv" to "csv_file2"
    And I submit the comparator form
    Then I should see the validation error "The csv file1 failed to upload."
