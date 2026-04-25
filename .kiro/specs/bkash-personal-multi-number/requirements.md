# Requirements Document

## Introduction

This feature extends the bkash-personal payment gateway in the Paybill system to support multiple personal bKash numbers per gateway configuration. Currently, the gateway is limited to a single `mobile_number` stored in the `gateways_parameter` table. Admins need the ability to configure a pool of bKash numbers so that payment load can be distributed across them. When a customer initiates a payment, the system selects one number from the pool (using round-robin or random strategy) and presents it to the customer in the payment instructions.

## Glossary

- **Admin**: An authenticated user with permission to manage gateway settings in the Paybill admin panel.
- **BkashPersonalGateway**: The PHP gateway class located at `paybill/pp-content/pp-modules/pp-gateways/bkash-personal/class.php` that handles payment instructions for the bkash-personal gateway.
- **Gateway_Parameter_Store**: The `gateways_parameter` database table that stores key-value configuration options per gateway and brand, with columns: `gateway_id`, `brand_id`, `option_name`, `value`.
- **Mobile_Number_Pool**: The ordered list of bKash mobile numbers configured for a specific bkash-personal gateway instance.
- **Number_Selector**: The component responsible for choosing one mobile number from the Mobile_Number_Pool when a payment is initiated.
- **Round_Robin_Counter**: A persistent counter stored in the Gateway_Parameter_Store (option_name = `rr_counter`) used to track the next index for round-robin selection.
- **Payment_Instructions**: The structured array returned by `BkashPersonalGateway::instructions()` that is displayed to the customer during checkout.
- **Edit_Page**: The admin gateway configuration page at `paybill/pp-content/pp-admin/pp-root/gateways/edit.php`.

---

## Requirements

### Requirement 1: Store Multiple Mobile Numbers

**User Story:** As an Admin, I want to save multiple bKash mobile numbers for the bkash-personal gateway, so that payments can be distributed across several numbers.

#### Acceptance Criteria

1. THE Gateway_Parameter_Store SHALL store the Mobile_Number_Pool as a JSON-encoded array under `option_name = 'mobile_number'` with `multiple = 1` for the bkash-personal gateway.
2. WHEN the Admin saves the gateway configuration with multiple mobile numbers, THE Gateway_Parameter_Store SHALL persist all provided numbers without data loss.
3. THE Gateway_Parameter_Store SHALL support a minimum of 1 and a maximum of 20 mobile numbers per gateway instance.
4. IF the Admin submits a mobile number entry that is empty or contains only whitespace, THEN THE Edit_Page SHALL discard that entry before saving.
5. WHEN the gateway configuration is loaded, THE BkashPersonalGateway SHALL read `options['mobile_number']` as an array regardless of whether one or multiple numbers are stored.

---

### Requirement 2: Admin UI for Managing Multiple Numbers

**User Story:** As an Admin, I want a dedicated UI section on the gateway edit page to add, reorder, and remove individual bKash numbers, so that I can manage the pool without editing raw data.

#### Acceptance Criteria

1. WHEN the Admin opens the bkash-personal gateway edit page, THE Edit_Page SHALL display a list of all currently configured mobile numbers, each in its own input row.
2. THE Edit_Page SHALL provide an "Add Number" control that appends a new empty input row to the list when activated.
3. WHEN the Admin activates the remove control on a number row, THE Edit_Page SHALL remove that row from the list immediately without a page reload.
4. THE Edit_Page SHALL validate that each non-empty mobile number input matches the pattern of 11-digit Bangladeshi mobile numbers (starting with 01) before form submission.
5. IF the Admin attempts to submit the form with zero valid mobile numbers, THEN THE Edit_Page SHALL prevent submission and display an error message indicating at least one number is required.
6. THE Edit_Page SHALL replace the existing single `mobile_number` text input (injected by the automation extraFields logic) with the multi-number management UI for the bkash-personal gateway specifically.

---

### Requirement 3: Number Selection on Payment Initiation

**User Story:** As a customer, I want to be shown a valid bKash number when I initiate a payment, so that I know where to send money.

#### Acceptance Criteria

1. WHEN a customer initiates a payment using the bkash-personal gateway, THE Number_Selector SHALL select one mobile number from the Mobile_Number_Pool.
2. THE Number_Selector SHALL use round-robin selection, cycling through the Mobile_Number_Pool in order based on the Round_Robin_Counter.
3. WHEN a number is selected via round-robin, THE Number_Selector SHALL increment the Round_Robin_Counter and persist the updated value to the Gateway_Parameter_Store.
4. WHEN the Round_Robin_Counter reaches the end of the Mobile_Number_Pool, THE Number_Selector SHALL reset the counter to 0 and select the first number.
5. IF the Mobile_Number_Pool contains exactly one number, THEN THE Number_Selector SHALL always return that number without modifying the Round_Robin_Counter.
6. IF the Mobile_Number_Pool is empty or missing, THEN THE BkashPersonalGateway SHALL return an empty string for the mobile number in the Payment_Instructions.

---

### Requirement 4: Display Selected Number in Payment Instructions

**User Story:** As a customer, I want to see the selected bKash number clearly in the payment instructions, so that I can send money to the correct number.

#### Acceptance Criteria

1. WHEN the BkashPersonalGateway generates Payment_Instructions, THE BkashPersonalGateway SHALL substitute `{mobile_number}` in instruction step 3 with the single number selected by the Number_Selector.
2. THE Payment_Instructions SHALL expose the selected number as the `value` field of instruction step 3 to enable the copy-to-clipboard function.
3. WHEN the selected mobile number is an empty string, THE BkashPersonalGateway SHALL still return the full Payment_Instructions array with an empty `value` for step 3.

---

### Requirement 5: Backward Compatibility with Single-Number Configurations

**User Story:** As an Admin, I want existing single-number gateway configurations to continue working after the upgrade, so that no manual migration is required.

#### Acceptance Criteria

1. WHEN the BkashPersonalGateway reads `options['mobile_number']` and the stored value is a plain string (legacy format), THE BkashPersonalGateway SHALL treat it as a Mobile_Number_Pool containing that single string.
2. WHEN the Admin saves the gateway configuration for a previously single-number gateway, THE Edit_Page SHALL convert the existing single value into the multi-number array format in the Gateway_Parameter_Store.
3. THE Number_Selector SHALL produce correct output for both legacy string values and new JSON array values without requiring a database migration script.
