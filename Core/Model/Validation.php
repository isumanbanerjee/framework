<?php

/**
 * Data Validation and Rule Engine
 *
 * This file contains the Validation class which provides comprehensive input
 * validation with extensible rule engine, error collection, and database-
 * aware validation (e.g., uniqueness checks).
 *
 * PHP version 8.1
 *
 * @category  Validation
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

namespace Core\Model;

use Core\Model\Database\Database;
use Exception;

/**
 * Validation Class
 *
 * Comprehensive data validator providing rule-based validation with built-in
 * rules and error collection. Supports database-aware validation like
 * uniqueness checks and provides extensible validation methods.
 *
 * Features:
 * - Rule-based validation with pipe-separated syntax
 * - Multiple built-in validation rules
 * - Database-aware validation (unique checks)
 * - Error collection per field
 * - Stop-on-first-error per field behavior
 * - Parameter support for rules (min:5, max:100)
 * - Optional field handling
 * - Extensible validation methods
 *
 * Built-in validation rules:
 * - required: Field must be present and non-empty
 * - email: Must be valid email format
 * - min:n: Minimum string length
 * - max:n: Maximum string length
 * - numeric: Must be numeric value
 * - match:field: Must match another field
 * - unique:table,column: Must be unique in database
 * - alpha: Only alphabetic characters allowed
 * - alphanumeric: Only alphanumeric characters allowed
 *
 * Rule syntax examples:
 * - 'required|email'
 * - 'required|min:8|max:100'
 * - 'required|unique:users,email'
 * - 'required|match:password'
 *
 * Example usage:
 * ```php
 * $validation = new Validation($database);
 *
 * $rules = [
 *     'email' => 'required|email|unique:users,email',
 *     'password' => 'required|min:8',
 *     'password_confirm' => 'required|match:password',
 *     'age' => 'numeric|min:1|max:3'
 * ];
 *
 * if ($validation->make($_POST, $rules)) {
 *     // Validation passed
 * } else {
 *     // Get errors
 *     $errors = $validation->errors();
 *     $emailError = $validation->firstError('email');
 * }
 * ```
 *
 * @category  Validation
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */
class Validation
{
    /**
     * Database instance for database-aware validation
     *
     * @var Database
     */
    private Database $db;

    /**
     * Collection of validation errors by field name
     *
     * Structure: ['field_name' => ['error1', 'error2']]
     *
     * @var array<string,array<int,string>>
     */
    private array $errors = [];

    /**
     * Input data being validated
     *
     * @var array<string,mixed>
     */
    private array $data = [];

    /**
     * Initialize validation engine with database support
     *
     * Constructs a new Validation instance with database access for
     * database-aware validation rules like uniqueness checks.
     *
     * @param Database $db Database instance for unique/exists validation
     *
     * @since 1.0.0
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Execute validation rules against provided data
     *
     * Validates input data against specified rules. Parses pipe-separated
     * rule strings, extracts parameters, and executes validation methods.
     * Stops on first error per field for efficiency.
     *
     * Rule syntax:
     * - Pipe-separated: 'required|email|min:8'
     * - With parameters: 'unique:users,email'
     * - Multiple params: 'unique:users,email,5'
     *
     * Validation behavior:
     * - Required rule always checked if present
     * - Optional fields skip validation if empty
     * - First failed rule stops further checks for that field
     * - All fields validated independently
     *
     * @param array<string,mixed>  $data  Input data to validate (e.g. $_POST)
     * @param array<string,string> $rules Validation rules per field
     *                                    Format: ['field' => 'rule1|rule2:param']
     *
     * @return bool True if all validations pass, false otherwise
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $valid = $validation->make($_POST, [
     *     'name' => 'required|alpha|min:2|max:50',
     *     'email' => 'required|email|unique:users,email',
     *     'age' => 'numeric'
     * ]);
     * ```
     */
    public function make(array $data, array $rules): bool
    {
        try {
            $this->data = $data;
            $this->errors = [];

            foreach ($rules as $field => $ruleString) {
                $rulesArray = explode('|', $ruleString);

                foreach ($rulesArray as $rule) {
                    $params = [];
                    // Handle rules with parameters
                    if (str_contains($rule, ':')) {
                        [$ruleName, $paramString] = explode(':', $rule);
                        $params = explode(',', $paramString);
                        $rule = $ruleName;
                    }

                    $methodName = 'validate' . ucfirst($rule);
                    $value = $this->data[$field] ?? null;

                    if (method_exists($this, $methodName)) {
                        // Skip validation if field is optional and empty
                        if ($rule !== 'required' && empty($value)) {
                            continue;
                        }

                        if (!$this->$methodName($field, $value, $params)) {
                            // Stop checking other rules for this field
                            break;
                        }
                    } else {
                        throw new Exception("Validation rule '{$rule}' does not exist");
                    }
                }
            }

            return empty($this->errors);
        } catch (Exception $e) {
            // In test environment, rethrow instead of terminating
            if (defined('TEST_ENV') || php_sapi_name() === 'cli') {
                throw $e;
            }

            $error = new Error();
            $error->terminateWithError(
                'VALIDATION_FAILED',
                'Validation process failed: ' . $e->getMessage(),
                Error::SEVERITY_ERROR
            );
        }
    }

