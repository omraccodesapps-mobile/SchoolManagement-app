# Validation Commands - Test the Fix

## Local Testing (Before Deploying)

### Build Docker Image
```bash
docker build -t school-management:latest .
```

### Run Container Locally
```bash
docker run -it \
  --name school-management-test \
  -p 8080:8080 \
  -e APP_SECRET="test-secret-32-bytes-here-a1b2c3d4e5f6g7h8" \
  -e APP_ENV=prod \
  -e APP_DEBUG=0 \
  school-management:latest
```

### Test Health Endpoint (In new terminal)
```bash
# Should return 200 with body "ok"
curl -v http://localhost:8080/health

# Expected output:
# < HTTP/1.1 200 OK
# < Content-Length: 2
# ok
```

### Test Home Page
```bash
# Should return 200 or redirect (not 500)
curl -I http://localhost:8080/

# Expected:
# HTTP/1.1 200 OK
# or
# HTTP/1.1 302 Found (if redirects to login)
```

### View All Logs
```bash
docker logs school-management-test

# Look for:
# [SUCCESS] APP_SECRET configured
# [SUCCESS] Nginx configuration valid
# [SUCCESS] Initialization complete
# [INFO] Logs available at:

# Should NOT see:
# [FATAL]
# 500
# Cannot find vendor/autoload.php
```

### Check Running Processes
```bash
docker exec school-management-test ps aux | grep -E "php-fpm|nginx|supervisor"

# Expected to see:
# root      supervisord
# www-data  php-fpm: pool www (multiple)
# www-data  nginx: worker
```

### Cleanup
```bash
docker stop school-management-test
docker rm school-management-test
```

---

## Railway Production Testing

### After Deployment Completes

### 1. Test Health Endpoint
```bash
# Most reliable test - no database required
curl https://your-app.railway.app/health

# Expected response:
ok
```

### 2. Test HTTP Status
```bash
# Check status code
curl -I https://your-app.railway.app/health

# Expected:
# HTTP/1.1 200 OK
# Content-Length: 2
# Content-Type: text/plain
```

### 3. Test Home Page
```bash
# Should NOT return 500
curl -I https://your-app.railway.app/

# Expected:
# HTTP/1.1 200 OK
# or
# HTTP/1.1 302 Found (redirect to login)

# NOT:
# HTTP/1.1 500 Internal Server Error
```

### 4. View Railway Logs

**In Railway Dashboard:**
1. Go to Your Service → Logs tab
2. Look for these success messages:
   ```
   [STARTUP] Loading environment...
   [SUCCESS] APP_SECRET configured
   [SUCCESS] vendor/autoload.php verified
   [SUCCESS] Symfony directories ready
   [SUCCESS] Nginx configuration generated
   [SUCCESS] Nginx configuration valid
   [STARTUP] Creating and seeding SQLite database...
   [SUCCESS] Database created and migrations applied
   [SUCCESS] Cache warmed
   [SUCCESS] Permissions configured
   ✅ Initialization complete - starting services
   ```

3. Should NOT see:
   ```
   [FATAL]
   [ERROR]
   500
   Cannot find
   vendor/autoload.php
   ```

### 5. Run Validation Script in Railway Shell

**In Railway Shell:**
```bash
bash /var/www/app/docker/validate-railway.sh
```

**Expected output:**
```
✅ Success:  20
⚠️  Warnings: 1-2
❌ Errors:   0
```

If any errors, read the output to see what's wrong.

---

## Detailed Validation Checklist

### ✅ Environment Variables
```bash
# In Railway Shell:
echo "APP_ENV=$APP_ENV"
echo "APP_DEBUG=$APP_DEBUG"
echo "APP_SECRET length: ${#APP_SECRET}"

# Expected:
# APP_ENV=prod
# APP_DEBUG=0
# APP_SECRET length: 64
```

### ✅ Files Exist
```bash
# In Railway Shell:
test -f /var/www/app/vendor/autoload.php && echo "✅ autoload.php" || echo "❌ Missing"
test -f /var/www/app/public/index.php && echo "✅ index.php" || echo "❌ Missing"
test -f /var/www/app/bin/console && echo "✅ console" || echo "❌ Missing"
test -f /var/www/app/var/data/school_management_prod.db && echo "✅ database" || echo "⚠️ May not exist yet"
```

### ✅ Processes Running
```bash
# In Railway Shell:
ps aux | grep -E "php-fpm|nginx|supervisord"

# Expected to see:
# 1. supervisord (root)
# 2. php-fpm (www-data) - multiple workers
# 3. nginx (www-data) - worker process
```

