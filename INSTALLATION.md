# Installation Guide - School Management Application

Complete setup instructions for the Symfony 7.4 School Management Application.

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Quick Start](#quick-start)
3. [Detailed Installation](#detailed-installation)
4. [Docker Setup](#docker-setup)
5. [Testing](#testing)
6. [Default Credentials](#default-credentials)
7. [Troubleshooting](#troubleshooting)

---

## 📦 Prerequisites

Before you begin, ensure you have the following installed on your system:

### Required Software

| Software | Version | Purpose |
|----------|---------|---------|
| **PHP** | 8.2+ | Application runtime |
| **Composer** | 2.0+ | Dependency manager |
| **Node.js** | 18+ | JavaScript runtime |
| **npm** | 9.0+ | Package manager |
| **Git** | Any | Version control |

### PHP Extensions

Your PHP installation must include these extensions:

```bash
# Check installed extensions
php -m | grep -E "sqlite3|intl|zip|pdo"
```

**Required Extensions:**
- ✅ `pdo_sqlite` - SQLite database support
- ✅ `intl` - Internationalization
- ✅ `zip` - Archive support
- ✅ `ctype` - Character type checking
- ✅ `iconv` - Character encoding conversion
- ✅ `json` - JSON support

### Installation Commands by OS

#### 🐧 Ubuntu/Debian

```bash
# Update package manager
sudo apt update

# Install PHP 8.2 with required extensions
sudo apt install php8.2 php8.2-fpm php8.2-cli \
    php8.2-pdo-sqlite php8.2-sqlite3 \
    php8.2-intl php8.2-zip \
    php8.2-curl php8.2-xml \
    composer nodejs npm git

# Verify installations
php --version
composer --version
node --version
npm --version
```

#### 🍎 macOS (with Homebrew)

```bash
# Install PHP
brew install php@8.2 composer node

# Verify installations
php --version
composer --version
node --version
npm --version
```

#### 🪟 Windows

1. **PHP**: Download from [php.net](https://windows.php.net/download/) or use [Chocolatey](https://chocolatey.org/)
   ```powershell
   choco install php composer nodejs
   ```

2. **Verify**:
   ```powershell
   php --version
   composer --version
   node --version
   npm --version
   ```

3. **Alternative**: Use [Docker](#docker-setup) for consistent environment

---

## 🚀 Quick Start

For experienced developers, here's the quick installation:

```bash
# 1. Clone the repository
git clone <repository-url>
cd school-management-app

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Configure environment
cp .env .env.local

# 5. Setup database
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# 6. Build assets
npm run build

# 7. Start development server
symfony serve
# OR
php -S localhost:8000 -t public/
```

**Access the application:**
- 🌐 http://localhost:8000

---

## 📖 Detailed Installation

Step-by-step setup instructions for new developers.

### Step 1: Clone the Repository

```bash
# Clone using HTTPS
git clone https://github.com/username/school-management-app.git
cd school-management-app

# OR clone using SSH
git clone git@github.com:username/school-management-app.git
cd school-management-app

# Verify
pwd  # Should show: .../school-management-app
ls   # Should show: bin/, src/, public/, var/, etc.
```

### Step 2: Install PHP Dependencies

```bash
# Install composer dependencies
composer install

# Verify composer
composer --version

# Check that vendor directory was created
ls vendor/ | head -5
```

**Expected output:**
```
Loading composer repositories with package definitions
Installing dependencies from composer.lock
...
✓ Successfully installed X dependencies
```

### Step 3: Configure Environment Variables

```bash
# Copy environment template
cp .env .env.local

# Edit .env.local with your settings (optional for local dev)
nano .env.local
# or
code .env.local
```

**Key variables in `.env.local`:**
```env
APP_ENV=dev
APP_DEBUG=true
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data/school_management_dev.db"
```

### Step 4: Create Database Directory

```bash
# Create var/data directory if it doesn't exist
mkdir -p var/data

# Verify
ls -la var/data/
```

### Step 5: Create and Migrate Database

```bash
# Create SQLite database
php bin/console doctrine:database:create

# Output should show:
# Created database "sqlite:///%kernel.project_dir%/var/data/school_management_dev.db"

# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Output should show:
# [OK] X migrations executed
```

### Step 6: Load Test Data (Fixtures)

```bash
# Load fixture data
php bin/console doctrine:fixtures:load --no-interaction

# Output should show:
# [OK] X fixtures loaded
```

**What's loaded:**
- Users (Admin, Teachers, Students)
- Courses/Classes
- Assignments
- Sample data for testing

### Step 7: Install Frontend Dependencies

```bash
# Install npm packages
npm install

# Verify
npm list | head -10

# Build assets
npm run build

# Output should show compiled assets in public/assets/
ls public/assets/ 2>/dev/null || echo "Assets will be generated on first run"
```

### Step 8: Verify Installation

```bash
# Check database
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM users" 2>/dev/null

# Check cache
php bin/console cache:warm

# Verify directory permissions
ls -la var/data/
```

### Step 9: Start Development Server

**Option A: Symfony CLI (Recommended)**

```bash
# Install Symfony CLI (if not already installed)
# macOS: brew install symfony-cli
# Ubuntu: curl -sS https://get.symfony.com/cli/installer | bash
# Windows: https://symfony.com/download

# Start server
symfony serve

# Output:
# [OK] Web Server listening on http://127.0.0.1:8000
# Press Ctrl+C to quit
```

**Option B: PHP Built-in Server**

```bash
# Start PHP server
php -S localhost:8000 -t public/

# Output:
# Development Server (http://localhost:8000) started
# Press Ctrl+C to quit
```

### Step 10: Access Application

Open your browser and navigate to:

```
🌐 http://localhost:8000
```

You should see the School Management Application homepage.

---

## 🐳 Docker Setup

For a containerized development environment, use Docker.

### Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+

### Docker Installation

```bash
# Start containers
docker-compose up -d

# Output should show:
# Creating school-management-php ... done
# Creating school-management-nginx ... done

# Verify services are running
docker-compose ps

# Output:
# NAME                     STATUS
# school-management-php    Up
# school-management-nginx  Up
```

### Docker Database Setup

```bash
# Install PHP dependencies
docker-compose exec php composer install

# Create and migrate database
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction

# Build assets
docker-compose exec php npm install
docker-compose exec php npm run build
```

### Docker Access

```
🌐 http://localhost:8080
```

### Useful Docker Commands

```bash
# View logs
docker-compose logs -f php
docker-compose logs -f nginx

# Execute commands in container
docker-compose exec php php bin/console cache:clear
docker-compose exec php bash  # Interactive shell

# Stop containers
docker-compose stop

# Remove containers and volumes
docker-compose down -v

# Rebuild images (after Dockerfile changes)
docker-compose up -d --build
```

For more details, see [DOCKER_SETUP.md](DOCKER_SETUP.md).

---

## 🧪 Testing

### Running Tests

```bash
# Run all tests
php bin/phpunit

# Run specific test file
php bin/phpunit tests/Unit/UserRepositoryTest.php

# Run tests with coverage
php bin/phpunit --coverage-text --coverage-html var/coverage

# Run tests in parallel (faster)
php bin/phpunit --process-isolation
```

**Expected output:**
```
PHPUnit X.X.X by Sebastian Bergmann and contributors.

....................                                   20 / 20 (100%)

Time: 2.34s, Memory: 8.00 MB

OK (20 tests, 40 assertions)
```

### Code Quality Checks

```bash
# PHP Code Sniffer (PSR-12 compliance)
composer cs-check

# PHPStan (Static analysis - Level 5)
composer stan

# All checks at once
bash bin/check-ci.sh
```

### Coverage Requirements

- Minimum: 75% code coverage
- Target: 85%+ code coverage

View coverage report:
```bash
# Generate HTML coverage report
php bin/phpunit --coverage-html var/coverage

# Open in browser
open var/coverage/index.html
# or
xdg-open var/coverage/index.html
# or
start var/coverage/index.html
```

---

## 👤 Default Credentials

The fixture data includes default users for testing:

### Admin Account
```
Email:    admin@school.com
Password: admin123
Role:     Administrator
```

### Teacher Account
```
Email:    teacher@school.com
Password: teacher123
Role:     Teacher
Courses:  2-3 assigned courses
```

### Student Account
```
Email:    student@school.com
Password: student123
Role:     Student
Enrolled: 3-4 courses
```

### Login URL
```
🔐 http://localhost:8000/login
```

⚠️ **Security Note**: Change these credentials immediately in production!

---

## 🔧 Troubleshooting

### Common Issues and Solutions

#### 1️⃣ SQLite Database Errors

**Problem**: `SQLSTATE[HY000]: General error`

**Solution**:
```bash
# Option 1: Delete and recreate database
rm var/data/school_management_dev.db*
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction

# Option 2: Check file permissions
chmod 755 var/data/
chmod 644 var/data/*.db

# Option 3: Clear cache
php bin/console cache:clear
```

#### 2️⃣ Permission Denied on var/ Directory

**Problem**: `Warning: fopen(var/log/dev.log): Failed to open stream`

**Solution**:
```bash
# Fix permissions
chmod -R 775 var/
chmod -R 775 public/

# On macOS/Linux with specific user
sudo chown -R $USER:$USER var/
sudo chown -R $USER:$USER public/

# Docker-specific
docker-compose exec php chown -R app:app /var/www/var
```

#### 3️⃣ Composer "Out of Memory" Error

**Problem**: `Allowed memory size exceeded`

**Solution**:
```bash
# Increase PHP memory limit
php -d memory_limit=-1 /usr/local/bin/composer install

# OR set in php.ini
memory_limit = 512M  # or higher
```

#### 4️⃣ Node/npm Dependencies Issues

**Problem**: `npm ERR! code ERESOLVE`

**Solution**:
```bash
# Clear npm cache
npm cache clean --force

# Remove node_modules and package-lock.json
rm -rf node_modules package-lock.json

# Reinstall
npm install

# Build
npm run build
```

#### 5️⃣ Database Already Exists

**Problem**: `Database "sqlite://..." already exists`

**Solution**:
```bash
# Use --if-not-exists flag (already included in setup)
php bin/console doctrine:database:create --if-not-exists

# OR force recreation
rm var/data/school_management_dev.db
php bin/console doctrine:database:create
```

#### 6️⃣ Port Already in Use

**Problem**: `Port 8000 is already in use`

**Solution**:
```bash
# Use different port
symfony serve --port=8001
# OR
php -S localhost:8001 -t public/

# Find process using port (Linux/macOS)
lsof -i :8000
# Kill process
kill -9 <PID>

# Windows
netstat -ano | findstr :8000
taskkill /PID <PID> /F
```

#### 7️⃣ Cache Not Clearing

**Problem**: Old code still running

**Solution**:
```bash
# Clear all caches
php bin/console cache:clear

# Warmup cache
php bin/console cache:warmup

# Clear for specific environment
php bin/console cache:clear --env=dev
php bin/console cache:clear --env=prod
```

#### 8️⃣ PHP Extensions Missing

**Problem**: `Call to undefined function` for PDO, intl, etc.

**Solution**:
```bash
# Check installed extensions
php -m

# Missing extension error output example:
# PHP Fatal error: Call to undefined function utf8_encode()
# → Missing: php-intl

# Install missing extensions
# Ubuntu/Debian
sudo apt install php8.2-intl php8.2-pdo-sqlite php8.2-zip

# macOS
brew install php@8.2
# Then enable extensions in php.ini

# Reload PHP-FPM (if using FPM)
sudo systemctl restart php8.2-fpm
```

#### 9️⃣ Migrations Not Running

**Problem**: `No migrations found to execute`

**Solution**:
```bash
# Check migration status
php bin/console doctrine:migrations:status

# List available migrations
php bin/console doctrine:migrations:list

# If migrations directory empty, create one
mkdir -p migrations

# Generate migration from current schema
php bin/console make:migration
```

#### 🔟 Database Connection Issues

**Problem**: `SQLSTATE[HY000]: could not find driver`

**Solution**:
```bash
# Verify DATABASE_URL in .env
cat .env | grep DATABASE_URL

# Should show:
# DATABASE_URL="sqlite:///%kernel.project_dir%/var/data/school_management_dev.db"

# Test connection
php bin/console doctrine:query:sql "SELECT 1"

# Check PHP version and extensions
php -v
php -m | grep pdo
```

### Getting Help

If you encounter issues not listed above:

1. **Check logs**:
   ```bash
   # Application logs
   tail -f var/log/dev.log
   
   # Web server logs (Docker)
   docker-compose logs -f nginx
   ```

2. **Check documentation**:
   - [Symfony Docs](https://symfony.com/doc/)
   - [Doctrine Docs](https://www.doctrine-project.org/)
   - [README.md](README.md)
   - [DOCKER_SETUP.md](DOCKER_SETUP.md)

3. **Debug mode**:
   ```bash
   # Enable detailed error messages
   APP_DEBUG=true php bin/console [command]
   ```

---

## ✅ Verification Checklist

After installation, verify everything works:

- [ ] Repository cloned successfully
- [ ] PHP dependencies installed (`vendor/` exists)
- [ ] Database created (`var/data/school_management_dev.db` exists)
- [ ] Migrations executed successfully
- [ ] Fixtures loaded (users in database)
- [ ] Assets built (`public/assets/` exists)
- [ ] Development server starts without errors
- [ ] Application loads at http://localhost:8000
- [ ] Login page accessible at http://localhost:8000/login
- [ ] Tests pass with `php bin/phpunit`
- [ ] Code quality checks pass with `composer stan`

---

## 📚 Next Steps

Once installation is complete:

1. **Explore the application**:
   - Create a course
   - Enroll students
   - Post assignments
   - Grade submissions

2. **Read the documentation**:
   - [README.md](README.md) - Project overview
   - [API.md](docs/API.md) - API documentation
   - [ARCHITECTURE.md](docs/ARCHITECTURE.md) - System design

3. **Start development**:
   - Create entities: `php bin/console make:entity`
   - Generate migrations: `php bin/console make:migration`
   - Create controllers: `php bin/console make:controller`

4. **Run tests regularly**:
   ```bash
   php bin/phpunit
   ```

5. **Check code quality**:
   ```bash
   composer stan
   composer cs-check
   ```

---

## 🆘 Support

For issues, questions, or contributions:

- 📧 Email: support@school-management.local
- 🐛 Bug Reports: [GitHub Issues](https://github.com/username/school-management-app/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/username/school-management-app/discussions)

---

## 📄 Related Documentation

- [README.md](README.md) - Project overview
- [DOCKER_SETUP.md](DOCKER_SETUP.md) - Docker configuration
- [CONFIG_GUIDE.md](CONFIG_GUIDE.md) - Configuration options
- [API.md](docs/API.md) - API endpoints
- [DEPLOYMENT.md](DEPLOYMENT.md) - Production deployment

---

**Last Updated**: December 26, 2025
**Version**: 1.0.0
