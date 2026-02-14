#!/bin/bash
# Railway Deployment Script - HTTP 500 Error Fix
# This script rebuilds and redeploys the application with the 500 error fixes

set -e

echo "╔════════════════════════════════════════════════════╗"
echo "║  🚀 School Management App - Railway RedDeploy     ║"
echo "╚════════════════════════════════════════════════════╝"
echo ""

# Check if railway CLI is installed
if ! command -v railway &> /dev/null; then
    echo "❌ Railway CLI not found. Install from: https://railway.app/cli"
    echo "   Then run: railway link"
    exit 1
fi

# Get current project
echo "📋 Getting current Railway project..."
RAILWAY_PROJECT=$(railway projects list 2>/dev/null | head -1 || echo "")

if [ -z "$RAILWAY_PROJECT" ]; then
    echo "⚠️  No Railway project linked. Running: railway link"
    railway link
fi

echo ""
echo "📊 Current Status:"
railway status

echo ""
echo "🔨 Building and deploying new image..."
echo "This may take 5-10 minutes..."
echo ""

# Deploy with rebuild
railway up --build

echo ""
echo "✅ Deployment initiated!"
echo ""
echo "📊 Checking deployment status..."
railway logs -f --limit 50

echo ""
echo "╔════════════════════════════════════════════════════╗"
echo "║  ✅ Deployment Complete                           ║"
echo "║  Testing endpoint...                             ║"
echo "╚════════════════════════════════════════════════════╝"
echo ""

# Get the deployed URL
DEPLOYMENT_URL=$(railway env --output bash | grep -o 'https://[^ ]*' | head -1 || echo "")

if [ -z "$DEPLOYMENT_URL" ]; then
    echo "deployment URL: Check Railway dashboard"
else
    echo "🌐 Testing: $DEPLOYMENT_URL"
    sleep 2
    
    if curl -s -f "$DEPLOYMENT_URL" > /dev/null; then
        echo "✅ Application is responding!"
        echo "   Status: HTTP 200"
        echo "   URL: $DEPLOYMENT_URL"
    else
        echo "⚠️  Application not responding yet. Waiting 30 seconds..."
        sleep 30
        if curl -s -f "$DEPLOYMENT_URL" > /dev/null; then
            echo "✅ Application is now responding!"
        else
            echo "❌ Application still not responding. Check logs:"
            echo "   railway logs -f"
        fi
    fi
fi

echo ""
echo "📖 Documentation:"
echo "   - HTTP_500_ERROR_FIX.md (detailed explanation)"
echo "   - DEPLOY_FIX_TODAY.md (quick reference)"
echo ""
echo "🔍 To view logs:"
echo "   railway logs -f"
echo ""
echo "🔄 To rollback (if needed):"
echo "   railway rollback [DEPLOYMENT_ID]"