### ✅ Network Listening
```bash
# In Railway Shell:
netstat -tlnp 2>/dev/null | grep -E ":(9000|8080)" || ss -tlnp 2>/dev/null | grep -E ":(9000|8080)"

# Expected:
# 127.0.0.1:9000 (PHP-FPM)
# 0.0.0.0:8080 (Nginx)
```

### ✅ Supervisor Status
```bash
# In Railway Shell:
supervisorctl status

# Expected:
# php-fpm  RUNNING   ...
# nginx    RUNNING   ...
```

### ✅ Nginx Configuration
```bash
# In Railway Shell:
nginx -t

# Expected:
# nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
# nginx: configuration file /etc/nginx/nginx.conf test is successful
```

### ✅ Cache Existence
```bash
# In Railway Shell:
ls -la /var/www/app/var/cache/prod/ | wc -l

# Expected: More than 10 files
# If < 5 files, cache didn't warm up (check logs)
```

### ✅ Permissions
```bash
# In Railway Shell:
ls -ld /var/www/app/var/

# Expected: drwxrwxr-x with www-data as owner
# If wrong: 
# chown -R www-data:www-data /var/www/app/var
# chmod -R 775 /var/www/app/var
```

### ✅ PHP Error Log
```bash
# In Railway Shell:
tail -20 /var/log/php-error.log

# Should be empty if no errors, or contain useful error messages
# NOT: "vendor/autoload.php not found"
```

### ✅ Nginx Error Log
```bash
# In Railway Shell:
tail -20 /var/log/nginx/error.log

# Should be mostly empty, or contain expected errors
# NOT: "connect() failed" to 127.0.0.1:9000
```

---

## Automated Test Suite

### Run All Tests
```bash
#!/bin/bash
echo "Testing School Management App Deployment..."
echo ""

# Test 1: Health Endpoint
echo "Test 1: Health Endpoint"
HEALTH=$(curl -s http://localhost:8080/health)
if [ "$HEALTH" = "ok" ]; then
    echo "✅ PASS"
else
    echo "❌ FAIL: Got '$HEALTH'"
fi

# Test 2: HTTP Status
echo ""
echo "Test 2: HTTP Status"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/)
if [ "$STATUS" = "200" ] || [ "$STATUS" = "302" ]; then
    echo "✅ PASS (HTTP $STATUS)"
else
    echo "❌ FAIL (HTTP $STATUS)"
fi

# Test 3: vendor/autoload.php
echo ""
echo "Test 3: Composer Dependencies"
if [ -f /var/www/app/vendor/autoload.php ]; then
    echo "✅ PASS"
else
    echo "❌ FAIL: vendor/autoload.php not found"
fi

# Test 4: Processes
echo ""
echo "Test 4: Processes Running"
PHP_FPM=$(pgrep -f "php-fpm" | wc -l)
NGINX=$(pgrep -f "nginx" | wc -l)
SUPERVISOR=$(pgrep -f "supervisord" | wc -l)

if [ $SUPERVISOR -gt 0 ] && [ $PHP_FPM -gt 0 ] && [ $NGINX -gt 0 ]; then
    echo "✅ PASS (PHP-FPM: $PHP_FPM, Nginx: $NGINX, Supervisor: $SUPERVISOR)"
else
    echo "❌ FAIL (PHP-FPM: $PHP_FPM, Nginx: $NGINX, Supervisor: $SUPERVISOR)"
fi

# Test 5: Database
echo ""
echo "Test 5: Database"
if [ -f /var/www/app/var/data/school_management_prod.db ]; then
    echo "✅ PASS"
else
    echo "⚠️ WARNING: Database not created yet (may create on first request)"
fi

# Test 6: Cache
echo ""
echo "Test 6: Cache Warmup"
CACHE=$(find /var/www/app/var/cache/prod -type f 2>/dev/null | wc -l)
if [ $CACHE -gt 10 ]; then
    echo "✅ PASS ($CACHE files)"
else
    echo "⚠️ WARNING: Cache not fully warmed ($CACHE files)"
fi

# Tests 7-10: Configuration Valid
echo ""
echo "Test 7: Nginx Configuration"
if nginx -t 2>&1 | grep -q "successful"; then
    echo "✅ PASS"
else
    echo "❌ FAIL: Nginx config invalid"
fi

echo ""
echo "Test 8: Supervisor Status"
if supervisorctl status 2>&1 | grep -q "RUNNING"; then
    echo "✅ PASS"
else
    echo "❌ FAIL: Supervisor not running"
fi

echo ""
echo "Test 9: Environment Variables"
if [ "$APP_ENV" = "prod" ] && [ "$APP_DEBUG" = "0" ]; then
    echo "✅ PASS (APP_ENV=prod, APP_DEBUG=0)"
else
    echo "❌ FAIL (APP_ENV=$APP_ENV, APP_DEBUG=$APP_DEBUG)"
fi

echo ""
echo "Test 10: APP_SECRET"
if [ -n "$APP_SECRET" ] && [ ${#APP_SECRET} -eq 64 ]; then
    echo "✅ PASS (64 characters)"
else
    echo "❌ FAIL (Length: ${#APP_SECRET})"
fi

echo ""
echo "=== Test Complete ==="
```