    /**
     * Check if validation has failed
     *
     * Determines whether any validation errors occurred during the last
     * make() call. Inverse of validation success.
     *
     * @return bool True if validation failed, false if passed
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $validation->make($data, $rules);
     * if ($validation->fails()) {
     *     return $validation->errors();
     * }
     * ```
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Retrieve all validation error messages
     *
     * Returns complete error collection organized by field name. Each
     * field may have multiple error messages if validation was run
     * multiple times.
     *
     * @return array<string,array<int,string>> Errors organized by field
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $errors = $validation->errors();
     * // ['email' => ['Email is required.', 'Must be valid email.']]
     * ```
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message for specific field
     *
     * Retrieves the first error message for a given field, useful for
     * displaying one error at a time per field. Returns null if no
     * errors exist for the field.
     *
     * @param string $field Field name to retrieve error for
     *
     * @return string|null First error message or null if no errors
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * $emailError = $validation->firstError('email');
     * echo $emailError ?? 'No email errors';
     * ```
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Manually add validation error message
     *
     * Internal method for appending error messages to a field's error
     * collection. Used by validation rule methods.
     *
     * @param string $field   Field name to add error to
     * @param string $message Error message text
     *
     * @return void
     *
     * @internal
     * @since 1.0.0
     */
    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    // ---------------------------------------------------------------------
    // Validation Rules
    // ---------------------------------------------------------------------

    /**
     * Validate required field
     *
     * Ensures field is present and not empty. Checks null values, empty
     * strings (after trimming), and empty arrays.
     *
     * @param string $field  Field name being validated
     * @param mixed  $value  Field value to check
     * @param array  $params Additional rule parameters (unused)
     *
     * @return bool True if field has value, false if empty
     *
     * @since 1.0.0
     */
    private function validateRequired(string $field, mixed $value, array $params): bool
    {
        if (is_null($value)
            || (is_string($value) && trim($value) === '')
            || (is_array($value) && empty($value))
        ) {
            $this->addError($field, "The $field field is required.");
            return false;
        }
        return true;
    }

