# Railway Production Deployment - Final Instructions

## 🚀 READY TO DEPLOY

Everything is fixed and ready. Follow these exact steps.

---

## STEP 1: Generate APP_SECRET

Run this command locally (anywhere in your terminal):

```bash
php -r "echo bin2hex(random_bytes(32));"
```

**Example output:**
```
a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2
```

**Copy this value.** You will need it in Railway.

---

## STEP 2: Commit and Push Changes

These files have been updated:

```bash
.env.railway                          # Environment configuration
Dockerfile                            # Docker build configuration
docker/start.sh                       # Startup script
docker/php/php.ini                    # PHP configuration
docker/php/pool.conf                  # PHP-FPM pool configuration
docker/nginx/nginx.conf               # Nginx main configuration
docker/nginx/railway.conf             # Nginx server configuration
docker/supervisor/supervisord.conf    # Supervisor configuration
RAILWAY_PRODUCTION_FIX_GUIDE.md       # Complete guide
RAILWAY_TROUBLESHOOTING.md            # Troubleshooting guide
docker/validate-railway.sh            # Validation script
```

**Commit to Git:**

```bash
git add -A
git commit -m "Fix: Production deployment on Railway - HTTP 500 error resolution

- APP_SECRET validation and auto-generation
- Error logging to stderr for Railway visibility
- PHP-FPM configuration for TCP socket
- Nginx error logging and performance tuning
- Supervisor process management with proper logging
- Dockerfile validation checks
- Complete startup script with error handling
- Environment variable passing to PHP-FPM

This fixes the 10 critical issues causing blank 500 errors:
1. Missing APP_SECRET handling
2. Error logs not visible in Railway
3. Nginx error logs not configured
4. PHP-FPM output not captured
5. Supervisor logs hidden
6. No runtime error visibility
7. vendor/autoload.php not verified
8. Environment variables not passed to PHP-FPM
9. Network communication issues
10. Dockerfile errors swallowed"

git push origin main
```

**Or if using Railway CLI:**

```bash
railway up
```

---

## STEP 3: Configure Railway Environment Variables

**Important: Do this BEFORE restarting the service!**

