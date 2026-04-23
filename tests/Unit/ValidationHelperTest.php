<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ValidationHelper.
 *
 * Covers all individual validators and the aggregate validate() method.
 */
class ValidationHelperTest extends TestCase
{
    // ── required() ────────────────────────────────────────────────

    /**
     * @test
     */
    public function testRequiredReturnsErrorForEmptyString(): void
    {
        $error = \ValidationHelper::required('', 'Name');

        $this->assertNotNull($error);
        $this->assertStringContainsString('Name', $error);
    }

    /**
     * @test
     */
    public function testRequiredReturnsErrorForNull(): void
    {
        $error = \ValidationHelper::required(null, 'Email');

        $this->assertNotNull($error);
        $this->assertStringContainsString('Email', $error);
    }

    /**
     * @test
     */
    public function testRequiredReturnsErrorForWhitespaceOnly(): void
    {
        $error = \ValidationHelper::required('   ', 'Phone');

        $this->assertNotNull($error);
        $this->assertStringContainsString('Phone', $error);
    }

    /**
     * @test
     */
    public function testRequiredReturnsNullForValidValue(): void
    {
        $this->assertNull(\ValidationHelper::required('John Doe', 'Name'));
        $this->assertNull(\ValidationHelper::required('0', 'Count'));
        $this->assertNull(\ValidationHelper::required(42, 'Age'));
    }

    // ── email() ───────────────────────────────────────────────────

    /**
     * @test
     */
    public function testEmailReturnsNullForValidEmail(): void
    {
        $this->assertNull(\ValidationHelper::email('user@example.com'));
        $this->assertNull(\ValidationHelper::email('admin+tag@sub.domain.org'));
    }

    /**
     * @test
     */
    public function testEmailReturnsErrorForInvalidEmail(): void
    {
        $error = \ValidationHelper::email('not-an-email');

        $this->assertNotNull($error);
        $this->assertStringContainsString('email', strtolower($error));
    }

    /**
     * @test
     */
    public function testEmailReturnsNullForEmptyValue(): void
    {
        // Empty values are handled by required(); email() should pass them through
        $this->assertNull(\ValidationHelper::email(''));
        $this->assertNull(\ValidationHelper::email(null));
    }

    // ── phone() ───────────────────────────────────────────────────

    /**
     * @test
     */
    public function testPhoneReturnsNullForValidBdPhone(): void
    {
        // Standard 01XXXXXXXXX format (01[3-9] prefix)
        $this->assertNull(\ValidationHelper::phone('01712345678'));
        $this->assertNull(\ValidationHelper::phone('01912345678'));
        $this->assertNull(\ValidationHelper::phone('01312345678'));
    }

    /**
     * @test
     */
    public function testPhoneReturnsNullForPlusPrefixFormat(): void
    {
        $this->assertNull(\ValidationHelper::phone('+8801712345678'));
    }

    /**
     * @test
     */
    public function testPhoneReturnsNullForCountryCodeWithoutPlus(): void
    {
        $this->assertNull(\ValidationHelper::phone('8801712345678'));
    }

    /**
     * @test
     */
    public function testPhoneReturnsErrorForInvalidFormat(): void
    {
        $error = \ValidationHelper::phone('12345');
        $this->assertNotNull($error);

        $error2 = \ValidationHelper::phone('abcdefghijk');
        $this->assertNotNull($error2);

        // 01[0-2] prefix is not a valid BD mobile prefix
        $error3 = \ValidationHelper::phone('01012345678');
        $this->assertNotNull($error3);
    }

    /**
     * @test
     */
    public function testPhoneReturnsNullForEmptyValue(): void
    {
        $this->assertNull(\ValidationHelper::phone(''));
        $this->assertNull(\ValidationHelper::phone(null));
    }

    // ── numeric() ─────────────────────────────────────────────────

    /**
     * @test
     */
    public function testNumericReturnsNullForValidNumber(): void
    {
        $this->assertNull(\ValidationHelper::numeric('42', 'Amount'));
        $this->assertNull(\ValidationHelper::numeric(3.14, 'Price'));
        $this->assertNull(\ValidationHelper::numeric('0', 'Count'));
    }

