# Error Management Quick Reference

## Error Codes by Category

### 🔐 Session Errors
| Code | Message | When It Occurs |
|------|---------|----------------|
| `SESSION_INITIALIZATION_FAILED` | Session initialization failed | Session cannot start or configure |
| `SESSION_CONFIGURATION_FAILED` | Session configuration failed | ini_set() fails for session settings |
| `SESSION_SET_FAILED` | Failed to set session value | Session not active when setting value |
| `SESSION_REGENERATE_FAILED` | Failed to regenerate session | session_regenerate_id() fails |
| `SESSION_DESTROY_FAILED` | Failed to destroy session | session_destroy() fails |
| `CSRF_TOKEN_GENERATION_FAILED` | Failed to generate CSRF token | random_bytes() or token setting fails |

### 👤 Authentication Errors
| Code | Message | When It Occurs |
|------|---------|----------------|
| `AUTH_INITIALIZATION_FAILED` | Auth initialization failed | Config loading or validation fails |
| `AUTH_LOGIN_FAILED` | Login failed | Login process encounters exception |
| `AUTH_REGISTRATION_FAILED` | User registration failed | Registration or password hashing fails |
| `AUTH_LOGOUT_FAILED` | Logout failed | DB update or cookie removal fails |
| `AUTH_REMEMBER_TOKEN_FAILED` | Failed to create remember token | Token generation or storage fails |

### ✅ Validation Errors
| Code | Message | When It Occurs |
|------|---------|----------------|
| `VALIDATION_FAILED` | Validation process failed | Validation rule doesn't exist or error |
| `VALIDATION_UNIQUE_CHECK_FAILED` | Unique validation check failed | Database query for uniqueness fails |

### 📨 Request/Response Errors
| Code | Message | When It Occurs |
|------|---------|----------------|
| `REQUEST_INITIALIZATION_FAILED` | Request initialization failed | Superglobal access fails |
| `JSON_ENCODING_FAILED` | JSON encoding failed | json_encode() fails |
| `RESPONSE_JSON_FAILED` | JSON response failed | General JSON response error |

### 🛣️ Routing Errors
| Code | Message | When It Occurs |
|------|---------|----------------|
| `ROUTING_RESOLVE_FAILED` | Route resolution failed | Route matching or regex fails |
| `ROUTING_DISPATCH_FAILED` | Route dispatch failed | Route dispatching encounters error |
| `CALLBACK_EXECUTION_FAILED` | Callback execution failed | Controller not found, method missing, or not callable |

### 🗄️ Database Errors
| Code | Message | When It Occurs |
|------|---------|----------------|
| `DB_TYPE_NOT_PROVIDED` | Database type not provided | DB_TYPE missing in config |
| `DB_HOST_NOT_PROVIDED` | Database host not provided | DB_HOST missing in config |
| `DB_NAME_NOT_PROVIDED` | Database name not provided | DB_NAME missing in config |
| `DB_PORT_NOT_PROVIDED` | Database port not provided | DB_PORT missing in config |
| `DB_USERNAME_NOT_PROVIDED` | Database username not provided | DB_USERNAME missing in config |
| `DB_PASSWORD_NOT_PROVIDED` | Database password not provided | DB_PASSWORD missing in config |
| `DATABASE_CONNECTION_FAILED` | Database connection failed | PDO connection fails |
| `UNSUPPORTED_DB_TYPE` | Unsupported database type | Invalid DB_TYPE in config |

---

## How to Use Error Management

### In Your Own Classes

```php
<?php

namespace MyApp;

use Core\Model\Error;
use Exception;

class MyClass
{
    public function myMethod()
    {
        try {
            // Your code here
            if (/* validation fails */) {
                throw new Exception('Validation error details');
            }
            
            // Do something
            $result = someOperation();
            
            if (!$result) {
                throw new Exception('Operation failed');
            }
            
            return $result;
            
        } catch (Exception $e) {
            $error = new Error();
            $error->terminateWithError(
                'MY_ERROR_CODE',
                'User-friendly message: ' . $e->getMessage(),
                Error::SEVERITY_ERROR,
                ['additional' => 'context']
            );
        }
    }
}
```

### Adding New Error Codes

1. **Add to `Configuration/error.env`:**
```env
MY_ERROR_CODE='My error message here.'
```

2. **Use in your code:**
```php
$error->terminateWithError('MY_ERROR_CODE', $details);
```

---

## Error Severity Levels

Use these constants from the Error class:

- `Error::SEVERITY_DEBUG` - Debug information
- `Error::SEVERITY_INFO` - Informational messages
- `Error::SEVERITY_WARNING` - Warning conditions
- `Error::SEVERITY_ERROR` - Error conditions (default)
- `Error::SEVERITY_CRITICAL` - Critical conditions
- `Error::SEVERITY_FATAL` - Fatal errors

### When to Use Each Level

| Level | Use When | Example |
|-------|----------|---------|
| DEBUG | Development debugging | Variable dumps, trace info |
| INFO | Informational logging | User logged in, config loaded |
| WARNING | Recoverable issues | Deprecated feature used |
| ERROR | Operation failures | Login failed, validation error |
| CRITICAL | System failures | Session init failed, auth init failed |
| FATAL | Unrecoverable errors | Database connection failed |

---

## Common Patterns

### Pattern 1: Input Validation
```php
try {
    if (empty($input)) {
        throw new Exception('Input is required');
    }
    
    if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Process...
} catch (Exception $e) {
    $error = new Error();
    $error->terminateWithError('INVALID_INPUT', $e->getMessage());
}
```

### Pattern 2: Operation Result Check
```php
try {
    $result = $this->db->executeQuery($query, $params);
    
    if (!$result) {
        throw new Exception('Query execution failed');
    }
    
    return $result;
} catch (Exception $e) {
    $error = new Error();
    $error->terminateWithError('DATABASE_ERROR', $e->getMessage());
}
```

### Pattern 3: External Resource Validation
```php
try {
    if (!file_exists($filePath)) {
        throw new Exception("File not found: {$filePath}");
    }
    
    $contents = file_get_contents($filePath);
    
    if ($contents === false) {
        throw new Exception('Failed to read file');
    }
    
    return $contents;
} catch (Exception $e) {
    $error = new Error();
    $error->terminateWithError('FILE_READ_ERROR', $e->getMessage());
}
```

### Pattern 4: Configuration Validation
```php
try {
    $config = App::config('MY_SETTING');
    
    if (empty($config)) {
        throw new Exception('MY_SETTING not configured');
    }
    
    // Validate format
    if (!is_numeric($config)) {
        throw new Exception('MY_SETTING must be numeric');
    }
    
    return (int)$config;
} catch (Exception $e) {
    $error = new Error();
    $error->terminateWithError('CONFIG_ERROR', $e->getMessage());
}
```

---

## Error Response Format

### Development Mode (DEBUG_MODE=true)
```json
{
    "error": true,
    "code": "AUTH_LOGIN_FAILED",
    "message": "Login failed",
    "severity": "ERROR",
    "timestamp": "2025-12-24 10:30:45",
    "details": "Invalid credentials provided",
    "context": {
        "identity": "user@example.com"
    },
    "environment": "development"
}
```

### Production Mode (DEBUG_MODE=false)
```json
{
    "error": true,
    "code": "AUTH_LOGIN_FAILED",
    "message": "Login failed",
    "severity": "ERROR",
    "timestamp": "2025-12-24 10:30:45"
}
```

---

## Checklist for New Features

When adding new features, ensure:

- [ ] All public methods have try-catch blocks
- [ ] Input validation throws exceptions on failure
- [ ] Operation results are checked before returning
- [ ] External resources (files, network) are validated
- [ ] Error codes added to `error.env`
- [ ] Appropriate severity level used
- [ ] Contextual information included (sanitized)
- [ ] User-friendly error messages defined
- [ ] Exception messages captured for logging

---

## Testing Error Handling

### Manual Testing
```php
// Test 1: Trigger validation error
$session = new Session();
$session->set('', 'value'); // Should trigger error

// Test 2: Trigger auth error  
$auth = new Auth($db, $session);
$auth->login('', ''); // Should trigger error

// Test 3: Trigger routing error
$router->get('/test', 'NonExistent@method');
$router->resolve(); // Should trigger error
```

### Unit Test Example
```php
public function testLoginWithEmptyCredentials()
{
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Identity and password are required');
    
    $auth = new Auth($db, $session);
    $auth->login('', '');
}
```

---

## Quick Commands

### Check Syntax
```bash
php -l Core/Model/Session.php
```

### View Error Codes
```bash
cat Configuration/error.env
```

### Regenerate Compiled Config
```bash
cd Configuration
php -r "$config = parse_ini_file('error.env'); 
        file_put_contents('error_compiled.php', 
        '<?php\n\nreturn ' . var_export($config, true) . ';\n');"
```

---

**Last Updated:** December 24, 2025  
**Version:** 1.0.0

