@api
Feature: As a site moderator I am able to import RDF files.

  Scenario: Test the import RDF files
    Given I am logged in as a moderator
    And I go to the pipeline orchestrator

    When I select "Spain - Center for Technology Transfer" from "Data pipeline"
    And I press "Execute"
    Then I should see "Spain - Center for Technology Transfer: Manual upload"

    # Test the wizard reset.
    And I reset the spain pipeline
    Then I go to the pipeline orchestrator
    When I select "Spain - Center for Technology Transfer" from "Data pipeline"
    And I press "Execute"

    When I attach the file "invalid_adms.rdf" to "File"
    And I press "Upload"
    And I press "Next"

    Then I should see the following error messages:
      | Spain - Center for Technology Transfer execution stopped with errors in ADMS Validation step. Please review the following errors: |
    And I should see the heading "Errors executing Spain - Center for Technology Transfer"
    And I should see "Imported data is not ADMS v2 compliant"

    Given I go to the pipeline orchestrator
    When I select "Spain - Center for Technology Transfer" from "Data pipeline"
    And I press "Execute"

    When I attach the file "valid_adms.rdf" to "File"
    And I press "Upload"
    And I press "Next"

    Then I should see the following success messages:
      | The Spain - Center for Technology Transfer execution has finished with success. |
    And I should see the heading "Successfully executed Spain - Center for Technology Transfer import pipeline"

    # We manually delete the imported entities as they are not tracked by Behat
    # and, as a consequence, will not be automatically deleted after test. Also
    # this is a good test that the entities were imported and exist.
    But I delete the "Asset" solution
    And I delete the "Contact" contact information
    And I delete the "The Publisher" owner
