# Railway Deployment Checklist - Quick Start

## Pre-Deployment (Local)

- [ ] Clone the repo with latest changes
- [ ] Verify all new files exist:
  - [ ] `docker/nginx/nginx.conf` (new)
  - [ ] `docker/nginx/railway.conf` (new)
  - [ ] `docker/php/pool.conf` (new)
  - [ ] `docker/php/php.ini` (new)
  - [ ] `docker/supervisor/supervisord.conf` (new)
  - [ ] `docker/start.sh` (updated)
  - [ ] `Dockerfile` (updated)
  - [ ] `railway.json` (updated)
  - [ ] `build.sh` (updated)

## Generate APP_SECRET

```bash
# Run this locally (or in any terminal with PHP):
php -r 'echo bin2hex(random_bytes(16));'

# Example output: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
# Keep this value for Railway config
```

## Railway Dashboard Configuration

1. **Open Railway Dashboard**:
   - Go to https://railway.app
   - Select project: `SchoolManagement-app`

2. **Add Environment Variables** (Variables tab):
   
   | Variable | Value | Description |
   |----------|-------|-------------|
   | `APP_ENV` | `prod` | Must be prod |
   | `APP_DEBUG` | `0` | Never true in prod |
   | `APP_SECRET` | `<paste generated value>` | The hex string from above |
   | `DATABASE_URL` | `sqlite:///%kernel.project_dir%/var/data/school_management_prod.db` | SQLite (recommend for now) |
   | `DEFAULT_URI` | `https://school-management-production-1378.up.railway.app` | Your production domain |

3. **Save Variables** (Railway auto-saves)

## Deploy

### Option A: Git Push (Recommended)
```bash
git add .
git commit -m "fix(railway): 502 bad gateway - production deployment fixes"
git push
# Railway auto-builds and deploys
```

### Option B: Force Redeploy (if no code changes)
1. Go to Railway Dashboard
2. Click Deployments tab
3. Find the latest deployment
4. Click the 3-dot menu → "Redeploy"

## Monitor Deployment

1. **Watch Build Log**:
   - Click "Build" tab
   - Should see:
     - ✅ Installing PHP dependencies
     - ✅ Warming up Symfony cache
     - ✅ Running migrations
     - ✅ Build complete

2. **Check Deployment Status**:
   - Click "Deploy" tab
   - Green checkmark = container started
   - Containers section should show "Running"

3. **View Runtime Logs**:
   - Click "Logs" tab
   - Should see supervisor starting PHP-FPM and Nginx
   - No errors about socket, permissions, or cache

## Test the Deployment

### Quick Test (HTTP 200)
```bash
curl -i https://school-management-production-1378.up.railway.app/
# Should return HTTP 200, not 502
```

### Health Check
```bash
curl https://school-management-production-1378.up.railway.app/php-fpm-status
# Should show JSON with active processes
```

### Login Test
1. Open https://school-management-production-1378.up.railway.app in browser
2. Navigate to login page
3. Perform a login action
4. Should work without errors

## Troubleshooting (If 502 still appears)

### Step 1: Verify Variables
```
Railway > Variables tab > Check all 5 variables are set
Especially: APP_SECRET should not be empty
```

### Step 2: Check Recent Logs for Errors
```
Rails Logs tab > Type "error" in search > Filter
Look for: nginx error, php-fpm error, permission denied, socket not found
```

### Step 3: Restart Container
```
Railway > Deployments > Latest > Restart
Wait 30 seconds > Refresh browser
```

### Step 4: Force Redeploy
```
Railway > Deployments > Latest > Redeploy without cache
```

### Step 5: Check Supervisor Status
In logs, you should see:
```
php-fpm: spawned "www" (pool worker)
nginx: master process started
```
If missing, supervisor didn't start.

### Step 6: Database Issue?
If logs show database error:
```
Go to Variables tab
Set DATABASE_URL to: sqlite:///%kernel.project_dir%/var/data/school_management_prod.db
Save and redeploy
```

## Success Indicators

✅ **Deploy Page**: Shows "Build Successful" and "Deployed"
✅ **Health Check**: Green in Railway dashboard
✅ **HTTP Test**: `curl` returns 200, not 502
✅ **Logs**: No errors, shows "supervisor started", "nginx: master"
✅ **Browser**: Can navigate and login without 502

## Performance Checks

- [ ] Homepage loads in < 2 seconds
- [ ] Login form loads
- [ ] Dashboard loads after login
- [ ] Static assets (CSS, JS) load
- [ ] No 502 errors during normal use

---

**Estimated time**: 15 minutes for deployment + test

If everything works, you're done! 🎉
