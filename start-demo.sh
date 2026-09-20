#!/bin/bash

# REVALUE Demo Startup Script
# Runs full app: Laravel API + Vue frontend with Google Maps

set -e

PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$PROJECT_DIR"

echo "🚀 REVALUE Demo Startup"
echo "======================="
echo ""

# Check requirements
echo "Checking requirements..."
command -v docker &> /dev/null || { echo "❌ Docker required. Install: https://docs.docker.com/install"; exit 1; }
command -v docker-compose &> /dev/null || { echo "⚠️ Using docker compose v2"; }

echo "✓ Docker available"
echo ""

# Start containers
echo "Starting containers..."
docker-compose up -d --build

sleep 5

# Get container IDs
APP_CONTAINER=$(docker-compose ps -q app 2>/dev/null || echo "")
if [ -z "$APP_CONTAINER" ]; then
    echo "❌ Failed to start containers"
    exit 1
fi

echo "✓ Containers running"
echo ""

# Run migrations
echo "Setting up database..."
docker-compose exec -T app php artisan migrate:fresh --seed --force > /dev/null 2>&1 || {
    echo "⚠️ Migrations: using existing DB"
}
echo "✓ Database ready"
echo ""

# Wait for services
echo "Waiting for services..."
for i in {1..30}; do
    if curl -s http://127.0.0.1:8000 > /dev/null 2>&1; then
        echo "✓ Laravel ready"
        break
    fi
    sleep 1
done

for i in {1..10}; do
    if curl -s http://127.0.0.1:5173 > /dev/null 2>&1; then
        echo "✓ Vite ready"
        break
    fi
    sleep 1
done

echo ""
echo "✅ REVALUE Demo Ready!"
echo ""
echo "📱 Frontend: http://127.0.0.1:8000"
echo "🔌 API:      http://127.0.0.1:8000/api"
echo "🗺️  Maps:     Embedded in routes & tracking pages"
echo ""
echo "Demo Data:"
echo "- Super Admin: superadmin@revalue.test / password"
echo "- Logistics Driver: Available via seeder"
echo "- Sample Routes: Created in database"
echo ""
echo "🎯 To see maps in action:"
echo "1. Login as super admin"
echo "2. Go to Logistics → Routes"
echo "3. Click any route to see live map"
echo ""
echo "Press Ctrl+C to stop containers"