    /**
     * @test
     */
    public function testNumericReturnsErrorForNonNumericValue(): void
    {
        $error = \ValidationHelper::numeric('abc', 'Amount');

        $this->assertNotNull($error);
        $this->assertStringContainsString('number', strtolower($error));
    }

    /**
     * @test
     */
    public function testNumericReturnsErrorWhenBelowMin(): void
    {
        $error = \ValidationHelper::numeric('-5', 'Amount', 0.0);

        $this->assertNotNull($error);
        $this->assertStringContainsString('0', $error);
    }

    /**
     * @test
     */
    public function testNumericReturnsErrorWhenAboveMax(): void
    {
        $error = \ValidationHelper::numeric('1001', 'Amount', null, 1000.0);

        $this->assertNotNull($error);
        $this->assertStringContainsString('1000', $error);
    }

    /**
     * @test
     */
    public function testNumericReturnsNullWhenWithinRange(): void
    {
        $this->assertNull(\ValidationHelper::numeric('500', 'Amount', 0.0, 1000.0));
        $this->assertNull(\ValidationHelper::numeric('0', 'Amount', 0.0, 1000.0));
        $this->assertNull(\ValidationHelper::numeric('1000', 'Amount', 0.0, 1000.0));
    }

    /**
     * @test
     */
    public function testNumericReturnsNullForEmptyValue(): void
    {
        $this->assertNull(\ValidationHelper::numeric('', 'Amount'));
        $this->assertNull(\ValidationHelper::numeric(null, 'Amount'));
    }

    // ── date() ────────────────────────────────────────────────────

    /**
     * @test
     */
    public function testDateReturnsNullForValidDate(): void
    {
        $this->assertNull(\ValidationHelper::date('2024-01-15'));
        $this->assertNull(\ValidationHelper::date('2000-12-31'));
    }

    /**
     * @test
     */
    public function testDateReturnsErrorForInvalidDate(): void
    {
        // Month 13 does not exist
        $error = \ValidationHelper::date('2024-13-01');
        $this->assertNotNull($error);
    }

    /**
     * @test
     */
    public function testDateReturnsErrorForWrongFormat(): void
    {
        // Default format is Y-m-d; d/m/Y should fail
        $error = \ValidationHelper::date('15/01/2024');
        $this->assertNotNull($error);
    }

    /**
     * @test
     */
    public function testDateAcceptsCustomFormat(): void
    {
        $this->assertNull(\ValidationHelper::date('15/01/2024', 'd/m/Y'));
        $this->assertNotNull(\ValidationHelper::date('2024-01-15', 'd/m/Y'));
    }

    /**
     * @test
     */
    public function testDateReturnsNullForEmptyValue(): void
    {
        $this->assertNull(\ValidationHelper::date(''));
        $this->assertNull(\ValidationHelper::date(null));
    }

    // ── inArray() ─────────────────────────────────────────────────

    /**
     * @test
     */
    public function testInArrayReturnsNullForValidValue(): void
    {
        $this->assertNull(\ValidationHelper::inArray('active', ['active', 'inactive', 'suspended'], 'Status'));
    }

    /**
     * @test
     */
    public function testInArrayReturnsErrorForInvalidValue(): void
    {
        $error = \ValidationHelper::inArray('deleted', ['active', 'inactive', 'suspended'], 'Status');

        $this->assertNotNull($error);
        $this->assertStringContainsString('Status', $error);
    }

    /**
     * @test
     */
    public function testInArrayUsesStrictComparison(): void
    {
        // Integer 1 should not match string '1'
        $error = \ValidationHelper::inArray(1, ['1', '2', '3'], 'Code');
        $this->assertNotNull($error);
    }

    /**
     * @test
     */
    public function testInArrayReturnsNullForEmptyValue(): void
    {
        $this->assertNull(\ValidationHelper::inArray('', ['a', 'b'], 'Field'));
        $this->assertNull(\ValidationHelper::inArray(null, ['a', 'b'], 'Field'));
    }

    // ── maxLength() ───────────────────────────────────────────────

