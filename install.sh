#!/bin/bash
set -e

echo ""
echo "  🐦 StreamRadar Installer"
echo "  ========================="
echo ""

# Check Docker
if ! command -v docker &> /dev/null; then
    echo "  ❌ Docker is not installed."
    echo ""
    echo "  Please install Docker first:"
    echo "    https://www.docker.com/products/docker-desktop/"
    echo ""
    exit 1
fi

if ! docker compose version &> /dev/null 2>&1; then
    echo "  ❌ Docker Compose is not available."
    echo "  Please update Docker Desktop to the latest version."
    exit 1
fi

echo "  ✓ Docker found"

# Choose install directory
INSTALL_DIR="${STREAMRADAR_DIR:-$HOME/streamradar}"

if [ -d "$INSTALL_DIR" ]; then
    echo "  → Directory $INSTALL_DIR already exists, updating..."
    cd "$INSTALL_DIR"
    if [ -d ".git" ]; then
        git pull --quiet
    fi
else
    echo "  → Downloading to $INSTALL_DIR..."
    if command -v git &> /dev/null; then
        git clone --quiet https://github.com/your-user/streamradar.git "$INSTALL_DIR"
    else
        echo "  → git not found, downloading ZIP..."
        curl -fsSL https://github.com/your-user/streamradar/archive/refs/heads/main.zip -o /tmp/streamradar.zip
        unzip -q /tmp/streamradar.zip -d /tmp
        mv /tmp/streamradar-main "$INSTALL_DIR"
        rm /tmp/streamradar.zip
    fi
    cd "$INSTALL_DIR"
fi

# Set port
PORT="${APP_PORT:-8080}"

echo "  → Starting StreamRadar on port $PORT..."
APP_PORT=$PORT docker compose up -d --build --quiet-pull 2>/dev/null || APP_PORT=$PORT docker compose up -d --build

echo ""
echo "  ✅ StreamRadar is running!"
echo ""
echo "  Open in your browser:"
echo "    → http://localhost:$PORT"
echo ""
echo "  Next steps:"
echo "    1. Go to Settings"
echo "    2. Enter your Twitch API keys"
echo "    3. Start tracking categories and channels"
echo ""
echo "  Useful commands:"
echo "    Stop:    cd $INSTALL_DIR && docker compose stop"
echo "    Start:   cd $INSTALL_DIR && docker compose up -d"
echo "    Update:  cd $INSTALL_DIR && git pull && docker compose up -d --build"
echo ""
