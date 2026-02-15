#!/bin/bash

# Kill any existing php server on port 8080
pkill -f "php -S localhost:8080" 2>/dev/null

echo "🚀 Starting IEEE MIU Local Server..."
php -S localhost:8080 > /dev/null 2>&1 &
PHP_PID=$!

# Wait for server to start
sleep 2

echo "✅ Server running locally at http://localhost:8080"
echo "🌐 Creating temporary public link..."
echo "---------------------------------------------------"
echo "Press Ctrl+C to stop sharing."
echo "---------------------------------------------------"

# Create tunnel using localhost.run (no install required)
# StrictHostKeyChecking=no avoids interactive prompts for new host keys
ssh -o StrictHostKeyChecking=no -R 80:localhost:8080 nokey@localhost.run

# Cleanup when tunnel is closed
kill $PHP_PID
echo "🛑 Server stopped."
