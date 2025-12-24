<?php

namespace Tests\Feature;

use Core\Model\Validation;
use Core\Model\Database\Database;
use PHPUnit\Framework\TestCase;

/**
 * Feature Test: Complex Form Validation
 *
 * Tests complex validation scenarios with multiple rules and dependencies.
 */
class FormValidationTest extends TestCase
{
    private Validation $validation;
    private Database $mockDb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockDb = $this->createMock(Database::class);
        $this->validation = new Validation($this->mockDb);
    }

    public function testCompleteUserProfileValidation(): void
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '1234567890',
            'age' => '25',
            'bio' => 'Software developer with 5 years experience',
            'country' => 'USA'
        ];
        
        $rules = [
            'first_name' => 'required|min:2|max:50|alpha',
            'last_name' => 'required|min:2|max:50|alpha',
            'email' => 'required|email',
            'phone' => 'numeric',
            'age' => 'numeric',
            'bio' => 'required|min:10|max:500',
            'country' => 'required|min:2'
        ];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertTrue($result);
        $this->assertTrue($this->validation->passes());
        $this->assertEmpty($this->validation->errors());
    }

    public function testMultipleValidationErrors(): void
    {
        $data = [
            'name' => 'J',  // Too short
            'email' => 'invalid',  // Invalid format
            'age' => 'abc',  // Not numeric
            'password' => '123'  // Too short
        ];
        
        $rules = [
            'name' => 'required|min:2',
            'email' => 'required|email',
            'age' => 'required|numeric',
            'password' => 'required|min:8'
        ];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertFalse($result);
        $this->assertTrue($this->validation->fails());
        
        $errors = $this->validation->errors();
        $this->assertCount(4, $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('age', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    public function testPasswordConfirmationValidation(): void
    {
        $data = [
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123'
        ];
        
        $rules = [
            'password' => 'required|min:8',
            'password_confirmation' => 'required|min:8'
        ];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertTrue($result);
        
        // Check passwords match
        $this->assertEquals($data['password'], $data['password_confirmation']);
    }

    public function testEmailValidationVariants(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
            'user_123@example-domain.com'
        ];
        
        foreach ($validEmails as $email) {
            $result = $this->validation->make(
                ['email' => $email],
                ['email' => 'required|email']
            );
            $this->assertTrue($result, "Email {$email} should be valid");
        }
        
        $invalidEmails = [
            'notanemail',
            '@example.com',
            'user@',
            'user name@example.com',
            'user@.com'
        ];
        
        foreach ($invalidEmails as $email) {
            $result = $this->validation->make(
                ['email' => $email],
                ['email' => 'required|email']
            );
            $this->assertFalse($result, "Email {$email} should be invalid");
        }
    }

    public function testNumericRangeValidation(): void
    {
        // Test age in valid range
        $data = ['age' => '25'];
        $rules = ['age' => 'numeric'];
        
        $result = $this->validation->make($data, $rules);
        $this->assertTrue($result);
        $this->assertTrue($data['age'] >= 0 && $data['age'] <= 150);
    }

    public function testAlphanumericValidation(): void
    {
        $validData = [
            'username' => 'user123',
            'code' => 'ABC123XYZ'
        ];
        
        foreach ($validData as $key => $value) {
            $result = $this->validation->make(
                [$key => $value],
                [$key => 'required']
            );
            $this->assertTrue($result);
            $this->assertTrue(ctype_alnum($value));
        }
    }


    public function testMaxLengthValidation(): void
    {
        $data = [
            'short' => 'test',
            'exact' => '12345',
            'long' => '123456789'
        ];
        
        // Short should pass max:10
        $result = $this->validation->make(
            ['text' => $data['short']],
            ['text' => 'max:10']
        );
        $this->assertTrue($result);
        
        // Exact should pass max:5
        $result = $this->validation->make(
            ['text' => $data['exact']],
            ['text' => 'max:5']
        );
        $this->assertTrue($result);
        
        // Long should fail max:5
        $result = $this->validation->make(
            ['text' => $data['long']],
            ['text' => 'max:5']
        );
        $this->assertFalse($result);
    }

    public function testMinLengthValidation(): void
    {
        $data = [
            'short' => '12',
            'exact' => '12345',
            'long' => '123456789'
        ];
        
        // Short should fail min:5
        $result = $this->validation->make(
            ['text' => $data['short']],
            ['text' => 'min:5']
        );
        $this->assertFalse($result);
        
        // Exact should pass min:5
        $result = $this->validation->make(
            ['text' => $data['exact']],
            ['text' => 'min:5']
        );
        $this->assertTrue($result);
        
        // Long should pass min:5
        $result = $this->validation->make(
            ['text' => $data['long']],
            ['text' => 'min:5']
        );
        $this->assertTrue($result);
    }

    public function testComplexFormWithAllRuleTypes(): void
    {
        $formData = [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
            'age' => '30',
            'bio' => 'Software developer',
            'terms' => 'accepted'
        ];
        
        $rules = [
            'username' => 'required|min:3|max:20|alpha',
            'email' => 'required|email|max:100',
            'password' => 'required|min:8|max:50',
            'age' => 'required|numeric',
            'bio' => 'required|min:5|max:200',
            'terms' => 'required'
        ];
        
        $result = $this->validation->make($formData, $rules);
        
        $this->assertTrue($result);
        $this->assertTrue($this->validation->passes());
        $this->assertEmpty($this->validation->errors());
        
        // Verify we can get first error (even though there aren't any)
        $firstError = $this->validation->firstError('username');
        $this->assertNull($firstError);
    }
}

