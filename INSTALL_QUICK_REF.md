# Installation Quick Reference

## ⚡ 5-Minute Setup

```bash
# 1. Clone
git clone <repo> && cd school-management-app

# 2. Install dependencies
composer install && npm install

# 3. Setup database
cp .env .env.local
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# 4. Build assets
npm run build

# 5. Run server
symfony serve
# → http://localhost:8000
```

## 📋 Prerequisites

| Tool | Version | Command |
|------|---------|---------|
| PHP | 8.2+ | `php --version` |
| Composer | 2.0+ | `composer --version` |
| Node.js | 18+ | `node --version` |
| npm | 9.0+ | `npm --version` |
| Git | Any | `git --version` |

**PHP Extensions Required:**
- pdo_sqlite
- intl
- zip
- ctype, iconv, json

## 🐳 Docker Quick Start

```bash
# Start containers
docker-compose up -d

# Setup
docker-compose exec php composer install
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction

# Access: http://localhost:8080
```

## 👤 Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@school.com | admin123 |
| Teacher | teacher@school.com | teacher123 |
| Student | student@school.com | student123 |

**Login**: http://localhost:8000/login

## 🧪 Testing & Quality

```bash
# Run tests
php bin/phpunit

# Code quality
composer cs-check      # PSR-12 compliance
composer stan          # PHPStan level 5 analysis

# Coverage (75% minimum)
php bin/phpunit --coverage-html var/coverage
```

## 🔧 Common Commands

```bash
# Clear cache
php bin/console cache:clear

# Database operations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load

# Create new entity
php bin/console make:entity

# Create new controller
php bin/console make:controller

# View logs
tail -f var/log/dev.log
```

## 🆘 Troubleshooting

| Issue | Solution |
|-------|----------|
| **Permission denied** | `chmod -R 775 var/ public/` |
| **SQLite error** | `rm var/data/*.db* && php bin/console doctrine:database:create` |
| **Port in use** | `symfony serve --port=8001` |
| **Cache issue** | `php bin/console cache:clear` |
| **Memory error** | `php -d memory_limit=-1 /usr/bin/composer install` |

## 📁 Important Directories

```
var/
├── data/                    # SQLite database
├── cache/                   # Application cache
├── log/                     # Application logs
└── coverage/                # Test coverage reports

public/
├── assets/                  # Built frontend assets
└── index.php                # Application entry point

src/
├── Controller/              # Symfony controllers
├── Entity/                  # Doctrine entities
├── Repository/              # Database repositories
├── Form/                    # Symfony forms
└── Service/                 # Business logic services
```

## 📚 Full Documentation

- **Setup Details**: [INSTALLATION.md](INSTALLATION.md)
- **Docker Guide**: [DOCKER_SETUP.md](DOCKER_SETUP.md)
- **Project Overview**: [README.md](README.md)
- **Configuration**: [CONFIG_GUIDE.md](CONFIG_GUIDE.md)
- **API Reference**: [docs/API.md](docs/API.md)

## ✅ Verification

After setup, verify:
```bash
# Check database
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM users"

# Check PHP extensions
php -m | grep -E "sqlite3|intl|zip"

# Test connectivity
curl http://localhost:8000/health

# Run tests
php bin/phpunit
```

---

**For detailed instructions, see [INSTALLATION.md](INSTALLATION.md)**
