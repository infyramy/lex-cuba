#!/bin/bash
# LexSZA — Start development servers
# Usage: ./start-dev.sh

PHP=/opt/homebrew/bin/php

echo "🚀 Starting LexSZA development servers..."

# Kill any existing processes on our ports
lsof -ti:8000 | xargs kill -9 2>/dev/null
lsof -ti:5180 | xargs kill -9 2>/dev/null

# Start Laravel API server
$PHP artisan serve --host=127.0.0.1 --port=8000 &
LARAVEL_PID=$!
echo "✅ Laravel API: http://localhost:8000 (PID $LARAVEL_PID)"

# Start Vite dev server
npm --prefix client run dev &
VITE_PID=$!
echo "✅ Vue Frontend: http://localhost:5180 (PID $VITE_PID)"

echo ""
echo "📋 Admin login: admin@example.com / admin12345"
echo "🔗 Open: http://localhost:5180/admin"
echo ""
echo "Press Ctrl+C to stop all servers"

# Wait and forward kill signal to children
trap "kill $LARAVEL_PID $VITE_PID 2>/dev/null; exit" INT TERM
wait
