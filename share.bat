@echo off
echo 🚀 Starting IEEE MIU Local Server...
start /B php -S localhost:8080
timeout /t 2 >nul

echo ✅ Server running locally at http://localhost:8080
:loop
echo 🌐 Creating/Restoring temporary public link...
echo ---------------------------------------------------
echo Press Ctrl+C to stop sharing.
echo ---------------------------------------------------

:: Create tunnel using localhost.run
ssh -tt -o "StrictHostKeyChecking=no" -o "ServerAliveInterval=60" -R 80:localhost:8080 nokey@localhost.run

echo ⚠️ Tunnel disconnected. Reconnecting in 5 seconds...
timeout /t 5 >nul
goto loop

:: Cleanup (this part is hard to reach in a loop, but kept for reference)
taskkill /F /IM php.exe >nul 2>&1
echo 🛑 Server stopped.
pause
