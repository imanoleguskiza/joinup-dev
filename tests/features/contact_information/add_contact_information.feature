@api
Feature: Creation of contact information through UI
  In order to manage contact information
  As a user
  I need to be able to create contact information through the UI.

  Scenario: Create a contact information
    Given I am logged in as an "authenticated user"
    When I visit "rdf_entity/add/contact_information"
    Then I should see the heading "Add Contact information"
    When I fill in the following:
      | E-mail      | foo@bar.com                 |
      | Name        | Contact information example |
      | Website URL | http://www.example.org      |
    And I press "Save"
    Then I should see the heading "Contact information example"
    # Cleanup created contact information.
    Then I delete the "Contact information example" contact information
