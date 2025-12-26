# Docker Quick Reference

## Files Created

```
docker-compose.yml              # Service orchestration
.dockerignore                   # Build exclusions
docker/
├── php/
│   ├── Dockerfile             # PHP 8.2-FPM image
│   ├── php.ini               # PHP configuration
│   └── opcache.ini           # Opcache settings
└── nginx/
    └── default.conf          # Nginx configuration
```

## Quick Start

```bash
# Start containers
docker-compose up -d

# View logs
docker-compose logs -f php

# Access application
# → http://localhost:8080
```

## Essential Commands

```bash
# Execute command in PHP container
docker-compose exec php bash

# Run Symfony commands
docker-compose exec php php bin/console cache:clear
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:migrations:migrate

# Run tests
docker-compose exec php php bin/phpunit

# Install/update dependencies
docker-compose exec php composer install
docker-compose exec php npm install

# Stop containers
docker-compose down

# View service status
docker-compose ps
```

## Services

| Service | Port | Purpose |
|---------|------|---------|
| Nginx | 8080 | Web server |
| PHP-FPM | 9000 | Application (internal) |

## Volumes

| Volume | Mount | Purpose |
|--------|-------|---------|
| `sqlite_data` | `/var/www/var/data` | SQLite database |
| `php_cache` | `/var/www/var/cache` | Cache files |
| `php_logs` | `/var/www/var/log` | Application logs |
| `nginx_logs` | `/var/log/nginx` | Web logs |
| `.` (bind) | `/var/www` | Project code |

## Environment

- **APP_ENV**: dev
- **APP_DEBUG**: true
- **DATABASE_URL**: sqlite:///:memory: (in-memory, or filesystem)
- **Database File**: `var/data/school_management_dev.db`

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Port 8080 in use | Change port in `docker-compose.yml` |
| Services not starting | Run `docker-compose logs` to see errors |
| Database errors | Delete volume: `docker-compose down -v` |
| Code changes not reflected | Check cache: `docker-compose exec php php bin/console cache:clear` |

## First Run Setup

```bash
# 1. Start containers
docker-compose up -d

# 2. Install dependencies
docker-compose exec php composer install

# 3. Setup database
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:migrations:migrate
docker-compose exec php php bin/console doctrine:fixtures:load

# 4. Build assets (if needed)
docker-compose exec php npm install
docker-compose exec php npm run build

# 5. Access application
# → http://localhost:8080
```

For detailed information, see [DOCKER_SETUP.md](DOCKER_SETUP.md)
