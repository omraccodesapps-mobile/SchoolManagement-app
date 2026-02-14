@echo off
REM Railway Deployment Script - HTTP 500 Error Fix (Windows PowerShell)
REM This script rebuilds and redeploys the application with the 500 error fixes

setlocal enabledelayedexpansion

echo.
echo ╔════════════════════════════════════════════════════╗
echo ║  🚀 School Management App - Railway RedDeploy     ║
echo ╚════════════════════════════════════════════════════╝
echo.

REM Check if railway CLI is installed
where railway >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ Railway CLI not found.
    echo.
    echo Install from: https://railway.app/cli
    echo Then run: railway link
    pause
    exit /b 1
)

echo 📋 Getting current Railway project...
railway status

echo.
echo 🔨 Building and deploying new image...
echo This may take 5-10 minutes...
echo.

REM Deploy with rebuild
railway up --build

if %errorlevel% neq 0 (
    echo ❌ Deployment failed!
    railway logs -f --limit 20
    pause
    exit /b 1
)

echo.
echo ✅ Deployment initiated!
echo.
echo 📊 Checking deployment status...
railway logs -f --limit 50

echo.
echo ╔════════════════════════════════════════════════════╗
echo ║  ✅ Deployment Complete                           ║
echo ║  Testing endpoint...                             ║
echo ╚════════════════════════════════════════════════════╝
echo.

REM Get deployment URL from environment
for /f "tokens=*" %%i in ('railway env --output bash 2^>nul ^| findstr /r "http"') do set DEPLOYMENT_URL=%%i

if defined DEPLOYMENT_URL (
    echo 🌐 Testing: !DEPLOYMENT_URL!
    timeout /t 2 /nobreak
    
    curl -s -f !DEPLOYMENT_URL! >nul 2>&1
    if !errorlevel! equ 0 (
        echo ✅ Application is responding!
        echo    Status: HTTP 200
        echo    URL: !DEPLOYMENT_URL!
    ) else (
        echo ⚠️  Application not responding yet. Waiting 30 seconds...
        timeout /t 30 /nobreak
        
        curl -s -f !DEPLOYMENT_URL! >nul 2>&1
        if !errorlevel! equ 0 (
            echo ✅ Application is now responding!
        ) else (
            echo ❌ Application still not responding. Check logs:
            echo    railway logs -f
        )
    )
) else (
    echo ℹ️  Deployment URL: Check Railway dashboard
)

echo.
echo 📖 Documentation:
echo    - HTTP_500_ERROR_FIX.md (detailed explanation)
echo    - DEPLOY_FIX_TODAY.md (quick reference)
echo.
echo 🔍 To view logs:
echo    railway logs -f
echo.
echo 🔄 To rollback (if needed):
echo    railway rollback [DEPLOYMENT_ID]
echo.

pause
