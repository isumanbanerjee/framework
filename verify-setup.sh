#!/bin/bash

# PHPDocumentor Setup Verification Script
# This script verifies that all documentation generation files are properly set up

echo "=========================================="
echo "PHPDocumentor Setup Verification"
echo "=========================================="
echo ""

ERRORS=0
WARNINGS=0

# Function to check file
check_file() {
    local file=$1
    local expected_min_size=$2
    local description=$3

    if [ ! -f "$file" ]; then
        echo "❌ MISSING: $description ($file)"
        ((ERRORS++))
        return 1
    fi

    local size=$(stat -c%s "$file" 2>/dev/null || stat -f%z "$file" 2>/dev/null)
    if [ "$size" -lt "$expected_min_size" ]; then
        echo "❌ TOO SMALL: $description ($file) - ${size} bytes (expected at least ${expected_min_size})"
        ((ERRORS++))
        return 1
    fi

    echo "✅ OK: $description ($file) - ${size} bytes"
    return 0
}

# Function to check directory
check_directory() {
    local dir=$1
    local description=$2

    if [ ! -d "$dir" ]; then
        echo "❌ MISSING: $description ($dir)"
        ((ERRORS++))
        return 1
    fi

    echo "✅ OK: $description ($dir)"
    return 0
}

# Check core files
echo "Checking configuration files..."
check_file "phpdoc.xml" 1000 "PHPDocumentor configuration"
check_file "generate-docs.sh" 2000 "Linux/Mac generation script"
check_file "generate-docs.bat" 2000 "Windows generation script"
check_file "phpDocumentor.phar" 10000000 "PHPDocumentor executable"
echo ""

# Check directories
echo "Checking directories..."
check_directory "Documentation" "Documentation output directory"
check_directory "Core" "Source code directory"
echo ""

# Check script permissions
echo "Checking permissions..."
if [ -x "generate-docs.sh" ]; then
    echo "✅ OK: generate-docs.sh is executable"
else
    echo "⚠️  WARNING: generate-docs.sh is not executable"
    echo "   Fix with: chmod +x generate-docs.sh"
    ((WARNINGS++))
fi
echo ""

# Check PHP (optional)
echo "Checking PHP installation..."
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    echo "✅ OK: PHP is installed (version $PHP_VERSION)"

    # Check PHP extensions
    echo ""
    echo "Checking PHP extensions..."
    for ext in xml mbstring json; do
        if php -m | grep -qi "^$ext$"; then
            echo "✅ OK: PHP extension '$ext' is loaded"
        else
            echo "❌ MISSING: PHP extension '$ext' is not loaded"
            echo "   Install with: sudo apt install php-$ext"
            ((ERRORS++))
        fi
    done
else
    echo "⚠️  WARNING: PHP is not installed"
    echo "   PHPDocumentor requires PHP to run"
    echo "   Install with: sudo apt install php-cli php-xml php-mbstring"
    ((WARNINGS++))
fi
echo ""

# Check XML validity
echo "Checking XML configuration validity..."
if [ -f "phpdoc.xml" ]; then
    if command -v xmllint &> /dev/null; then
        if xmllint --noout phpdoc.xml 2>/dev/null; then
            echo "✅ OK: phpdoc.xml is valid XML"
        else
            echo "❌ ERROR: phpdoc.xml has XML syntax errors"
            ((ERRORS++))
        fi
    else
        echo "⚠️  SKIP: xmllint not available (optional check)"
    fi
fi
echo ""

# Check script syntax
echo "Checking script syntax..."
if command -v bash &> /dev/null; then
    if bash -n generate-docs.sh 2>/dev/null; then
        echo "✅ OK: generate-docs.sh has valid bash syntax"
    else
        echo "❌ ERROR: generate-docs.sh has syntax errors"
        ((ERRORS++))
    fi
fi
echo ""

# Summary
echo "=========================================="
echo "Verification Summary"
echo "=========================================="
if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo "✅ ALL CHECKS PASSED!"
    echo ""
    echo "Your PHPDocumentor setup is complete and ready to use."
    echo ""
    if command -v php &> /dev/null; then
        echo "Next step: Run ./generate-docs.sh to generate documentation"
    else
        echo "Next step: Install PHP, then run ./generate-docs.sh"
        echo "  sudo apt install php-cli php-xml php-mbstring"
    fi
elif [ $ERRORS -eq 0 ]; then
    echo "⚠️  WARNINGS: $WARNINGS"
    echo ""
    echo "Setup is functional but has warnings (see above)."
    echo "You can still generate documentation."
else
    echo "❌ ERRORS: $ERRORS"
    echo "⚠️  WARNINGS: $WARNINGS"
    echo ""
    echo "Please fix the errors above before generating documentation."
    exit 1
fi
echo ""

