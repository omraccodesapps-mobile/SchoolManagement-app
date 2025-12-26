# Installation Documentation - Index

Complete installation documentation for the School Management Application.

## 📚 Documentation Files

### Main Installation Guide
**[INSTALLATION.md](INSTALLATION.md)** - 400+ lines
- ✅ Complete setup instructions for all users
- ✅ OS-specific prerequisites (Ubuntu, macOS, Windows)
- ✅ Quick Start (5 min) and Detailed Installation (30 min)
- ✅ Docker alternative setup
- ✅ Testing and code quality verification
- ✅ 10 troubleshooting scenarios with solutions
- ✅ Verification checklist

### Quick Reference Card
**[INSTALL_QUICK_REF.md](INSTALL_QUICK_REF.md)** - One-page
- ⚡ 5-minute setup script (copy-paste ready)
- 📋 Quick prerequisites table
- 🐳 Docker quick start
- 👤 Test account credentials
- 🧪 Common testing commands
- 🔧 Essential command reference
- 🆘 Quick troubleshooting
- 📚 Links to detailed docs

### Documentation Summaries
- [INSTALLATION_SUMMARY.md](INSTALLATION_SUMMARY.md) - Overview of created docs
- [INSTALLATION_GUIDE_COMPLETE.md](INSTALLATION_GUIDE_COMPLETE.md) - Complete details

---

## 🎯 Quick Navigation

