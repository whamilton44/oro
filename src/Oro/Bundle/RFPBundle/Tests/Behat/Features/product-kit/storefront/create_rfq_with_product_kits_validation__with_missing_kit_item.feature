@regression
@feature-BB-22730
@fixture-OroRFPBundle:product-kit/storefront/rfq_with_product_kits__product.yml

Feature: Create RFQ with Product Kits Validation - with missing Kit Item

  Scenario: Feature background
    Given sessions active:
      | Buyer | first_session  |
      | Admin | second_session |

  Scenario: Create RFQ from scratch
    Given I continue as the Buyer
    When I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    And I click "Requests For Quote"
    And I click "New Quote"
    And fill form with:
      | PO Number | PO013 |
    And fill "Frontend Request Form" with:
      | Line Item Product | product-kit-01 - Product Kit 01 |
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Product Kit 01                       |
      | Kit Item 1 Name      | Optional Item                        |
      | Kit Item 2 Name      | Mandatory Item                       |
      | Kit Item 1 Product 1 | simple-product-03 Product 03 $3.7035 |
      | Kit Item 1 Product 2 | None                                 |
      | Kit Item 2 Product 1 | simple-product-01 Product 01 $1.2345 |
      | Kit Item 2 Product 2 | simple-product-02 Product 02 $2.469  |
      | Price                | Total: $124.6867       |
      | okButton             | Save                                 |

  Scenario: Add Product Kit Line Item to the RFQ
    When I click "RFQ Kit Item Line Item 1 Product 1"
    And click "Save"
    Then I should see "product-kit-01 - Product Kit 01 Optional Item 1 piece simple-product-03 - Simple Product 03 Mandatory Item 1 piece simple-product-01 - Simple Product 01" in the "RFQ Products List Line Item 1" element

  Scenario: Add a Kit Item to Product Kit
    Given I proceed as the Admin
    When I login as administrator
    And go to Products / Products
    And click "Edit" on row "product-kit-01" in grid
    And I click "Add Kit Item"
    And I fill "ProductKitForm" with:
      | Kit Item 3 Label            | Flash Drives |
      | Kit Item 3 Product Unit     | piece        |
      | Kit Item 3 Minimum Quantity | 1            |
    And I click "Add Product" in "Product Kit Item 3" element
    And I click on simple-product-01 in grid "KitItemProductsAddGrid"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Try to submit the Request
    Given I continue as the Buyer
    When click "Update Line Item"
    Then I should see "product-kit-01 - Product Kit 01 Optional Item 1 piece simple-product-03 - Simple Product 03 Mandatory Item 1 piece simple-product-01 - Simple Product 01" in the "RFQ Products List Line Item 1" element
    When I click "Submit Request"
    Then I should not see "Request has been saved" flash message
    And I should see "product-kit-01 - Product Kit 01 Optional Item 1 piece simple-product-03 - Simple Product 03 Mandatory Item 1 piece simple-product-01 - Simple Product 01 Flash Drives 1 piece Selection required The selected kit configuration is not valid. Please modify or remove it." in the "RFQ Products List Line Item 1" element

  Scenario: Update and submit RFQ
    When click on "RFQ Kit Item Line Item 1 Configure Button"
    And I click "RFQ Kit Item Line Item 3 Product 1"
    And click "Save"
    And click "Update Line Item"
    Then I should see "product-kit-01 - Product Kit 01 Optional Item 1 piece simple-product-03 - Simple Product 03 Mandatory Item 1 piece simple-product-01 - Simple Product 01 Flash Drives 1 piece simple-product-01 - Simple Product 01" in the "RFQ Products List Line Item 1" element
    When I click "Submit Request"
    Then I should see "Request has been saved" flash message

  Scenario: Check RFQ with Product Kits in the admin area
    Given I proceed as the Admin
    When I go to Sales / Requests For Quote
    Then I should see following grid:
      | Submitted By | Internal Status | PO Number |
      | Amanda Cole  | Open            | PO013     |
    When click view "PO013" in grid
    Then I should see next rows in "Request Line Items Table" table
      | SKU            | Product                                                                                                                                            | Requested Quantity |
      | product-kit-01 | Product Kit 01 Optional Item [piece x 1] Simple Product 03 Mandatory Item [piece x 1] Simple Product 01 Flash Drives [piece x 1] Simple Product 01 | 1 pc               |
