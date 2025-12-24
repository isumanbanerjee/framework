#!/bin/bash

# Framework PHPDocumentor Documentation Generator
# This script generates API documentation using PHPDocumentor

echo "=========================================="
echo "Framework Documentation Generator"
echo "=========================================="
echo ""

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ ERROR: PHP is not installed!"
    echo ""
    echo "To install PHP, run one of these commands:"
    echo ""
    echo "Ubuntu/Debian:"
    echo "  sudo apt update"
    echo "  sudo apt install php-cli php-xml php-mbstring"
    echo ""
    echo "Or install a specific version:"
    echo "  sudo apt install php8.1-cli php8.1-xml php8.1-mbstring"
    echo ""
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "✓ PHP Version: $PHP_VERSION"
echo ""

# Check if phpDocumentor.phar exists
if [ ! -f "phpDocumentor.phar" ]; then
    echo "❌ ERROR: phpDocumentor.phar not found!"
    echo ""
    echo "Download it from: https://phpdoc.org/phpDocumentor.phar"
    exit 1
fi

echo "✓ phpDocumentor.phar found"
echo ""

# Create Documentation directory if it doesn't exist
if [ ! -d "Documentation" ]; then
    echo "Creating Documentation directory..."
    mkdir -p Documentation
    echo "✓ Documentation directory created"
else
    echo "✓ Documentation directory exists"
fi
echo ""

# Clean old documentation
echo "Cleaning old documentation..."
find Documentation -mindepth 1 ! -name 'README.md' ! -name '.gitkeep' -delete 2>/dev/null || true
echo "✓ Old documentation removed"
echo ""

# Generate documentation
echo "=========================================="
echo "Generating Documentation..."
echo "=========================================="
echo ""
echo "This may take a few minutes..."
echo ""

if [ -f "phpdoc.xml" ]; then
    echo "Using phpdoc.xml configuration file..."
    php phpDocumentor.phar run --config=phpdoc.xml
else
    echo "Using default configuration..."
    php phpDocumentor.phar run \
        --directory=Core \
        --target=Documentation \
        --title="Framework API Documentation" \
        --visibility=public,protected \
        --defaultpackagename=Framework \
        --template=default
fi

# Check if documentation was generated successfully
if [ $? -eq 0 ]; then
    echo ""
    echo "=========================================="
    echo "✓ Documentation Generated Successfully!"
    echo "=========================================="
    echo ""
    echo "Documentation location: $(pwd)/Documentation"
    echo ""
    echo "To view the documentation:"
    echo "  1. Open Documentation/index.html in your browser"
    echo "  2. Or run: xdg-open Documentation/index.html"
    echo ""
else
    echo ""
    echo "=========================================="
    echo "❌ Documentation Generation Failed!"
    echo "=========================================="
    echo ""
    echo "Please check the error messages above."
    exit 1
fi
