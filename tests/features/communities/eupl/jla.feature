@api @javascript @eupl
Feature:
  As the owner of the EUPL community
  in order to make it easier for users to find appropriate licences
  I need to be able to present them in a nice searchable way.

  Scenario: Present and search the licences.
    Given SPDX licences:
      | uri                       | title            | ID       |
      | http://joinup.eu/spdx/foo | SPDX licence foo | SPDX_FOO |
      | http://joinup.eu/spdx/bar | SPDX licence bar | SPDX_BAR |
    And licences:
      | uri                             | title          | description                             | type | spdx licence     | legal type                                                            |
      | http://joinup.eu/licence/foo    | Foo Licence    | Licence details for the foo licence.    |      | SPDX licence foo | Strong Community, Royalty free, Modify, Governments/EU, Use/reproduce |
      | http://joinup.eu/licence/bar    | Bar Licence    | Licence details for the bar licence.    |      | SPDX licence bar | Distribute                                                            |
      | http://joinup.eu/licence/random | Random Licence | A licence that should not be available. |      |                  | Distribute                                                            |

    When I am not logged in
    And I visit the "JLA" custom page
    Then I should see the heading "JLA"
    And I should see the following filter categories in the correct order:
      | Can        |
      | Must       |
      | Cannot     |
      | Compatible |
      | Law        |
      | Support    |
    And I should see the text "2 licences found"
    And I should see the text "Foo Licence"
    And I should see the text "Bar Licence"
    But I should not see the text "Random Licence"
    # Assert concatenated categories.
    And I should see the text "Strong Community, Governments/EU"

    And the licence item with the "SPDX_FOO" SPDX tag should include the following legal type categories:
      | Can     |
      | Must    |
      | Cannot  |
      | Support |
    And the response should contain "http://joinup.eu/spdx/foo.html#licenseText"
    And the response should contain "http://joinup.eu/spdx/bar.html#licenseText"

    When I click "Distribute" in the "Content" region
    Then I should see the text "1 licences found"
    And I should see the text "Bar Licence"
    But I should not see the text "Foo Licence"

    # Clicking again, deselects the item.
    When I click "Distribute" in the "Content" region
    Then I should see the text "2 licences found"
    And I should see the text "Foo Licence"
    And I should see the text "Bar Licence"

    When I fill in "SPDX id" with "SPDX_FOO"
    Then I should see the text "1 licences found"
    And I should see the text "Foo Licence"
    But I should not see the text "Bar Licence"

    When I clear the content of the field "SPDX id"
    Then I should see the text "2 licences found"
    And I should see the text "Foo Licence"
    And I should see the text "Bar Licence"
