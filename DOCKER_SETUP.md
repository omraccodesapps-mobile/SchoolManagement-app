# Docker Setup for School Management Application

Complete Docker configuration for Symfony 7.4 development with PHP 8.2-FPM, Nginx, and SQLite.

## 📁 File Structure

```
docker/
├── php/
│   ├── Dockerfile          # PHP 8.2-FPM image definition
│   ├── php.ini            # PHP configuration
│   └── opcache.ini        # Opcache configuration
└── nginx/
    └── default.conf       # Nginx configuration
docker-compose.yml         # Service orchestration
.dockerignore             # Docker build exclusions
```

## 🚀 Quick Start

### Prerequisites
- Docker Engine 20.10+
- Docker Compose 2.0+

### Start Services

```bash
# Start all services in background
docker-compose up -d

# View logs
docker-compose logs -f php
docker-compose logs -f nginx

# Stop services
docker-compose down

# Remove volumes (careful - deletes data!)
docker-compose down -v
```

### Access Application

- **Web**: http://localhost:8080
- **Database**: SQLite at `var/data/school_management_dev.db`

## 📋 Services

### PHP Service (php:9000)

**Container**: `school-management-php`

**Image**: Custom built from `docker/php/Dockerfile`

**Features:**
- PHP 8.2-FPM
- Extensions: pdo_sqlite, intl, zip, opcache
- Composer installed
- Non-root user (app:1000)
- Health checks enabled

**Volumes:**
- `.:/var/www:cached` - Code sync
- `sqlite_data:/var/www/var/data` - Database persistence
- `php_cache:/var/www/var/cache` - Cache
- `php_logs:/var/www/var/log` - Logs

**Environment Variables:**
- `APP_ENV=dev`
- `APP_DEBUG=true`
- `DATABASE_URL=sqlite:///%var_dir%/school_management_dev.db`

### Nginx Service (port 8080)

**Container**: `school-management-nginx`

**Image**: `nginx:alpine`

**Features:**
- Lightweight Alpine-based image
- Symfony routing configured
- Security headers
- Static file caching
- Health checks enabled

**Volumes:**
- `.:/var/www:cached` - Code sync
- `./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf` - Config
- `nginx_logs:/var/log/nginx` - Logs

**Ports:**
- `8080:80` - HTTP access

## 🐳 Dockerfile Details

### Base Image
```dockerfile
FROM php:8.2-fpm-alpine
```

### Installed Extensions
1. **pdo_sqlite** - SQLite database support
2. **intl** - Internationalization
3. **zip** - Archive handling
4. **opcache** - Opcode caching

### Key Features
- Alpine Linux (small image size ~150MB)
- Non-root user `app` (security)
- Health checks via PHP-FPM status
- Proper file permissions
- Composer pre-installed
- Memory limit: 512MB

### Build Command
```bash
docker build -t school-management:php -f docker/php/Dockerfile .
```

## ⚙️ Configuration Files

### php.ini (Development)
- Display errors enabled
- Memory limit: 512MB
- Session handling configured
- File upload support: 100MB

### opcache.ini (Development)
- Opcache enabled for performance
- Validation on every request (development mode)
- Memory: 256MB
- Max cached files: 20,000

### default.conf (Nginx)
- Symfony front controller routing
- PHP-FPM upstream
- Security headers (CSP, X-Frame-Options, etc.)
- Static file caching (1 day)
- Hidden file protection
- Health check endpoint
- Metrics endpoint

## 📊 Volumes

### Named Volumes (Persistent Data)

| Volume | Mount | Purpose |
|--------|-------|---------|
| `sqlite_data` | `/var/www/var/data` | SQLite database files |
| `php_cache` | `/var/www/var/cache` | PHP cache |
| `php_logs` | `/var/www/var/log` | Application logs |
| `nginx_logs` | `/var/log/nginx` | Web server logs |

### Bind Mounts (Development)
- Project root → `/var/www` (cached for performance)

## 🌐 Networks

**Network**: `school-network` (bridge)

All services communicate via this bridge network:
- PHP communicates with Nginx via upstream
- Hostname resolution works automatically

## 🔧 Common Commands

### Docker Compose

```bash
# Start services
docker-compose up -d

# View logs (all services)
docker-compose logs -f

# View specific service logs
docker-compose logs -f php
docker-compose logs -f nginx

# Execute command in PHP container
docker-compose exec php bash

# Run Symfony commands
docker-compose exec php php bin/console cache:clear
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:migrations:migrate

# Run tests
docker-compose exec php php bin/phpunit

# Install dependencies
docker-compose exec php composer install
docker-compose exec php npm install

# Stop services
docker-compose stop

# Remove services (keep volumes)
docker-compose down

# Remove services and volumes
docker-compose down -v

# Rebuild images
docker-compose up -d --build
```

