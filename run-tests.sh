#!/bin/bash

# Test Runner Script for PHP Framework
# Provides convenient test execution with various options

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PHPUNIT="php resources/vendor/bin/phpunit"

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}   PHP Framework Test Suite${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# Function to run tests
run_tests() {
    local cmd="$1"
    local description="$2"

    echo -e "${YELLOW}Running: ${description}${NC}"
    echo ""

    eval $cmd
    local exit_code=$?

    echo ""
    if [ $exit_code -eq 0 ]; then
        echo -e "${GREEN}✓ Tests passed${NC}"
    else
        echo -e "${RED}✗ Tests failed${NC}"
    fi
    echo ""

    return $exit_code
}

# Parse arguments
case "${1:-all}" in
    unit)
        run_tests "$PHPUNIT Tests/Unit/" "Unit Tests"
        ;;
    integration)
        run_tests "$PHPUNIT Tests/Integration/" "Integration Tests"
        ;;
    feature)
        run_tests "$PHPUNIT Tests/Feature/" "Feature Tests"
        ;;
    coverage)
        echo -e "${YELLOW}Generating coverage report...${NC}"
        $PHPUNIT --coverage-html coverage/
        echo -e "${GREEN}Coverage report generated in coverage/${NC}"
        ;;
    app)
        run_tests "$PHPUNIT Tests/Unit/AppTest.php" "App Tests"
        ;;
    session)
        run_tests "$PHPUNIT Tests/Unit/SessionTest.php" "Session Tests"
        ;;
    request)
        run_tests "$PHPUNIT Tests/Unit/RequestTest.php" "Request Tests"
        ;;
    response)
        run_tests "$PHPUNIT Tests/Unit/ResponseTest.php" "Response Tests"
        ;;
    router)
        run_tests "$PHPUNIT Tests/Unit/RouterTest.php" "Router Tests"
        ;;
    validation)
        run_tests "$PHPUNIT Tests/Unit/ValidationTest.php" "Validation Tests"
        ;;
    logger)
        run_tests "$PHPUNIT Tests/Unit/LoggerTest.php" "Logger Tests"
        ;;
    watch)
        echo -e "${YELLOW}Watching for changes...${NC}"
        echo -e "${YELLOW}Press Ctrl+C to stop${NC}"
        echo ""
        while true; do
            clear
            $PHPUNIT
            sleep 2
        done
        ;;
    help)
        echo "Usage: ./run-tests.sh [option]"
        echo ""
        echo "Options:"
        echo "  all          - Run all tests (default)"
        echo "  unit         - Run unit tests only"
        echo "  integration  - Run integration tests only"
        echo "  feature      - Run feature tests only"
        echo "  coverage     - Generate code coverage report"
        echo "  app          - Run App class tests"
        echo "  session      - Run Session class tests"
        echo "  request      - Run Request class tests"
        echo "  response     - Run Response class tests"
        echo "  router       - Run Router class tests"
        echo "  validation   - Run Validation class tests"
        echo "  logger       - Run Logger class tests"
        echo "  watch        - Watch and re-run tests on change"
        echo "  help         - Show this help message"
        echo ""
        ;;
    all|*)
        run_tests "$PHPUNIT" "All Tests"
        ;;
esac

echo -e "${BLUE}================================================${NC}"

