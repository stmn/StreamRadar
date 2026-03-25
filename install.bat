@echo off
title StreamRadar Installer
echo.
echo   StreamRadar Installer
echo   =========================
echo.

:: Check Docker
docker compose version >nul 2>&1
if %errorlevel% neq 0 (
    echo   X Docker is not installed.
    echo.
    echo   Please install Docker Desktop first:
    echo     https://www.docker.com/products/docker-desktop/
    echo.
    pause
    exit /b 1
)
echo   * Docker found

:: Install directory
if "%STREAMRADAR_DIR%"=="" (
    set "INSTALL_DIR=%USERPROFILE%\streamradar"
) else (
    set "INSTALL_DIR=%STREAMRADAR_DIR%"
)

:: Download
if exist "%INSTALL_DIR%\" (
    echo   ^> Directory %INSTALL_DIR% already exists, updating...
    cd /d "%INSTALL_DIR%"
    if exist ".git" git pull --quiet
) else (
    echo   ^> Downloading to %INSTALL_DIR%...
    git clone --quiet https://github.com/your-user/streamradar.git "%INSTALL_DIR%" 2>nul
    if %errorlevel% neq 0 (
        echo   ^> git not found, downloading ZIP...
        curl -fsSL https://github.com/your-user/streamradar/archive/refs/heads/main.zip -o "%TEMP%\streamradar.zip"
        powershell -Command "Expand-Archive -Path '%TEMP%\streamradar.zip' -DestinationPath '%TEMP%' -Force"
        move "%TEMP%\streamradar-main" "%INSTALL_DIR%" >nul
        del "%TEMP%\streamradar.zip"
    )
    cd /d "%INSTALL_DIR%"
)

:: Port
if "%APP_PORT%"=="" set "APP_PORT=8080"

:: Start
echo   ^> Starting StreamRadar on port %APP_PORT%...
set APP_PORT=%APP_PORT%
docker compose up -d --build

echo.
echo   StreamRadar is running!
echo.
echo   Open in your browser:
echo     http://localhost:%APP_PORT%
echo.
echo   Next steps:
echo     1. Go to Settings
echo     2. Enter your Twitch API keys
echo     3. Start tracking categories and channels
echo.
pause