### Symfony Commands Inside Container

```bash
# Cache clearing
docker-compose exec php php bin/console cache:clear --env=dev

# Database operations
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:migrations:migrate
docker-compose exec php php bin/console doctrine:fixtures:load

# Asset building
docker-compose exec php npm run build

# Code quality
docker-compose exec php composer cs-check
docker-compose exec php composer stan
```

## 🏥 Health Checks

### PHP Service
- Status: `/healthcheck.sh` (checks PHP-FPM)
- Interval: 10 seconds
- Timeout: 5 seconds
- Retries: 3

### Nginx Service
- Status: HTTP GET to `/` 
- Interval: 10 seconds
- Timeout: 5 seconds
- Retries: 3

Check health status:
```bash
docker-compose ps
```

## 📝 Environment Variables

Set in `docker-compose.yml`:

```yaml
environment:
  - APP_ENV=dev
  - APP_DEBUG=true
  - DATABASE_URL=sqlite:///%var_dir%/school_management_dev.db
  - PHP_IDE_CONFIG=serverName=localhost
```

## 🔐 Security Considerations

### Implemented
- ✅ Non-root user (`app:1000`)
- ✅ Security headers (CSP, X-Frame-Options, etc.)
- ✅ Hidden file protection
- ✅ Sensitive file denial (.env, ~)
- ✅ Network isolation

### Production Recommendations
- Use production-grade base images (not Alpine for critical apps)
- Implement secrets management
- Use environment-specific configurations
- Enable resource limits
- Use health checks with restart policies
- Implement proper logging aggregation
- Use reverse proxy (Traefik, nginx-proxy)
- Regular image updates and scanning

## 🐛 Troubleshooting

### Services Not Starting

```bash
# Check service status
docker-compose ps

# View detailed logs
docker-compose logs php
docker-compose logs nginx

# Validate docker-compose.yml
docker-compose config
```

### Permission Denied Errors

```bash
# Fix ownership issues
docker-compose exec php chown -R app:app /var/www
```

### Port Already in Use

Change port in `docker-compose.yml`:
```yaml
ports:
  - "8081:80"  # Use 8081 instead of 8080
```

### Database Lock/Corruption

```bash
# Remove and recreate database
docker-compose down -v
docker-compose up -d
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:migrations:migrate
```

### Connection Refused

Ensure services are running:
```bash
docker-compose ps

# If not running, start them
docker-compose up -d

# Check PHP-FPM is ready
docker-compose exec php php -v
```

## 📚 Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose](https://docs.docker.com/compose/)
- [PHP Docker Hub](https://hub.docker.com/_/php)
- [Nginx Docker Hub](https://hub.docker.com/_/nginx)
- [Symfony Docker Guide](https://symfony.com/doc/current/setup/docker.html)

## 📖 Next Steps

1. **Start containers:**
   ```bash
   docker-compose up -d
   ```

2. **Install dependencies:**
   ```bash
   docker-compose exec php composer install
   ```

3. **Set up database:**
   ```bash
   docker-compose exec php php bin/console doctrine:database:create
   docker-compose exec php php bin/console doctrine:migrations:migrate
   ```

4. **Load fixtures:**
   ```bash
   docker-compose exec php php bin/console doctrine:fixtures:load
   ```

5. **Access application:**
   - Browser: http://localhost:8080
   - Container: `docker-compose exec php bash`

## 🔄 Development Workflow

### File Changes (Code Sync)
- Changes to local files appear immediately in container
- PHP-FPM reloads code automatically
- No rebuild needed for code changes

### Dependency Updates
```bash
# Update composer
docker-compose exec php composer update

# Update npm
docker-compose exec php npm update
```

### Database Changes
```bash
# Create migration
docker-compose exec php php bin/console make:migration

# Run migrations
docker-compose exec php php bin/console doctrine:migrations:migrate
```

### Rebuild Images
```bash
# Rebuild PHP image (after Dockerfile changes)
docker-compose up -d --build

# Force rebuild (no cache)
docker-compose up -d --build --no-cache
```

## 🎯 Performance Tips

1. **Use bind mount cache on Mac/Windows:**
   ```yaml
   volumes:
     - .:/var/www:cached
   ```

2. **Enable Opcache:**
   Configured by default in `docker/php/opcache.ini`

3. **Use Alpine images:**
   Already configured (saves ~500MB per image)

4. **Limit logging:**
   Configure in nginx and PHP

5. **Resource limits:**
   Uncomment in `docker-compose.yml` for production

---

For more information, see the main [README.md](../README.md).
