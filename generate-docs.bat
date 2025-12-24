@echo off
REM Framework PHPDocumentor Documentation Generator for Windows
REM This script generates API documentation using PHPDocumentor

echo ==========================================
echo Framework Documentation Generator
echo ==========================================
echo.

REM Check if PHP is installed
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: PHP is not installed!
    echo.
    echo Please install PHP from: https://windows.php.net/download/
    echo.
    pause
    exit /b 1
)

REM Check PHP version
for /f "tokens=*" %%a in ('php -r "echo PHP_VERSION;"') do set PHP_VERSION=%%a
echo PHP Version: %PHP_VERSION%
echo.

REM Check if phpDocumentor.phar exists
if not exist "phpDocumentor.phar" (
    echo ERROR: phpDocumentor.phar not found!
    echo.
    echo Download it from: https://phpdoc.org/phpDocumentor.phar
    echo.
    pause
    exit /b 1
)

echo phpDocumentor.phar found
echo.

REM Create Documentation directory if it doesn't exist
if not exist "Documentation" (
    echo Creating Documentation directory...
    mkdir Documentation
    echo Documentation directory created
) else (
    echo Documentation directory exists
)
echo.

REM Clean old documentation
echo Cleaning old documentation...
if exist "Documentation\*" del /q "Documentation\*.*" >nul 2>nul
if exist "Documentation" for /d %%d in (Documentation\*) do @rmdir /s /q "%%d" >nul 2>nul
echo Old documentation removed
echo.

REM Generate documentation
echo ==========================================
echo Generating Documentation...
echo ==========================================
echo.
echo This may take a few minutes...
echo.

if exist "phpdoc.xml" (
    echo Using phpdoc.xml configuration file...
    php phpDocumentor.phar run --config=phpdoc.xml
) else (
    echo Using default configuration...
    php phpDocumentor.phar run --directory=Core --target=Documentation --title="Framework API Documentation" --visibility=public,protected --defaultpackagename=Framework --template=default
)

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ==========================================
    echo Documentation Generated Successfully!
    echo ==========================================
    echo.
    echo Documentation location: %CD%\Documentation
    echo.
    echo To view the documentation:
    echo   Open Documentation\index.html in your browser
    echo.
) else (
    echo.
    echo ==========================================
    echo Documentation Generation Failed!
    echo ==========================================
    echo.
    echo Please check the error messages above.
    echo.
)

pause

