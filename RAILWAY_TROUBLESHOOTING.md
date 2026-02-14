# Railway Troubleshooting Quick Reference

## Quick Diagnostics (Run in Railway Shell)

### 1. Is the app running?
```bash
curl -I http://localhost:8080/health
```
**Expected:** `HTTP/1.1 200 OK`

### 2. What error are you getting?
```bash
# View last 50 lines of logs
tail -50 /var/log/nginx/error.log
tail -50 /var/log/php-error.log

# Or in Railway dashboard: Logs tab
```

### 3. Are processes running?
```bash
ps aux | grep -E "php-fpm|nginx|supervisor"
```
**Expected:** See php-fpm, nginx, and supervisord processes

---

## Common Issues & Fixes

### ❌ Error: "There are no commands defined in the 'server' namespace"

**Cause:** `symfony server:start` or `bin/console server:run` in production
**Fix:** This is fixed in the new Dockerfile. Deploy the updated code.

---

### ❌ HTTP 500 - No logs visible

**Cause:** Error logging not configured
**Fix:** Logs now go to `/dev/stderr`. Check Railway logs viewer.

**Immediate check:**
```bash
# View all output
tail -100 /var/log/php-error.log
tail -100 /var/log/nginx/error.log
```

---

### ❌ "Unable to load APP_SECRET"

**Cause:** APP_SECRET not set in Railway environment
**Fix:**
1. Go to Railway Dashboard → Your Service → Settings
2. Add environment variable:
   ```
   APP_SECRET=<generate_with_php_command>
   ```
3. Generate value locally:
   ```bash
   php -r "echo bin2hex(random_bytes(32));"
   ```
4. Restart service

---

### ❌ "vendor/autoload.php not found"

**Cause:** Composer dependencies not installed during build
**Fix:**
1. Check Docker build logs for composer errors
2. Ensure `composer.lock` exists and is in git
3. Rebuild:
   ```bash
   docker build -t app:latest .
   ```

---

### ❌ PHP-FPM not responding (502 Bad Gateway from Nginx)

**Cause:** PHP-FPM crashed or not listening on 127.0.0.1:9000
**Fix:**
```bash
# Check if PHP-FPM is running
ps aux | grep php-fpm

# If not running, check logs
tail -50 /var/log/php-fpm.err.log

# Get supervisor status
supervisorctl status

# Try to restart
supervisorctl restart php-fpm
```

---

### ❌ Nginx won't start

**Cause:** Configuration error
**Fix:**
```bash
# Validate config
nginx -t

# Should show error location
# Fix the error in docker/nginx/railway.conf
# Then rebuild and redeploy
```

---

### ❌ Cache not warming up (empty var/cache/prod/)

**Cause:** Bundle or configuration error
**Fix:**
```bash
cd /var/www/app

# Try to warm cache manually
php bin/console cache:warmup --env=prod

# Check output for errors
php bin/console debug:config

# Validate routing
php bin/console debug:router
```

---

### ❌ Database errors "database locked" or "SQLITE_BUSY"

**Cause:** Concurrent access or corrupted SQLite database
**Fix:**
```bash
# Check if database exists and is valid
sqlite3 /var/www/app/var/data/school_management_prod.db ".tables"

# If corrupted, back it up and recreate
cp /var/www/app/var/data/school_management_prod.db{,.backup}
rm /var/www/app/var/data/school_management_prod.db

# Then in Railway shell run migrations
php bin/console doctrine:database:create --env=prod
php bin/console doctrine:migrations:migrate --env=prod
```

---

### ❌ Static files (CSS, JS) not loading (404)

**Cause:** Asset mapper or public files issue
**Fix:**
```bash
# Check if public/index.php exists
ls -la /var/www/app/public/

# Dump and build assets
php bin/console asset-map:compile

# Check nginx can read files
ls -la /var/www/app/public/bundles/
ls -la /var/www/app/public/assets/
```

---

### ⚠️ Slow response times (>5 seconds)

**Cause:** PHP-FPM saturation or database queries
**Fix:**
```bash
# Check slowlog
tail -20 /var/log/php-fpm/slowlog.log

# Check PHP-FPM pool stats
php -i | grep -A 5 "fpm"

# Monitor active connections
watch -n 1 'netstat -anp | grep -E "php-fpm|nginx" | wc -l'

# If too many connections, increase pm.max_children
# Edit docker/php/pool.conf and redeploy
```

---

### ⚠️ Memory/disk filling up

**Check usage:**
```bash
# Memory
free -h

# Disk
df -h

# Largest directories
du -sh /var/www/app/* | sort -rh | head -10
```

**Fix:**
```bash
# Clear old logs
rm -f /var/log/php-fpm/*.log.*
rm -f /var/log/nginx/*.log.*
rm -f /var/log/supervisor/*.log.*

# Clear cache (generates on next request)
rm -rf /var/www/app/var/cache/prod/*

# Check for large uploads or temp files
ls -lh /var/www/app/public/uploads/
```

