<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Database\Database;
use Core\Model\Validation;
use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    private function validation(): Validation
    {
        $db = $this->createMock(Database::class);
        return new Validation($db);
    }

    public function testRequiredFailsOnMissingOrEmpty(): void
    {
        $v = $this->validation();
        $this->assertFalse($v->make(['name' => ''], ['name' => 'required']));
        $this->assertTrue($v->fails());
        $this->assertSame('The name field is required.', $v->firstError('name'));
    }

    public function testRequiredPasses(): void
    {
        $v = $this->validation();
        $this->assertTrue($v->make(['name' => 'Alice'], ['name' => 'required']));
        $this->assertFalse($v->fails());
    }

    public function testEmailRule(): void
    {
        $v = $this->validation();
        $this->assertFalse($v->make(['email' => 'not-an-email'], ['email' => 'required|email']));
        $this->assertSame('The email must be a valid email address.', $v->firstError('email'));

        $v = $this->validation();
        $this->assertTrue($v->make(['email' => 'user@example.com'], ['email' => 'required|email']));
    }

    public function testMinAndMaxLength(): void
    {
        $v = $this->validation();
        $this->assertFalse($v->make(['password' => 'abc'], ['password' => 'required|min:8']));
        $this->assertSame('The password must be at least 8 characters.', $v->firstError('password'));

        $v = $this->validation();
        $this->assertFalse($v->make(['bio' => str_repeat('a', 300)], ['bio' => 'required|max:100']));
        $this->assertSame('The bio must not exceed 100 characters.', $v->firstError('bio'));
    }

    public function testNumericRule(): void
    {
        $v = $this->validation();
        $this->assertFalse($v->make(['age' => 'abc'], ['age' => 'required|numeric']));

        $v = $this->validation();
        $this->assertTrue($v->make(['age' => '42'], ['age' => 'required|numeric']));
    }

    public function testMatchRule(): void
    {
        $v = $this->validation();
        $data = ['password' => 'secret123', 'password_confirm' => 'different'];
        $this->assertFalse($v->make($data, ['password_confirm' => 'required|match:password']));
        $this->assertSame(
            'The password_confirm field does not match the password field.',
            $v->firstError('password_confirm')
        );

        $v = $this->validation();
        $data = ['password' => 'secret123', 'password_confirm' => 'secret123'];
        $this->assertTrue($v->make($data, ['password_confirm' => 'required|match:password']));
    }

    public function testAlphaAndAlphanumericRules(): void
    {
        $v = $this->validation();
        $this->assertFalse($v->make(['name' => 'John123'], ['name' => 'required|alpha']));

        $v = $this->validation();
        $this->assertTrue($v->make(['name' => 'John Doe'], ['name' => 'required|alpha']));

        $v = $this->validation();
        $this->assertFalse($v->make(['code' => 'abc-123'], ['code' => 'required|alphanumeric']));

        $v = $this->validation();
        $this->assertTrue($v->make(['code' => 'abc123'], ['code' => 'required|alphanumeric']));
    }

    public function testOptionalFieldSkipsValidationWhenEmpty(): void
    {
        $v = $this->validation();
        $this->assertTrue($v->make([], ['nickname' => 'alpha|min:2']));
    }

    public function testStopsAtFirstFailingRulePerField(): void
    {
        $v = $this->validation();
        $v->make(['age' => 'abc'], ['age' => 'numeric|min:2']);
        $this->assertCount(1, $v->errors()['age']);
    }

    public function testUniqueRuleQueriesDatabase(): void
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())
            ->method('fetchOneNamed')
            ->with(
                $this->stringContains('SELECT COUNT(*) as count FROM users WHERE email = :value'),
                [':value' => 'taken@example.com']
            )
            ->willReturn(['count' => 1]);

        $validation = new Validation($db);
        $this->assertFalse($validation->make(
            ['email' => 'taken@example.com'],
            ['email' => 'unique:users,email']
        ));
        $this->assertSame('The email has already been taken.', $validation->firstError('email'));
    }

    public function testUniqueRulePassesWhenNoMatch(): void
    {
        $db = $this->createMock(Database::class);
        $db->method('fetchOneNamed')->willReturn(['count' => 0]);

        $validation = new Validation($db);
        $this->assertTrue($validation->make(
            ['email' => 'fresh@example.com'],
            ['email' => 'unique:users,email']
        ));
    }

    public function testStrongPasswordRejectsWeakPasswords(): void
    {
        // Missing uppercase
        $v = $this->validation();
        $this->assertFalse($v->make(['password' => 'lowercase1!'], ['password' => 'required|strongPassword']));

        // Missing lowercase
        $v = $this->validation();
        $this->assertFalse($v->make(['password' => 'UPPERCASE1!'], ['password' => 'required|strongPassword']));

        // Missing digit
        $v = $this->validation();
        $this->assertFalse($v->make(['password' => 'NoDigits!!'], ['password' => 'required|strongPassword']));

        // Missing special character
        $v = $this->validation();
        $this->assertFalse($v->make(['password' => 'NoSpecial1'], ['password' => 'required|strongPassword']));

        // Too short (7 chars)
        $v = $this->validation();
        $this->assertFalse($v->make(['password' => 'Ab1!xyz'], ['password' => 'required|strongPassword']));
    }

    public function testStrongPasswordAcceptsCompliantPassword(): void
    {
        $v = $this->validation();
        $this->assertTrue($v->make(
            ['password' => 'SecurePass123!'],
            ['password' => 'required|strongPassword']
        ));
    }

    public function testStrongPasswordErrorMessage(): void
    {
        $v = $this->validation();
        $v->make(['password' => 'weak'], ['password' => 'required|strongPassword']);
        $this->assertStringContainsString('at least 8 characters', $v->firstError('password'));
    }

    public function testUnknownRuleThrowsInCliContext(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Validation rule 'bogus' does not exist");

        $this->validation()->make(['field' => 'value'], ['field' => 'bogus']);
    }
}
