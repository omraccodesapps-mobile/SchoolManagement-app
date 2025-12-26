@echo off
REM ============================================================================
REM Database Setup Script for Symfony 7.4 School Management Application
REM ============================================================================
REM This script automates the complete database initialization process:
REM - Creates the var/data/ directory
REM - Removes existing SQLite database
REM - Creates a new database
REM - Runs migrations
REM - Loads fixtures
REM
REM Usage: bin\setup-db.bat
REM ============================================================================

setlocal enabledelayedexpansion

REM ============================================================================
REM Color Codes (using default Windows colors)
REM ============================================================================
REM Note: Windows Command Prompt has limited color support
REM Colors will work in Windows 10/11 with VT100 sequences enabled

cls
color 0F

echo.
echo ============================================================================
echo           Database Setup - School Management Application
echo ============================================================================
echo.

REM Get the project root directory
for /d %%F in ("%~dp0..") do set PROJECT_ROOT=%%~dpF
cd /d "%PROJECT_ROOT%"

echo [INFO] Project root: %PROJECT_ROOT%
echo [INFO] Environment: %APP_ENV:dev%
echo.

REM ============================================================================
REM Step 1: Create var/data/ directory
REM ============================================================================
echo [Step 1/5] Creating database directory
if not exist "var\data" (
    mkdir var\data
    if errorlevel 1 (
        color 0C
        echo [ERROR] Failed to create var\data directory
        exit /b 1
    )
    echo [SUCCESS] Directory var\data created
) else (
    echo [WARNING] Directory var\data already exists
)
echo.

REM ============================================================================
REM Step 2: Remove existing SQLite database
REM ============================================================================
echo [Step 2/5] Removing existing SQLite database files
set DB_PATH=var\data\school_management_dev.db
set DB_WAL=%DB_PATH%-wal
set DB_SHM=%DB_PATH%-shm

if exist "%DB_PATH%" (
    del /f /q "%DB_PATH%" "%DB_WAL%" "%DB_SHM%" 2>nul
    if errorlevel 1 (
        echo [WARNING] Could not delete existing database files
    ) else (
        echo [SUCCESS] Removed existing database: %DB_PATH%
    )
) else (
    echo [WARNING] No existing database found at %DB_PATH%
)
echo.

REM ============================================================================
REM Step 3: Create new SQLite database
REM ============================================================================
echo [Step 3/5] Creating new SQLite database
call php bin\console doctrine:database:create --if-not-exists
if errorlevel 1 (
    color 0C
    echo [ERROR] Database creation failed
    exit /b 1
)
echo [SUCCESS] Database creation completed
echo.

REM ============================================================================
REM Step 4: Run migrations
REM ============================================================================
echo [Step 4/5] Running database migrations
call php bin\console doctrine:migrations:migrate --no-interaction
if errorlevel 1 (
    color 0C
    echo [ERROR] Migration failed
    exit /b 1
)
echo [SUCCESS] Database migrations completed
echo.

REM ============================================================================
REM Step 5: Load fixtures
REM ============================================================================
echo [Step 5/5] Loading test data fixtures
call php bin\console doctrine:fixtures:load --no-interaction --append
if errorlevel 1 (
    echo [WARNING] Fixture loading had issues (this might be expected if no fixtures exist)
) else (
    echo [SUCCESS] Fixture loading completed
)
echo.

REM ============================================================================
REM Success Summary
REM ============================================================================
color 0A
echo.
echo ============================================================================
echo [SUCCESS] Database setup completed successfully!
echo ============================================================================
echo.
echo [INFO] Database location: %PROJECT_ROOT%%DB_PATH%
echo.
echo [INFO] Next steps:
echo   1. Start the Symfony server: symfony server:start
echo   2. Open browser: http://localhost:8000
echo   3. Run tests: php bin\phpunit
echo.
echo ============================================================================
echo.

color 0F
exit /b 0
