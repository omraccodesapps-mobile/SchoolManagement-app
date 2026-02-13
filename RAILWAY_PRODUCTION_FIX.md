# Railway Production Deployment Guide - 502 Bad Gateway Fix

## Problem Diagnosis

The production deployment was returning `502 Bad Gateway` because:

1. **APP_ENV=dev** - Symfony was running in development mode, causing:
   - Unoptimized cache
   - Different routing and error handling
   - Performance issues

2. **PHP-FPM startup race condition**:
   - Old start.sh used `php-fpm -D` (daemon), then Nginx tried immediate connection
   - No graceful restart mechanism if PHP-FPM crashed
   - Nginx = 502 immediately if PHP-FPM wasn't ready

3. **TCP localhost binding issue**:
   - Nginx configuration used `fastcgi_pass 127.0.0.1:9000` (TCP socket)
   - More prone to connection failures under load
   - Unix socket is more reliable and faster

4. **Cache warmup at startup**:
   - Cache was being warmed at runtime (in start.sh)
   - If this failed or was slow, early requests got 502

5. **Missing PHP-FPM configuration**:
   - Default PHP-FPM settings not optimized for production
   - No concurrency tuning

6. **No process supervision**:
   - If Nginx or PHP-FPM crashed, container would fail silently
   - Railway would see it as "running" but unresponsive

7. **Misconfigured Nginx timeout**:
   - `fastcgi_read_timeout 300` was set, but PHP-FPM wasn't responding in time

## Architecture: Fixed Production Setup

```
┌─────────────────────────────────────────┐
│  Railway (Container, PORT env var)      │
├─────────────────────────────────────────┤
│                                         │
│  ┌───────────────────────────────────┐  │
│  │  Supervisor (Process Manager)     │  │
│  │  - Manages PHP-FPM & Nginx        │  │
│  │  - Auto-restarts on crash         │  │
│  │  - Logs to /var/log/supervisor/   │  │
│  └───────────────────────────────────┘  │
│         ├─ PHP-FPM Pool (www)           │
│         │  - 50 max children             │
│         │  - 10 start servers            │
│         │  - Unix socket: /run/php-fpm.sock │
│         │  - APP_ENV=prod (env var)      │
│         │  - OPcache enabled             │
│         │                                │
│         └─ Nginx (Master + Workers)     │
│            - Listen 0.0.0.0:$PORT       │
│            - Connect to PHP via socket   │
│            - 300s timeout for slow reqs  │
│                                         │
├─────────────────────────────────────────┤
│  Symfony 7.x (Production)               │
│  - Cache warmed in Docker build         │
│  - DATABASE_URL configurable            │
│  - Migrations run on startup (if needed) │
└─────────────────────────────────────────┘
```

## Key Changes Made

### 1. **Dockerfile** (`Dockerfile`)
- Multi-stage build with `composer` and `php:8.2-fpm` base
- Dependencies installed with `--prefer-dist`
- **Cache warmup happens during build** (not at startup)
- Supervisor installed for process management
- All config files copied: nginx, php-fpm, supervisor
- Health check added

### 2. **Nginx Configuration** 
- `docker/nginx/nginx.conf`: Production-ready main config
  - OPcache settings included in main block
  - Buffer tuning for large responses
  - Gzip compression enabled
  
- `docker/nginx/railway.conf`: Site config
  - Listens on `0.0.0.0:${PORT}` (Railway style)
  - **Unix socket: `fastcgi_pass unix:/run/php-fpm.sock`** (not TCP)
  - Filename: `railway.conf` → copied to `/etc/nginx/sites-available/default`
  - 300s fastcgi_read_timeout
  - Security headers added
  - Static file caching (1 year)
  - Proper error logging

### 3. **PHP-FPM Configuration** (`docker/php/pool.conf`)
- Dynamic process manager: 50 max children, 10 start, 5 min spare
- **Unix socket with correct permissions** (www-data:www-data, 0660)
- Environment variables injected: `APP_ENV=prod`, `APP_DEBUG=0`
- 300s request timeout
- Status page at `/php-fpm-status`

### 4. **PHP.ini** (`docker/php/php.ini`)
- OPcache fully enabled with production settings
- Session handler configured
- Display errors OFF (security)
- Error logging to `/var/log/php-error.log`
- Memory limit: 256M
- Upload limit: 50M

### 5. **Supervisor Configuration** (`docker/supervisor/supervisord.conf`)
- Manages both PHP-FPM and Nginx
- Auto-restarts if either crashes
- Logs to `/var/log/supervisor/`
- Runs in foreground (nodaemon=true)
- Priority: PHP-FPM starts first, then Nginx