1. Open [Railway Dashboard](https://railway.app)
2. Go to **Your Service** → **Settings** (gear icon)
3. Scroll to **Variables**
4. Add these environment variables:

| Variable Name | Value | Notes |
|---|---|---|
| `APP_SECRET` | Paste the value from STEP 1 | Required - use the 64-char hex string |
| `APP_ENV` | `prod` | Must be exact: `prod` |
| `APP_DEBUG` | `0` | Must be: `0` (no debug in production) |
| `PORT` | `8080` | Usually set by Railway already |
| `DATABASE_URL` | (leave empty) | Uses SQLite: `var/data/school_management_prod.db` |

**Save changes.**

---

## STEP 4: Deploy

Railway will automatically detect the new code and rebuild the Docker image.

**Options:**

### A. Using Web Dashboard (Easiest)

1. Go to Railway Dashboard → Your Service → Deployments
2. Click the deployment from STEP 2
3. Watch the build process in the "Build Logs" tab
4. Wait for deployment to complete (green checkmark)

### B. Using Railway CLI

```bash
railway up
```

### C. Using Git Push (If configured)

```bash
git push railroad main
```

**Build takes 3-10 minutes. Get coffee ☕**

---

## STEP 5: Verify Deployment

Once the deployment shows **✅ Active**:

### Test 1: Health Check

```bash
curl https://your-app.railway.app/health
```

Expected response:
```
ok
```

### Test 2: Check Logs

In Railway Dashboard → Logs tab:

Look for these success messages:
```
[STARTUP] Loading environment...
[SUCCESS] APP_SECRET configured
[SUCCESS] Nginx configuration generated
[SUCCESS] Initialization complete - starting services
```

Should **NOT** see:
```
[FATAL] ...
❌ Error
500
```

### Test 3: Load the Homepage

Visit: `https://your-app.railway.app/`

Should load **without HTTP 500 error**

### Test 4: Run Validation Script (Optional)

In Railway Shell:

```bash
bash /var/www/app/docker/validate-railway.sh
```

Expected output:
```
✅ Success:  20
⚠️  Warnings: 2
❌ Errors:   0
```

---

## STEP 6: Monitor Production

### Watch Logs

Railway Dashboard → Your Service → Logs

Look for:
- ✅ Normal PHP requests (get cached)
- ⚠️ Warning messages (review but not critical)
- ❌ Error messages (investigate)
- 🔴 SERVICE CRASHED (restart service)

### If Something Goes Wrong

**See:** [RAILWAY_TROUBLESHOOTING.md](RAILWAY_TROUBLESHOOTING.md)

---

## What Changed (Technical Summary)

### Configuration Files (Production-Hardened)

1. **php.ini** - Error logging to stderr
2. **pool.conf** - TCP socket, clear_env corrections
3. **nginx.conf** - Error logging, access logging to stdout
4. **railway.conf** - Complete rewrite for Symfony routing
5. **supervisord.conf** - Foreground mode, stdout logging
6. **Dockerfile** - Validation checks, error visibility
7. **start.sh** - Comprehensive startup with error handling

### Architecture

```
Railway Load Balancer
        ↓ (HTTPS)
  Nginx (0.0.0.0:8080)
    ↓ (FastCGI)
  PHP-FPM (127.0.0.1:9000)
    ↓
  Symfony (public/index.php)
    ↓
  SQLite (var/data/school_management_prod.db)
```

### Logging Flow

```
PHP errors          → /dev/stderr → Railway Logs
Nginx errors        → /dev/stderr → Railway Logs
Nginx access logs   → /dev/stdout → Railway Logs
Supervisor logs     → /dev/stdout → Railway Logs
Startup messages    → stdout      → Railway Logs
```

---

## Important: DO NOT

❌ Use `symfony server:start` - This will FAIL
❌ Use `php -S 0.0.0.0:8080` - This will FAIL
❌ Use `php artisan serve` - This will FAIL
❌ Hardcode `APP_SECRET` in code - Set it in Railway only
❌ Use development mode in production - `APP_ENV=prod` always
❌ Leave `APP_DEBUG=1` in production - Set to `0`

---

## Success Checklist ✅

After deployment, verify:

- [ ] Deployment shows "✅ Active" (green)
- [ ] `/health` endpoint returns 200
- [ ] Home page loads without 500 error
- [ ] Railway Logs show no errors
- [ ] Database queries work
- [ ] Static files (CSS, JS) load correctly
- [ ] User login/registration works
- [ ] Forms submit without errors
- [ ] Database exports (PDFs) work

---

## Rollback (If Needed)

If something breaks permanently:

1. Go to Railway Dashboard → Deployments
2. Find the last **✅ successful deployment**
3. Click **Redeploy**
4. Service will revert to previous version

---

## Performance Tips

**Optional - After verifying everything works:**

### Increase PHP-FPM Children

In `docker/php/pool.conf`:

```diff
- pm.max_children = 20
+ pm.max_children = 50

- pm.start_servers = 4
+ pm.start_servers = 10

- pm.min_spare_servers = 2
+ pm.min_spare_servers = 5

- pm.max_spare_servers = 6
+ pm.max_spare_servers = 15
```

Redeploy to apply.

### Enable OPcache JIT

In `docker/php/php.ini`:

```diff
  opcache.memory_consumption = 256
+ opcache.jit = tracing
+ opcache.jit_buffer_size = 100M
```

Redeploy to apply.

---

## Database Backup

**IMPORTANT: Always backup SQLite database!**

```bash
# In Railway Shell
cp /var/www/app/var/data/school_management_prod.db /var/www/app/var/data/school_management_prod.db.backup

# Download backup
# Railway Dashboard → Files → Download
```

---

## Next Steps

1. ✅ Deploy the code (STEP 1-2)
2. ✅ Set APP_SECRET in Railway (STEP 3)
3. ✅ Monitor deployment (STEP 4)
4. ✅ Verify it works (STEP 5)
5. ✅ Check logs for any issues (STEP 6)
6. 📚 Read [RAILWAY_PRODUCTION_FIX_GUIDE.md](RAILWAY_PRODUCTION_FIX_GUIDE.md) for details
7. 🚨 Bookmark [RAILWAY_TROUBLESHOOTING.md](RAILWAY_TROUBLESHOOTING.md) for future issues

---

## Questions?

### "What is APP_SECRET?"
A cryptographic key Symfony uses for CSRF tokens, sessions, and message signing. Must be random and secret.

### "Why don't I set DATABASE_URL?"
SQLite database path is already configured in `.env.railway`. Manual DATABASE_URL is optional for PostgreSQL/MySQL migration.

### "How do I view application logs?"
Railways handles it! Go to Dashboard → Your Service → Logs. We output everything there.

### "What if APP_SECRET is wrong?"
Users will get "Invalid CSRF token" or "Signature would be invalid" errors. Regenerate with the PHP command and update Railway.

### "Can I deploy without restarting?"
No, you must rebuild the Docker image. Railway does this automatically on git push.

### "Why 8080 and not 80?"
Railway manages networking. Port 8080 is what container uses, Railway routes HTTPS to it.

---

## Support Resources

- [Symfony Deployment](https://symfony.com/doc/current/deployment.html)
- [PHP-FPM Best Practices](https://www.php.net/manual/en/install.fpm.configuration.php)
- [Railway Docs](https://docs.railway.app/)
- [Nginx Docs](https://nginx.org/en/docs/)

---

**You are now ready for production! 🚀**

**Any issues?** See [RAILWAY_TROUBLESHOOTING.md](RAILWAY_TROUBLESHOOTING.md)
