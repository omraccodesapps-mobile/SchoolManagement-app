# Railway Permission Fix - Nginx PID Error Resolution

## Problem Fixed
```
[emerg] 18#18: open() "/tmp/nginx.pid" failed (13: Permission denied)
```

Railway's container environment restricts write access to `/tmp`. The fix moves all PID files and sockets from `/tmp` to `/var/run` (the standard Linux location).

## Changes Made

### 1. **docker/nginx/nginx.conf**
```diff
- pid /tmp/nginx.pid;
+ pid /var/run/nginx.pid;
```

### 2. **docker/supervisor/supervisord.conf**
```diff
- pidfile = /tmp/supervisord.pid
+ pidfile = /var/run/supervisord.pid
- file = /tmp/supervisor.sock
+ file = /var/run/supervisor.sock
- serverurl = unix:////tmp/supervisor.sock
+ serverurl = unix:////var/run/supervisor.sock
```

### 3. **docker/start.sh**
Added at startup (lines 16-22):
```bash
# CRITICAL FIX: Create /var/run directories for PID files and sockets
# This prevents "Permission denied" errors on Railway
mkdir -p /var/run/supervisor /var/run/nginx
chmod 777 /var/run/supervisor /var/run/nginx
echo "[STARTUP] Configured /var/run directories for PID files"
```

### 4. **Dockerfile**
Added `/var/run` directory creation:
```diff
RUN mkdir -p var/cache var/log var/data var/sessions public/uploads /var/log/nginx \
+           /var/run/supervisor /var/run/nginx && \
    ...
+   chmod 777 /var/run/supervisor /var/run/nginx
```

### 5. **Healthcheck Improved**
```diff
- HEALTHCHECK --interval=10s --timeout=5s --start-period=30s --retries=3 \
-   CMD curl -f http://localhost:8080/health || exit 1
+ HEALTHCHECK --interval=10s --timeout=5s --start-period=60s --retries=3 \
+   CMD curl -sf http://localhost:8080/health > /dev/null 2>&1 || exit 1
```

## Deploy to Railway

### Option 1: Automatic (Recommended)
```bash
git add docker/nginx/nginx.conf docker/supervisor/supervisord.conf docker/start.sh Dockerfile
git commit -m "Fix: Move PID files from /tmp to /var/run for Railway compatibility"
git push
```
Railway will automatically rebuild and deploy.

### Option 2: Manual Redeploy
1. Go to Railway dashboard
2. Click your service name (School Management App)
3. Click **Deploy** tab
4. Click **Redeploy** on the latest commit
5. Wait for build to complete

## Expected Behavior
After deployment, you should see:
```
[STARTUP] Configured /var/run directories for PID files
[SUCCESS] Nginx configuration valid
[14-Feb-2026 23:00:05] NOTICE: fpm is running, pid 17
[14-Feb-2026 23:00:05] NOTICE: ready to handle connections
```

**NO MORE PERMISSION DENIED ERRORS**

## Healthcheck Verification
The healthcheck endpoint will:
- Start checking after 60 seconds (gives app time to fully boot)
- Check every 10 seconds
- Timeout after 5 seconds per check
- Fail after 3 consecutive fails (then Railway auto-restarts)

Test locally:
```bash
curl http://localhost:8080/health
# Should return: ok
```

## Troubleshooting

### Still seeing permission errors?
1. Force rebuild without cache:
   ```bash
   git push --force-with-lease
   ```
2. Check Railway build logs in real-time on Railway dashboard

### Healthcheck failing?
1. Ensure `/health` endpoint is returning 200:
   ```bash
   curl -v http://localhost:8080/health
   ```
2. Check `/var/log/nginx/error.log` for issues

## Files Modified
- ✅ `docker/nginx/nginx.conf`
- ✅ `docker/supervisor/supervisord.conf`
- ✅ `docker/start.sh`
- ✅ `Dockerfile`

## Verification Checklist
- [ ] Git commit pushed
- [ ] Railway build completed successfully
- [ ] No "Permission denied" errors in logs
- [ ] Healthcheck shows "healthy" in Railway dashboard
- [ ] Application is responsive at port 8080