Save as `test-deployment.sh` and run:
```bash
bash test-deployment.sh
```

---

## Common Test Results

### ✅ SUCCESS
```
Test 1: Health Endpoint
✅ PASS

Test 2: HTTP Status
✅ PASS (HTTP 200)

Test 3: Composer Dependencies
✅ PASS

Test 4: Processes Running
✅ PASS (PHP-FPM: 4, Nginx: 1, Supervisor: 1)

Test 5: Database
✅ PASS

Test 6: Cache Warmup
✅ PASS (156 files)

Test 7: Nginx Configuration
✅ PASS

Test 8: Supervisor Status
✅ PASS

Test 9: Environment Variables
✅ PASS (APP_ENV=prod, APP_DEBUG=0)

Test 10: APP_SECRET
✅ PASS (64 characters)

=== All Tests Passed ===
```

### ❌ FAILURE Example 1: Missing APP_SECRET
```
Test 9: Environment Variables
❌ FAIL (APP_ENV=prod, APP_DEBUG=0)
```

**Fix:** Set APP_SECRET in Railway Variables

### ❌ FAILURE Example 2: vendor/autoload.php Missing
```
Test 3: Composer Dependencies
❌ FAIL: vendor/autoload.php not found
```

**Fix:** `composer install` didn't run. Check Docker build logs.

### ❌ FAILURE Example 3: PHP-FPM Not Running
```
Test 4: Processes Running
❌ FAIL (PHP-FPM: 0, Nginx: 1, Supervisor: 1)
```

**Fix:** PHP-FPM crashed. Check logs with: `supervisorctl tail php-fpm stderr`

---

## Real-World Testing

### Before Deploying
1. Build locally: `docker build -t app:test .`
2. Run locally: `docker run -it ... app:test`
3. Run test suite: `bash test-deployment.sh`
4. Manual browser test: Open `http://localhost:8080/`
5. View logs: `docker logs <container_id>`

### After Deploying
1. Wait for deployment to complete (green checkmark)
2. Run health test: `curl https://your-app.railway.app/health`
3. Check logs: Railway Dashboard → Logs tab
4. Run validation: `bash docker/validate-railway.sh`
5. Manual browser test: Visit your app

### If Something's Wrong
1. Check Railway Logs for [FATAL] messages
2. Run diagnostic: `bash docker/validate-railway.sh`
3. Check specific logs: `tail /var/log/php-error.log`
4. Review [RAILWAY_TROUBLESHOOTING.md](RAILWAY_TROUBLESHOOTING.md)
5. Rollback if necessary

---

## Continuous Monitoring

### Set Up Alerts
Monitor these health checks:

```bash
# Cron: Run every 5 minutes
curl -s https://your-app.railway.app/health | grep -q "ok" || \
  echo "ALERT: Health check failed" | mail -s "App Down" admin@example.com
```

### Monitor Error Rate
```bash
# Check if error rate is increasing
tail -100 /var/log/nginx/error.log | grep "error" | wc -l
```

### Monitor Database
```bash
# Check database isn't growing too large
du -h /var/www/app/var/data/school_management_prod.db
```

### Monitor Resources
```bash
# Check memory/disk aren't full
free -h
df -h
```

---

## Success Metrics

After deployment is complete, you should see:

- ✅ 0 HTTP 500 errors
- ✅ Full error visibility in Railway logs
- ✅ All processes running (php-fpm, nginx, supervisor)
- ✅ Database working
- ✅ Cache warmed
- ✅ Static files loading
- ✅ Features working normally
- ✅ No permission errors
- ✅ No "vendor/autoload.php" errors
- ✅ Clean startup sequence in logs

If you have all these, **deployment is successful! 🎉**
