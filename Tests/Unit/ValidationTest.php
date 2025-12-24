<?php

namespace Tests\Unit;

use Core\Model\Validation;
use Core\Model\Database\Database;
use Core\Model\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for Validation Class
 *
 * Tests validation rules and error handling.
 */
class ValidationTest extends TestCase
{
    private Validation $validation;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock database for validation that requires DB
        $logger = $this->createMock(Logger::class);
        $db = $this->createMock(Database::class);
        
        $this->validation = new Validation($db);
    }

    public function testRequiredRulePassesForNonEmptyValue(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'required'];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertTrue($result);
        $this->assertEmpty($this->validation->errors());
    }

    public function testRequiredRuleFailsForEmptyValue(): void
    {
        $data = ['name' => ''];
        $rules = ['name' => 'required'];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertFalse($result);
        $this->assertNotEmpty($this->validation->errors());
    }

    public function testEmailRulePassesForValidEmail(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertTrue($result);
    }

    public function testEmailRuleFailsForInvalidEmail(): void
    {
        $data = ['email' => 'invalid-email'];
        $rules = ['email' => 'email'];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertFalse($result);
    }

    public function testMinRulePassesForValidLength(): void
    {
        $data = ['password' => 'password123'];
        $rules = ['password' => 'min:6'];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertTrue($result);
    }

    public function testMinRuleFailsForShortValue(): void
    {
        $data = ['password' => '12345'];
        $rules = ['password' => 'min:6'];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertFalse($result);
    }

    public function testMaxRulePassesForValidLength(): void
    {
        $data = ['username' => 'john'];
        $rules = ['username' => 'max:10'];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertTrue($result);
    }

    public function testMaxRuleFailsForLongValue(): void
    {
        $data = ['username' => 'verylongusername'];
        $rules = ['username' => 'max:10'];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertFalse($result);
    }

    public function testNumericRulePassesForNumber(): void
    {
        $data = ['age' => '25'];
        $rules = ['age' => 'numeric'];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertTrue($result);
    }

    public function testNumericRuleFailsForNonNumber(): void
    {
        $data = ['age' => 'twenty'];
        $rules = ['age' => 'numeric'];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertFalse($result);
    }


    public function testAlphaRulePassesForLettersOnly(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'alpha'];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertTrue($result);
    }

    public function testAlphaRuleFailsForNumbersOrSpecialChars(): void
    {
        $data = ['name' => 'John123'];
        $rules = ['name' => 'alpha'];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertFalse($result);
    }

    public function testMultipleRulesCanBeApplied(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'required|email|max:50'];
        
        $result = $this->validation->make($data, $rules);
        
        $this->assertTrue($result);
    }

    public function testErrorsReturnsValidationErrors(): void
    {
        $data = ['email' => 'invalid'];
        $rules = ['email' => 'email'];
        
        $this->validation->make($data, $rules);
        $errors = $this->validation->errors();
        
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testFirstErrorReturnsFirstErrorMessage(): void
    {
        $data = ['email' => ''];
        $rules = ['email' => 'required|email'];
        
        $this->validation->make($data, $rules);
        $firstError = $this->validation->firstError('email');
        
        $this->assertIsString($firstError);
        $this->assertNotEmpty($firstError);
    }


    public function testFailsReturnsTrueWhenHasErrors(): void
    {
        $data = ['name' => ''];
        $rules = ['name' => 'required'];
        
        $this->validation->make($data, $rules);
        
        $this->assertTrue($this->validation->fails());
    }
}

