# Railway Deployment Workflow - Quick Reference

## 🚀 Deploy & Test

### Step 1: Commit Changes
```powershell
git add -A
git commit -m "Fix: Railway nginx permission errors and improve healthcheck"
git push
```

### Step 2: Monitor Railway Build
1. Open [Railway Dashboard](https://railway.app)
2. Go to your **School Management App** service
3. Click **Deployments** tab
4. Watch the build logs in real-time

### Step 3: Expected Success Logs
```
[STARTUP] Configured /var/run directories for PID files
[SUCCESS] Nginx configuration valid
[SUCCESS] vendor/autoload.php found
[SUCCESS] Permissions configured
✅ Initialization complete - starting services
[14-Feb-2026 23:00:05] NOTICE: fpm is running, pid 17
[14-Feb-2026 23:00:05] NOTICE: ready to handle connections
```

### Step 4: Test the App
```powershell
# Get railway URL (check dashboard)
curl https://your-railway-url/health
# Should return: ok

# Test full app
curl https://your-railway-url/
```

## 📋 What Was Fixed

| Issue | Fix | File |
|-------|-----|------|
| PID permission denied | `/tmp` → `/var/run` | `docker/nginx/nginx.conf` |
| Supervisor socket error | `/tmp` → `/var/run` | `docker/supervisor/supervisord.conf` |
| Slow startup to health | 30s → 60s start-period | `Dockerfile` |
| Missing /var/run setup | Added directory creation | `docker/start.sh` |

## 🔍 Monitoring

### Check Logs on Railway
```
Dashboard → Service → Logs → Recent Deployments (live view)
```

### Common Success Indicators
- ✅ No "Permission denied" errors
- ✅ Healthcheck changes from "unhealthy" to "healthy"
- ✅ Service status shows "running" (green)

### If Issues Occur
1. **Click "Redeploy"** on the latest commit
2. **Check build logs** for specific errors
3. **Verify variables** in service settings (APP_SECRET, DATABASE_URL, etc.)

## 🎯 Key Points

- **No manual Railway config needed** - all fixes are in Docker
- **PID files** now use `/var/run` (Railway-standard)
- **Permissions** are set to 777 (world-writable)
- **Startup delay** increased from 30s → 60s for stability
- **Healthcheck** simplified to use curl `-sf` flags

## 📦 All Files Updated

These 4 files contain the complete fix:
1. `docker/nginx/nginx.conf` - Nginx PID path
2. `docker/supervisor/supervisord.conf` - Supervisor PID and socket paths
3. `docker/start.sh` - Runtime /var/run setup
4. `Dockerfile` - Build-time /var/run setup

Ready to push! 🚀