    /**
     * Validate email format
     *
     * Checks if value is a valid email address using PHP's built-in
     * email validation filter.
     *
     * @param string $field  Field name being validated
     * @param mixed  $value  Email address to validate
     * @param array  $params Additional rule parameters (unused)
     *
     * @return bool True if valid email, false otherwise
     *
     * @since 1.0.0
     */
    private function validateEmail(string $field, mixed $value, array $params): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The $field must be a valid email address.");
            return false;
        }
        return true;
    }

    /**
     * Validate minimum string length
     *
     * Ensures string value meets minimum character length requirement.
     *
     * @param string $field  Field name being validated
     * @param mixed  $value  String value to check length
     * @param array  $params [0] => minimum length required
     *
     * @return bool True if meets minimum length, false otherwise
     *
     * @since 1.0.0
     */
    private function validateMin(string $field, mixed $value, array $params): bool
    {
        $min = (int) $params[0];
        if (strlen($value) < $min) {
            $this->addError($field, "The $field must be at least $min characters.");
            return false;
        }
        return true;
    }

    /**
     * Validate maximum string length
     *
     * Ensures string value doesn't exceed maximum character length.
     *
     * @param string $field  Field name being validated
     * @param mixed  $value  String value to check length
     * @param array  $params [0] => maximum length allowed
     *
     * @return bool True if within maximum length, false otherwise
     *
     * @since 1.0.0
     */
    private function validateMax(string $field, mixed $value, array $params): bool
    {
        $max = (int) $params[0];
        if (strlen($value) > $max) {
            $this->addError($field, "The $field must not exceed $max characters.");
            return false;
        }
        return true;
    }

    /**
     * Validate numeric value
     *
     * Checks if value is numeric (integer or float).
     *
     * @param string $field  Field name being validated
     * @param mixed  $value  Value to check if numeric
     * @param array  $params Additional rule parameters (unused)
     *
     * @return bool True if numeric, false otherwise
     *
     * @since 1.0.0
     */
    private function validateNumeric(string $field, mixed $value, array $params): bool
    {
        if (!is_numeric($value)) {
            $this->addError($field, "The $field must be a number.");
            return false;
        }
        return true;
    }

    /**
     * Validate field matches another field
     *
     * Ensures value matches another field's value. Common for password
     * confirmation fields.
     *
     * @param string $field  Field name being validated
     * @param mixed  $value  Value to compare
     * @param array  $params [0] => target field name to match
     *
     * @return bool True if fields match, false otherwise
     *
     * @since 1.0.0
     */
    private function validateMatch(string $field, mixed $value, array $params): bool
    {
        $targetField = $params[0];
        if ($value !== ($this->data[$targetField] ?? null)) {
            $this->addError($field, "The $field field does not match the $targetField field.");
            return false;
        }
        return true;
    }

    /**
     * Validate database uniqueness
     *
     * Checks if value is unique in specified database table/column.
     * Supports optional ID exclusion for update operations.
     *
     * Rule syntax:
     * - unique:table,column
     * - unique:table,column,excludeId
     *
     * @param string $field  Field name being validated
     * @param mixed  $value  Value to check for uniqueness
     * @param array  $params [0] => table name
     *                       [1] => column name (optional, defaults to field)
     *                       [2] => exclude ID (optional, for updates)
     *
     * @return bool True if unique, false if duplicate exists
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * // Check email is unique in users table
     * 'email' => 'unique:users,email'
     *
     * // Check during update, exclude current user ID
     * 'email' => 'unique:users,email,5'
     * ```
     */
    private function validateUnique(string $field, mixed $value, array $params): bool
    {
        try {
            if (empty($params[0])) {
                throw new Exception('Table name required for unique validation');
            }

            $table = $params[0];
            $column = $params[1] ?? $field;
            $excludeId = $params[2] ?? null;

            $query = "SELECT COUNT(*) as count FROM $table WHERE $column = :value";
            $queryParams = [':value' => $value];

            if ($excludeId) {
                $query .= ' AND id != :id';
                $queryParams[':id'] = $excludeId;
            }

            $result = $this->db->fetchOneNamed($query, $queryParams);

            if ($result && $result['count'] > 0) {
                $this->addError($field, "The $field has already been taken.");
                return false;
            }
            return true;
        } catch (Exception $e) {
            // In test environment, rethrow instead of terminating
            if (defined('TEST_ENV') || php_sapi_name() === 'cli') {
                throw $e;
            }

            $error = new Error();
            $error->terminateWithError(
                'VALIDATION_UNIQUE_CHECK_FAILED',
                'Unique validation failed: ' . $e->getMessage(),
                Error::SEVERITY_ERROR,
                ['field' => $field, 'table' => $params[0] ?? 'unknown']
            );
        }
    }

    /**
     * Validate alphabetic characters only
     *
     * Ensures value contains only letters (spaces allowed).
     *
     * @param string $field  Field name being validated
     * @param mixed  $value  Value to check for alphabetic characters
     * @param array  $params Additional rule parameters (unused)
     *
     * @return bool True if only letters, false otherwise
     *
     * @since 1.0.0
     */
    private function validateAlpha(string $field, mixed $value, array $params): bool
    {
        if (!ctype_alpha(str_replace(' ', '', $value))) {
            $this->addError($field, "The $field may only contain letters.");
            return false;
        }
        return true;
    }

    /**
     * Validate alphanumeric characters only
     *
     * Ensures value contains only letters and numbers (spaces allowed).
     *
     * @param string $field  Field name being validated
     * @param mixed  $value  Value to check for alphanumeric characters
     * @param array  $params Additional rule parameters (unused)
     *
     * @return bool True if only letters and numbers, false otherwise
     *
     * @since 1.0.0
     */
    private function validateAlphanumeric(string $field, mixed $value, array $params): bool
    {
        if (!ctype_alnum(str_replace(' ', '', $value))) {
            $this->addError($field, "The $field may only contain letters and numbers.");
            return false;
        }
        return true;
    }

    /**
     * Validate password strength
     *
     * Ensures the value meets a baseline password policy: at least 8
     * characters and containing at least one lowercase letter, one uppercase
     * letter, one digit, and one special character.
     *
     * Rule syntax:
     * - strongPassword
     *
     * @param string $field  Field name being validated
     * @param mixed  $value  Value to check for password strength
     * @param array  $params Additional rule parameters (unused)
     *
     * @return bool True if password meets the policy, false otherwise
     *
     * @since 1.0.0
     *
     * @example
     * ```php
     * 'password' => 'required|strongPassword'
     * ```
     */
    private function validateStrongPassword(string $field, mixed $value, array $params): bool
    {
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/';

        if (!is_string($value) || !preg_match($pattern, $value)) {
            $this->addError(
                $field,
                "The $field must be at least 8 characters and include uppercase, "
                . 'lowercase, a number, and a special character.'
            );
            return false;
        }
        return true;
    }
}