    /**
     * @test
     */
    public function testMaxLengthReturnsNullWhenWithinLimit(): void
    {
        $this->assertNull(\ValidationHelper::maxLength('Hello', 10, 'Name'));
        $this->assertNull(\ValidationHelper::maxLength('Hello', 5, 'Name')); // exactly at limit
    }

    /**
     * @test
     */
    public function testMaxLengthReturnsErrorWhenExceedsLimit(): void
    {
        $error = \ValidationHelper::maxLength('Hello World', 5, 'Name');

        $this->assertNotNull($error);
        $this->assertStringContainsString('5', $error);
        $this->assertStringContainsString('Name', $error);
    }

    /**
     * @test
     */
    public function testMaxLengthHandlesMultibyteCharacters(): void
    {
        // 3 Bengali characters — each is multi-byte but mb_strlen counts them as 3
        $this->assertNull(\ValidationHelper::maxLength('বাংলা', 10, 'Text'));
        $error = \ValidationHelper::maxLength('বাংলাদেশ', 5, 'Text');
        $this->assertNotNull($error);
    }

    /**
     * @test
     */
    public function testMaxLengthReturnsNullForEmptyValue(): void
    {
        $this->assertNull(\ValidationHelper::maxLength('', 5, 'Name'));
        $this->assertNull(\ValidationHelper::maxLength(null, 5, 'Name'));
    }

    // ── validate() ────────────────────────────────────────────────

    /**
     * @test
     */
    public function testValidateReturnsEmptyArrayForValidData(): void
    {
        $data = [
            'email'  => 'user@example.com',
            'phone'  => '01712345678',
            'amount' => '500',
        ];

        $rules = [
            'email'  => ['required', 'email'],
            'phone'  => ['required', 'phone'],
            'amount' => ['required', 'numeric:0:1000'],
        ];

        $errors = \ValidationHelper::validate($data, $rules);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function testValidateReturnsFirstErrorPerField(): void
    {
        // 'email' field is empty — should trigger 'required' before 'email'
        $data  = ['email' => ''];
        $rules = ['email' => ['required', 'email']];

        $errors = \ValidationHelper::validate($data, $rules);

        $this->assertArrayHasKey('email', $errors);
        // The error should be the 'required' message, not the 'email format' message
        $this->assertStringContainsString('required', strtolower($errors['email']));
    }

    /**
     * @test
     */
    public function testValidateReturnsErrorsForMultipleInvalidFields(): void
    {
        $data = [
            'name'  => '',
            'email' => 'not-valid',
        ];

        $rules = [
            'name'  => ['required'],
            'email' => ['required', 'email'],
        ];

        $errors = \ValidationHelper::validate($data, $rules);

        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    /**
     * @test
     */
    public function testValidateHandlesInRuleWithCommaDelimitedValues(): void
    {
        $data  = ['status' => 'active'];
        $rules = ['status' => ['in:active,inactive,suspended']];

        $this->assertEmpty(\ValidationHelper::validate($data, $rules));

        $data2  = ['status' => 'deleted'];
        $errors = \ValidationHelper::validate($data2, $rules);
        $this->assertArrayHasKey('status', $errors);
    }

    /**
     * @test
     */
    public function testValidateHandlesMaxRule(): void
    {
        $data  = ['name' => str_repeat('a', 256)];
        $rules = ['name' => ['max:255']];

        $errors = \ValidationHelper::validate($data, $rules);
        $this->assertArrayHasKey('name', $errors);
    }

    /**
     * @test
     */
    public function testValidateHandlesDateRule(): void
    {
        $data  = ['dob' => '2000-06-15'];
        $rules = ['dob' => ['date']];

        $this->assertEmpty(\ValidationHelper::validate($data, $rules));

        $data2  = ['dob' => 'not-a-date'];
        $errors = \ValidationHelper::validate($data2, $rules);
        $this->assertArrayHasKey('dob', $errors);
    }

    /**
     * @test
     */
    public function testValidateMissingFieldTreatedAsNull(): void
    {
        // Field not present in $data — should be treated as null
        $data  = [];
        $rules = ['name' => ['required']];

        $errors = \ValidationHelper::validate($data, $rules);
        $this->assertArrayHasKey('name', $errors);
    }
}