---

### ❌ Permission denied errors

**Check permissions:**
```bash
# Should be www-data
ls -la /var/www/app/var/

# If wrong, fix
chown -R www-data:www-data /var/www/app/var
chmod -R 775 /var/www/app/var/cache /var/www/app/var/log

# Also fix nginx/php-fpm dirs
chown -R www-data:www-data /var/log/nginx
chown -R www-data:www-data /var/log/php-fpm
```

---

### ❌ 503 Service Temporarily Unavailable

**Cause:** Nginx can't reach PHP-FPM
**Fix:**
```bash
# Check if PHP-FPM is running
supervisorctl status php-fpm

# Should show RUNNING

# If not, start it
supervisorctl start php-fpm

# Check the error
supervisorctl tail php-fpm stderr
```

---

## Complete Diagnostic Bundle

Run this to collect all diagnostic info:

```bash
#!/bin/bash
echo "=== ENVIRONMENT ==="
env | grep -E "^APP_|^PORT|^RAILW"

echo -e "\n=== PROCESSES ==="
ps aux | grep -E "php-fpm|nginx|supervisor"

echo -e "\n=== LISTENING PORTS ==="
netstat -tlnp 2>/dev/null | grep -E ":(9000|8080)" || ss -tlnp 2>/dev/null | grep -E ":(9000|8080)"

echo -e "\n=== FILE STRUCTURE ==="
ls -la /var/www/app/var/

echo -e "\n=== NGINX CONFIG ==="
nginx -t

echo -e "\n=== SUPERVISOR STATUS ==="
supervisorctl status

echo -e "\n=== PHP ERRORS (Last 20 lines) ==="
tail -20 /var/log/php-error.log

echo -e "\n=== NGINX ERRORS (Last 20 lines) ==="
tail -20 /var/log/nginx/error.log

echo -e "\n=== CACHE STATUS ==="
ls -la /var/www/app/var/cache/prod/ | head -20

echo -e "\n=== DATABASE STATUS ==="
ls -lh /var/www/app/var/data/*.db

echo -e "\n=== TEST HTTP ==="
curl -I http://localhost:8080/health 2>&1
```

Save this as a script and run it:
```bash
bash diagnostic.sh > diagnostic-report.txt
# Download diagnostic-report.txt for analysis
```

---

## When to Restart

### Restart PHP-FPM only:
```bash
supervisorctl restart php-fpm
```

### Restart Nginx only:
```bash
supervisorctl restart nginx
```

### Restart all services:
```bash
supervisorctl restart all
```

### Full service restart (in Railway):
1. Go to Railway Dashboard → Your Service → Settings
2. Click "Restart"
3. Wait 30-60 seconds for container to start

---

## Emergency Fixes

### If everything is broken (nuclear option):

1. **Rebuild the Docker image:**
   ```bash
   docker build -t app:latest . --no-cache
   docker run -p 8080:8080 app:latest
   ```

2. **If that fails, check Dockerfile errors:**
   - Are composer.json and composer.lock present?
   - Does the build log mention missing files?
   - Is PHP version 8.2 available?

3. **If database is corrupted:**
   ```bash
   rm -f /var/www/app/var/data/school_management_prod.db
   # Service will recreate on restart
   ```

4. **If cache is broken:**
   ```bash
   rm -rf /var/www/app/var/cache/prod/*
   php bin/console cache:warmup --env=prod
   ```

5. **Last resort - rollback:**
   - In Railway: Go to Deployments → Select previous → Redeploy
   - This starts the last known good version

---

## Success Indicators ✅

- [ ] `curl http://localhost:8080/health` returns 200
- [ ] `ps aux` shows php-fpm, nginx, supervisord running
- [ ] `supervisorctl status` shows all RUNNING
- [ ] `/var/www/app/var/cache/prod/` has files
- [ ] `/var/www/app/var/data/school_management_prod.db` exists
- [ ] No errors in last 20 lines of logs
- [ ] Home page loads without 500 error
- [ ] Database queries work

---

## Need More Help?

### Check Rails production logs format:
```bash
journalctl -n 100 -f         # If using systemd
tail -f /var/log/syslog      # If using syslog
```

### View Supervisor logs:
```bash
supervisorctl tail php-fpm stdout
supervisorctl tail php-fpm stderr
supervisorctl tail nginx stdout
supervisorctl tail nginx stderr
```

### Enable debug mode (temporary):
```bash
export APP_DEBUG=1
php bin/console cache:clear --env=prod
# But remember to turn it off before exposing to users!
```

---

**Remember:** All logs now output to `/dev/stdout` and `/dev/stderr`, which Railway captures and shows in the Logs viewer. Check there first!
