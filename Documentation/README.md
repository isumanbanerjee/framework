# PHPDocumentor Documentation

## Overview

This directory contains the auto-generated API documentation for the Framework project using PHPDocumentor.

---

## 📋 Requirements

To generate the documentation, you need:

- **PHP 7.2 or higher** (PHP 8.1+ recommended)
- **PHP Extensions:**
  - php-xml
  - php-mbstring
  - php-intl (optional but recommended)

---

## 🚀 Quick Start

### Linux/Mac

```bash
# Make the script executable (first time only)
chmod +x generate-docs.sh

# Generate documentation
./generate-docs.sh
```

### Windows

```cmd
# Run the batch file
generate-docs.bat
```

### Manual Generation

If you prefer to run PHPDocumentor manually:

```bash
php phpDocumentor.phar run --config=phpdoc.xml
```

Or without config file:

```bash
php phpDocumentor.phar run \
    --directory=Core \
    --target=Documentation \
    --title="Framework API Documentation" \
    --visibility=public,protected \
    --defaultpackagename=Framework \
    --template=default
```

---

## 📁 Documentation Structure

After generation, you'll find:

```
Documentation/
├── index.html              # Main documentation entry point
├── classes/                # Class documentation
├── namespaces/             # Namespace documentation
├── packages/               # Package documentation
├── graphs/                 # Class diagrams (if enabled)
├── css/                    # Stylesheets
├── js/                     # JavaScript files
└── fonts/                  # Font files
```

---

## 🔧 Configuration

The documentation generation is configured via `phpdoc.xml` in the project root.

### Current Configuration

- **Source Directory:** `Core/`
- **Output Directory:** `Documentation/`
- **Title:** Framework - API Documentation
- **Visibility:** Public and Protected members
- **Template:** Default
- **Graphs:** Enabled

### Customizing Configuration

Edit `phpdoc.xml` to customize:

```xml
<phpdoc>
    <title>Your Title Here</title>
    <paths>
        <output>Documentation</output>
    </paths>
    <!-- Add more paths to document -->
    <version number="2.0.0">
        <api>
            <source dsn=".">
                <path>Core</path>
                <!-- Add more paths here -->
            </source>
        </api>
    </version>
</phpdoc>
```

---

## 📖 Viewing Documentation

### Local Viewing

1. **Open in Browser:**
   ```bash
   # Linux
   xdg-open Documentation/index.html
   
   # macOS
   open Documentation/index.html
   
   # Windows
   start Documentation/index.html
   ```

2. **Using PHP Built-in Server:**
   ```bash
   cd Documentation
   php -S localhost:8080
   ```
   Then visit: http://localhost:8080

3. **Using Python HTTP Server:**
   ```bash
   cd Documentation
   python3 -m http.server 8080
   ```
   Then visit: http://localhost:8080

### Hosting Documentation

To host documentation on a web server:

1. Upload the entire `Documentation/` directory
2. Ensure `.htaccess` or server configuration allows serving HTML files
3. Access via your domain/subdomain

---

## 🎯 What's Documented

The generated documentation includes:

### Core Components

- **Model Layer:**
  - ✅ Error (Enhanced error handling system)
  - ✅ Logger (Logging with rotation)
  - ✅ Auth (Authentication)
  - ✅ Session (Session management)
  - ✅ Request (HTTP request handling)
  - ✅ Response (HTTP response handling)
  - ✅ Router (URL routing)
  - ✅ Validation (Input validation)
  - ✅ EnvFileParser (Environment file parsing)

- **Database Layer:**
  - ✅ Database (Database connection and management)
  - ✅ QueryBuilder (SQL query builder)

### Documentation Features

- **Class Documentation:** Complete API reference for all classes
- **Method Documentation:** Parameters, return types, exceptions
- **Property Documentation:** Types and descriptions
- **Namespace Documentation:** Organized by namespace
- **Package Documentation:** Grouped by functionality
- **Inheritance Diagrams:** Class relationships (if graphs enabled)
- **Cross-References:** Links between related classes and methods

---

## 🔍 Documentation Quality

Our codebase includes:

- ✅ **100% PHPDoc Coverage** - All classes, methods, and properties documented
- ✅ **Type Hints** - Full type declarations for better IDE support
- ✅ **Parameter Documentation** - Detailed parameter descriptions
- ✅ **Return Type Documentation** - Clear return value documentation
- ✅ **Exception Documentation** - Documented thrown exceptions
- ✅ **Usage Examples** - Code examples where applicable

---

## 📊 Documentation Statistics

After generation, PHPDocumentor provides statistics about:

- Number of files processed
- Number of classes documented
- Number of methods documented
- Coverage percentage
- Warnings and errors

