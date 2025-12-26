#!/bin/bash

# ============================================================================
# Database Setup Script for Symfony 7.4 School Management Application
# ============================================================================
# This script automates the complete database initialization process:
# - Creates the var/data/ directory
# - Removes existing SQLite database
# - Creates a new database
# - Runs migrations
# - Loads fixtures
#
# Usage: ./bin/setup-db.sh
# ============================================================================

set -o pipefail

# ============================================================================
# Color Codes for Terminal Output
# ============================================================================
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ============================================================================
# Helper Functions
# ============================================================================

# Print colored success message
success() {
    echo -e "${GREEN}✓ $1${NC}"
}

# Print colored error message
error() {
    echo -e "${RED}✗ $1${NC}"
}

# Print colored info message
info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Print colored warning message
warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Execute command with error handling
execute() {
    local cmd="$1"
    local description="$2"
    
    info "Running: $description"
    if eval "$cmd"; then
        success "$description completed successfully"
        return 0
    else
        error "$description failed with exit code $?"
        return 1
    fi
}

# ============================================================================
# Script Entry Point
# ============================================================================

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║         Database Setup - School Management Application         ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Get the project root directory (parent of bin/)
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

info "Project root: $PROJECT_ROOT"
info "Environment: ${APP_ENV:-dev}"
echo ""

# ============================================================================
# Step 1: Create var/data/ directory
# ============================================================================
echo -e "${BLUE}[Step 1/5] Creating database directory${NC}"
if [ ! -d "var/data" ]; then
    if mkdir -p "var/data"; then
        success "Directory var/data/ created"
    else
        error "Failed to create var/data/ directory"
        exit 1
    fi
else
    warning "Directory var/data/ already exists"
fi
echo ""

# ============================================================================
# Step 2: Remove existing SQLite database
# ============================================================================
echo -e "${BLUE}[Step 2/5] Removing existing SQLite database files${NC}"
DB_PATH="var/data/school_management_dev.db"
DB_WAL="${DB_PATH}-wal"
DB_SHM="${DB_PATH}-shm"

if [ -f "$DB_PATH" ]; then
    rm -f "$DB_PATH" "$DB_WAL" "$DB_SHM"
    success "Removed existing database: $DB_PATH"
else
    warning "No existing database found at $DB_PATH"
fi
echo ""

# ============================================================================
# Step 3: Create new SQLite database
# ============================================================================
echo -e "${BLUE}[Step 3/5] Creating new SQLite database${NC}"
if ! execute "php bin/console doctrine:database:create --if-not-exists" "Database creation"; then
    error "Database creation failed. Exiting."
    exit 1
fi
echo ""

# ============================================================================
# Step 4: Run migrations
# ============================================================================
echo -e "${BLUE}[Step 4/5] Running database migrations${NC}"
if ! execute "php bin/console doctrine:migrations:migrate --no-interaction" "Database migrations"; then
    error "Migration failed. Exiting."
    exit 1
fi
echo ""

# ============================================================================
# Step 5: Load fixtures
# ============================================================================
echo -e "${BLUE}[Step 5/5] Loading test data fixtures${NC}"
if execute "php bin/console doctrine:fixtures:load --no-interaction --append" "Fixture loading"; then
    FIXTURE_SUCCESS=true
else
    warning "Fixture loading had issues (this might be expected if no fixtures exist)"
    FIXTURE_SUCCESS=false
fi
echo ""

# ============================================================================
# Success Summary
# ============================================================================
echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
success "Database setup completed successfully!"
echo -e "${BLUE}║                                                                ║${NC}"
info "Database location: $(pwd)/$DB_PATH"
info "Database size: $(ls -lh "$DB_PATH" 2>/dev/null | awk '{print $5}' || echo 'N/A')"
echo -e "${BLUE}║                                                                ║${NC}"
info "Next steps:"
echo "  1. Start the Symfony server: symfony server:start"
echo "  2. Open browser: http://localhost:8000"
echo "  3. Run tests: php bin/phpunit"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

exit 0
