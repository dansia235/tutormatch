@echo off
echo 🚀 Demarrage Redis pour TutorMatch...

REM Verifier si Redis est deja installe
where redis-server >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo ✅ Redis trouve, demarrage...
    redis-server
) else (
    echo ❌ Redis non trouve!
    echo.
    echo 💡 Options d'installation:
    echo.
    echo 1. Docker ^(Recommande^):
    echo    docker run -d -p 6379:6379 --name tutormatch-redis redis:latest
    echo.
    echo 2. Windows Binary:
    echo    Telecharger: https://github.com/microsoftarchive/redis/releases
    echo.
    echo 3. Chocolatey:
    echo    choco install redis-64
    echo.
    echo 4. WSL:
    echo    wsl sudo apt install redis-server
    echo    wsl sudo service redis-server start
    echo.
    pause
)