### I'm new to this project → Start here
1. Read [Prerequisites](#prerequisites) below
2. Follow [Quick Start](#quick-start) (5 min)
3. Read [Setup & Run](#setup--run) section
4. Verify with checklist

### I'm an experienced developer → Quick setup
1. Check [Prerequisites](#prerequisites)
2. Copy commands from [INSTALL_QUICK_REF.md](INSTALL_QUICK_REF.md)
3. Reference troubleshooting if needed

### I need detailed instructions
Read [INSTALLATION.md](INSTALLATION.md) - covers everything in detail

### I want to use Docker
Follow Docker section in [INSTALLATION.md](INSTALLATION.md) or use [DOCKER_SETUP.md](../DOCKER_SETUP.md)

---

## 📋 Prerequisites

### Required Software

```bash
# Check what you have installed
php --version          # Need: 8.2+
composer --version     # Need: 2.0+
node --version        # Need: 18+
npm --version         # Need: 9.0+
git --version         # Any version
```

### Required PHP Extensions

```bash
# Verify these are installed
php -m | grep -E "pdo_sqlite|intl|zip|ctype|iconv|json"
```

✅ **Must have:**
- pdo_sqlite
- intl
- zip
- ctype
- iconv
- json

### Installation by OS

| OS | Command |
|----|---------|
| **Ubuntu/Debian** | `sudo apt install php8.2 composer nodejs git` |
| **macOS** | `brew install php@8.2 composer node` |
| **Windows** | Use [Chocolatey](https://chocolatey.org/) or [Docker](../DOCKER_SETUP.md) |

---

## ⚡ Quick Start (5 Minutes)

```bash
# 1. Clone repository
git clone <repository-url>
cd school-management-app

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Configure environment
cp .env .env.local

# 5. Create database
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# 6. Build assets
npm run build

# 7. Start server
symfony serve

# 8. Open browser
# → http://localhost:8000
```

**Done!** The application is running.

---

## 🔐 Test Accounts

Login at http://localhost:8000/login

| Role | Email | Password |
|------|-------|----------|
| **Admin** | admin@school.com | admin123 |
| **Teacher** | teacher@school.com | teacher123 |
| **Student** | student@school.com | student123 |

---

## 📖 Setup & Run

### Option 1: Standard Installation (Recommended)

**See Full Guide:** [INSTALLATION.md - Detailed Installation](INSTALLATION.md#detailed-installation)

- Detailed explanation of each step
- Expected outputs shown
- Verification at each stage
- Perfect for new developers

### Option 2: Docker Installation

**See Full Guide:** [INSTALLATION.md - Docker Setup](INSTALLATION.md#docker-setup)

Or quick reference: [DOCKER_SETUP.md](../DOCKER_SETUP.md)

```bash
docker-compose up -d
docker-compose exec php composer install
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
# → http://localhost:8080
```

### Option 3: Quick Copy-Paste

**See Quick Reference:** [INSTALL_QUICK_REF.md](INSTALL_QUICK_REF.md)

All commands in one place, minimal explanation.

---

## 🧪 Verify Installation

```bash
# Check database was created
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM users"

# Run tests
php bin/phpunit

# Check code quality
composer stan         # PHPStan analysis
composer cs-check     # Code style

# View coverage
php bin/phpunit --coverage-html var/coverage
# Open: var/coverage/index.html
```

**All checks passing?** ✅ You're ready to develop!

---

## 🆘 Common Issues

### SQLite Database Error
```bash
rm var/data/school_management_dev.db*
php bin/console doctrine:database:create
```

### Permission Denied on var/
```bash
chmod -R 775 var/
chmod -R 775 public/
```

### Port 8000 Already in Use
```bash
symfony serve --port=8001
```

### Composer Memory Error
```bash
php -d memory_limit=-1 /usr/bin/composer install
```

### PHP Extension Missing
See [INSTALLATION.md - Troubleshooting](INSTALLATION.md#troubleshooting)

---

## 📚 Related Documentation

### Project Setup & Configuration
- [README.md](../README.md) - Project overview
- [CONFIG_GUIDE.md](../CONFIG_GUIDE.md) - Configuration options
- [START_HERE.md](../START_HERE.md) - Getting started

### Development Guides
- [docs/API.md](../docs/API.md) - API endpoints
- [docs/DETAILED_STEPS.md](../docs/DETAILED_STEPS.md) - Development steps
- [docs/SLIDES_README.md](../docs/SLIDES_README.md) - Architecture slides

### Deployment & CI/CD
- [DEPLOYMENT.md](../DEPLOYMENT.md) - Production setup
- [DOCKER_SETUP.md](../DOCKER_SETUP.md) - Docker guide
- [.github/workflows/ci.yml](../.github/workflows/ci.yml) - CI/CD pipeline

### Database & Scripts
- [DB_SETUP_SCRIPTS.md](../DB_SETUP_SCRIPTS.md) - Database setup scripts
- [bin/setup-db.sh](../bin/setup-db.sh) - Bash setup script
- [bin/setup-db.bat](../bin/setup-db.bat) - Windows setup script

---

## 💡 Development Workflow

### After Installation

1. **Start the server:**
   ```bash
   symfony serve
   ```

2. **Create a database entity:**
   ```bash
   php bin/console make:entity
   ```

3. **Create a migration:**
   ```bash
   php bin/console make:migration
   php bin/console doctrine:migrations:migrate
   ```

4. **Create a controller:**
   ```bash
   php bin/console make:controller
   ```

5. **Run tests:**
   ```bash
   php bin/phpunit
   ```

### Regular Development Commands

```bash
# Clear cache
php bin/console cache:clear

# View application logs
tail -f var/log/dev.log

# Build assets (when modified)
npm run build

# Run code quality checks
composer stan
composer cs-check

# Database operations
php bin/console doctrine:query:sql "SELECT..."
php bin/console doctrine:database:drop --if-exists
php bin/console doctrine:database:create
```

---

## ✅ Installation Checklist

After following the setup steps, verify:

- [ ] PHP 8.2+ installed
- [ ] Composer dependencies installed (`vendor/` exists)
- [ ] `.env.local` file created
- [ ] Database file created (`var/data/school_management_dev.db`)
- [ ] Migrations executed successfully
- [ ] Fixtures loaded (users in database)
- [ ] Node dependencies installed (`node_modules/` exists)
- [ ] Assets built (`public/assets/` exists)
- [ ] Development server starts without errors
- [ ] Application loads at http://localhost:8000
- [ ] Login page accessible
- [ ] Tests pass (`php bin/phpunit`)
- [ ] Code quality checks pass (`composer stan`)

---

## 🚀 Next Steps

1. **Explore the application**
   - Create a course
   - Enroll students
   - Post assignments

2. **Read the documentation**
   - [docs/API.md](../docs/API.md) - Available endpoints
   - [CONFIG_GUIDE.md](../CONFIG_GUIDE.md) - Configuration options

3. **Start developing**
   - Create new entities
   - Add new features
   - Write tests

4. **Deploy when ready**
   - Follow [DEPLOYMENT.md](../DEPLOYMENT.md)
   - Use Docker for production

---

## 📞 Getting Help

**Installation issues?**

1. Check [Troubleshooting](INSTALLATION.md#troubleshooting) section
2. Check [INSTALL_QUICK_REF.md](INSTALL_QUICK_REF.md) for quick solutions
3. Review [CONFIG_GUIDE.md](../CONFIG_GUIDE.md) for configuration
4. Check application logs: `tail -f var/log/dev.log`

**Still stuck?**

- 📧 Contact support
- 🐛 Create an issue on GitHub
- 💬 Start a discussion

---

## 📄 File Reference

All installation-related documentation:

```
.
├── INSTALLATION.md                      ← Main guide (400+ lines)
├── INSTALL_QUICK_REF.md                 ← Quick reference (1 page)
├── INSTALLATION_SUMMARY.md              ← Overview
├── INSTALLATION_GUIDE_COMPLETE.md       ← This index + details
├── DOCKER_SETUP.md                      ← Docker configuration
├── DOCKER_QUICK_REFERENCE.md            ← Docker quick ref
├── CONFIG_GUIDE.md                      ← Configuration options
├── README.md                            ← Project overview
├── START_HERE.md                        ← Getting started
├── DB_SETUP_SCRIPTS.md                  ← Database scripts
├── bin/setup-db.sh                      ← Bash setup script
├── bin/setup-db.bat                     ← Windows setup script
└── docker-compose.yml                   ← Docker configuration
```

---

## 📊 Documentation Statistics

| Document | Type | Lines | Purpose |
|----------|------|-------|---------|
| INSTALLATION.md | Main Guide | 400+ | Complete setup instructions |
| INSTALL_QUICK_REF.md | Quick Ref | 150+ | Copy-paste commands |
| DOCKER_SETUP.md | Docker Guide | 400+ | Docker configuration |
| CONFIG_GUIDE.md | Config | 300+ | Configuration options |
| DEPLOYMENT.md | Deployment | 400+ | Production setup |
| README.md | Overview | 200+ | Project information |

**Total Installation Documentation:** 1000+ lines

---

**Last Updated:** December 26, 2025
**Version:** 1.0.0

**Start with [INSTALLATION.md](INSTALLATION.md) → Choose your path → Follow instructions!**