### 6. **Start Script** (`docker/start.sh`)
- Sets `APP_ENV=prod` and `APP_DEBUG=0`
- Validates PORT is numeric
- Uses `envsubst` to substitute `$PORT` in Nginx config
- Creates socket directory `/run` for PHP-FPM
- Final permission fixes
- **Starts Supervisor** (not individual services)
- Validates Nginx config before starting
- Graceful startup order

### 7. **Railway Configuration** (`railway.json`)
- Declares environment variables with descriptions
- `APP_ENV=prod`, `APP_DEBUG=0` as defaults
- `APP_SECRET` (no default - must set in Railway)
- `DATABASE_URL` defaults to SQLite
- `DEFAULT_URI` points to production domain

## Deployment Steps

### Step 1: Generate APP_SECRET
```bash
php -r 'echo bin2hex(random_bytes(16));'
```
Copy the output.

### Step 2: Configure Railway Environment Variables
In Railway dashboard:
1. Go to project > Variables
2. Add these variables:
   - `APP_ENV`: `prod` (should already be set)
   - `APP_DEBUG`: `0` (should already be set)
   - `APP_SECRET`: Paste the generated value
   - `DATABASE_URL`: Leave as default SQLite, or configure external DB
   - `DEFAULT_URI`: `https://school-management-production-1378.up.railway.app`

### Step 3: Deploy
```bash
git push  # Railway auto-triggers on push
```

Or force redeploy in Railway dashboard.

### Step 4: Monitor Logs
```
Railway dashboard > Deployments > Logs > View Build/Deploy logs
```

Look for:
- ✅ Nginx config valid
- ✅ PHP-FPM is running
- ✅ Services started via Supervisor

## Troubleshooting 502 Errors

### If 502 persists:

1. **Check Supervisor logs**:
```bash
docker logs <container> | grep supervisor
```

2. **PHP-FPM status**:
```bash
curl http://localhost:8080/php-fpm-status
```

3. **Nginx error.log**:
```
/var/log/nginx/error.log
```

4. **Symfony cache**:
```
ls var/cache/prod/
```
Should have files. If empty, cache warmup failed.

5. **PHP-FPM socket**:
```bash
ls -la /run/php-fpm.sock
```
Should be owned by www-data with permission 0660.

6. **Database connection**:
```bash
php bin/console doctrine:database:create --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

## Database Configuration

### SQLite (Default - Already Configured)
- File: `var/data/school_management_prod.db`
- Persists between restarts (writable var/data)
- WARNING: Shared across replicas if you scale up

### External Database (Production Ready)

**PostgreSQL Example**:
```
DATABASE_URL=postgresql://user:password@db.railway.app:5432/school_management?serverVersion=16
```

**MySQL Example**:
```
DATABASE_URL=mysql://user:password@db.railway.app:3306/school_management?serverVersion=8.0.32&charset=utf8mb4
```

Set `DATABASE_URL` in Railway Variables, then:
```bash
php bin/console doctrine:database:create --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

## Performance Tuning

### If you still get slow responses:

1. **Scale up PHP-FPM** (in `docker/php/pool.conf`):
```ini
pm.max_children = 100           # was 50
pm.start_servers = 20           # was 10
pm.max_spare_servers = 40       # was 20
```

2. **Increase Railway memory** (in Railway dashboard):
- Default: 512MB RAM
- Recommended: 1GB RAM or more

3. **CDN for static assets**:
- Nginx already sets `Cache-Control: public, immutable` for 1 year
- Connect to Cloudflare or similar

4. **Database optimization**:
- Add indexes to frequently queried columns
- Consider read replicas for scaling

## Files Modified / Created

✅ `Dockerfile` - Complete rewrite for production
✅ `docker/nginx/nginx.conf` - New main Nginx config
✅ `docker/nginx/railway.conf` - New site-specific config
✅ `docker/php/pool.conf` - New PHP-FPM pool config
✅ `docker/php/php.ini` - New production PHP settings
✅ `docker/supervisor/supervisord.conf` - New process manager config
✅ `docker/start.sh` - Rewritten startup script
✅ `railway.json` - Updated with environment variables
✅ `build.sh` - Updated build script
✅ `.env.railway` - Production environment reference

## Verification Checklist

- [ ] APP_SECRET is set in Railway variables
- [ ] DATABASE_URL is correct (or using default SQLite)
- [ ] Deploy completes without errors
- [ ] Container health check passes (GREEN in Railway)
- [ ] `curl https://school-management-production-1378.up.railway.app/` returns 200 (not 502)
- [ ] Log in and perform a test action
- [ ] Check `/php-fpm-status` shows active processes

## Support

If 502 continues:
1. Check Railway logs for errors
2. Verify all environment variables are set
3. Ensure `APP_SECRET` is not empty
4. Check that `/var/www/app/var/cache/prod/` has files (cache warmed)
5. Verify `/run/php-fpm.sock` exists and has correct permissions

---

**Last Updated**: 2026-02-13
**Production Ready**: Yes