---

## 🛠️ Troubleshooting

### Common Issues

#### 1. PHP Not Found

**Error:** `Command 'php' not found`

**Solution:**
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php-cli php-xml php-mbstring

# CentOS/RHEL
sudo yum install php-cli php-xml php-mbstring

# macOS (using Homebrew)
brew install php
```

#### 2. Memory Limit Exceeded

**Error:** `PHP Fatal error: Allowed memory size exhausted`

**Solution:**
```bash
# Temporary increase
php -d memory_limit=512M phpDocumentor.phar run --config=phpdoc.xml

# Or edit php.ini
memory_limit = 512M
```

#### 3. Missing Extensions

**Error:** `Extension 'xml' not found`

**Solution:**
```bash
# Ubuntu/Debian
sudo apt install php-xml php-mbstring

# CentOS/RHEL
sudo yum install php-xml php-mbstring
```

#### 4. Permission Denied

**Error:** `Permission denied: Documentation/`

**Solution:**
```bash
# Fix permissions
chmod -R 755 Documentation/
# Or
sudo chown -R $USER:$USER Documentation/
```

#### 5. Old Documentation Not Cleaned

**Solution:**
```bash
# Manually clean
rm -rf Documentation/*
# Then regenerate
./generate-docs.sh
```

---

## 🔄 Regenerating Documentation

Documentation should be regenerated:

- ✅ After adding new classes or methods
- ✅ After updating PHPDoc comments
- ✅ Before releasing new versions
- ✅ When changing visibility of methods
- ✅ After refactoring code structure

### Automated Regeneration

Add to your CI/CD pipeline:

```yaml
# .github/workflows/docs.yml
name: Generate Documentation
on: [push]
jobs:
  docs:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Generate Docs
        run: ./generate-docs.sh
      - name: Deploy to GitHub Pages
        uses: peaceiris/actions-gh-pages@v3
        with:
          github_token: ${{ secrets.GITHUB_TOKEN }}
          publish_dir: ./Documentation
```

---

## 📚 Additional Resources

### PHPDocumentor Documentation
- Official Site: https://www.phpdoc.org/
- Guide: https://docs.phpdoc.org/
- GitHub: https://github.com/phpDocumentor/phpDocumentor

### PHPDoc Standards
- PSR-5: PHPDoc Standard
- PSR-19: PHPDoc Tags

### Documentation Best Practices
- Write clear, concise descriptions
- Include @param and @return tags
- Document exceptions with @throws
- Add @example tags for complex methods
- Use @see for related methods/classes
- Keep documentation up-to-date

---

## 🎨 Template Customization

PHPDocumentor supports custom templates:

### Available Templates

1. **default** - Standard PHPDocumentor template
2. **clean** - Minimal, clean design
3. **responsive** - Mobile-friendly template

### Using Custom Template

```bash
php phpDocumentor.phar run \
    --config=phpdoc.xml \
    --template=clean
```

Or edit `phpdoc.xml`:

```xml
<template name="clean"/>
```

### Creating Custom Templates

See: https://docs.phpdoc.org/3.0/guide/references/templates.html

---

## 📝 Documentation Tags Reference

Common PHPDoc tags used in this project:

| Tag | Purpose | Example |
|-----|---------|---------|
| `@param` | Parameter description | `@param string $name User name` |
| `@return` | Return value | `@return bool Success status` |
| `@throws` | Exception thrown | `@throws \Exception On error` |
| `@var` | Variable type | `@var array $items` |
| `@see` | Related item | `@see ClassName::method()` |
| `@since` | Version added | `@since 2.0.0` |
| `@deprecated` | Deprecated item | `@deprecated Use newMethod() instead` |
| `@example` | Code example | `@example $obj->method('test')` |
| `@todo` | TODO item | `@todo Add validation` |
| `@package` | Package name | `@package Core\Model` |

---

## 🚀 Next Steps

After generating documentation:

1. ✅ Review the generated docs in `Documentation/index.html`
2. ✅ Check for any warnings or errors
3. ✅ Verify all classes are documented
4. ✅ Test navigation and links
5. ✅ Deploy to your documentation server (optional)

---

## 📧 Support

For issues with:
- **Documentation generation:** Check troubleshooting section above
- **PHPDocumentor issues:** https://github.com/phpDocumentor/phpDocumentor/issues
- **Framework documentation:** contact@isumanbanerjee.com

---

## 📜 License

Documentation is generated from source code which is:

COPYRIGHT (c) [SUMAN BANERJEE] - All Rights Reserved

---

**Last Updated:** December 23, 2025  
**PHPDocumentor Version:** 3.x  
**Framework Version:** 2.0.0

