Write-Host ""
Write-Host "  StreamRadar Installer" -ForegroundColor Cyan
Write-Host "  ========================="
Write-Host ""

# Check Docker
try {
    docker compose version | Out-Null
    Write-Host "  * Docker found" -ForegroundColor Green
} catch {
    Write-Host "  X Docker is not installed." -ForegroundColor Red
    Write-Host ""
    Write-Host "  Please install Docker Desktop first:"
    Write-Host "    https://www.docker.com/products/docker-desktop/"
    Write-Host ""
    exit 1
}

# Install directory
$InstallDir = if ($env:STREAMRADAR_DIR) { $env:STREAMRADAR_DIR } else { "$HOME\streamradar" }

if (Test-Path $InstallDir) {
    Write-Host "  > Directory $InstallDir already exists, updating..."
    Set-Location $InstallDir
    if (Test-Path ".git") {
        git pull --quiet
    }
} else {
    Write-Host "  > Downloading to $InstallDir..."
    try {
        git clone --quiet https://github.com/your-user/streamradar.git $InstallDir
    } catch {
        Write-Host "  > git not found, downloading ZIP..."
        Invoke-WebRequest -Uri "https://github.com/your-user/streamradar/archive/refs/heads/main.zip" -OutFile "$env:TEMP\streamradar.zip"
        Expand-Archive -Path "$env:TEMP\streamradar.zip" -DestinationPath $env:TEMP -Force
        Move-Item "$env:TEMP\streamradar-main" $InstallDir
        Remove-Item "$env:TEMP\streamradar.zip"
    }
    Set-Location $InstallDir
}

# Start
$Port = if ($env:APP_PORT) { $env:APP_PORT } else { "8080" }

Write-Host "  > Starting StreamRadar on port $Port..."
$env:APP_PORT = $Port
docker compose up -d --build

Write-Host ""
Write-Host "  StreamRadar is running!" -ForegroundColor Green
Write-Host ""
Write-Host "  Open in your browser:"
Write-Host "    > http://localhost:$Port" -ForegroundColor Yellow
Write-Host ""
Write-Host "  Next steps:"
Write-Host "    1. Go to Settings"
Write-Host "    2. Enter your Twitch API keys"
Write-Host "    3. Start tracking categories and channels"
Write-Host ""
Write-Host "  Useful commands:"
Write-Host "    Stop:    cd $InstallDir; docker compose stop"
Write-Host "    Start:   cd $InstallDir; docker compose up -d"
Write-Host "    Update:  cd $InstallDir; git pull; docker compose up -d --build"
Write-Host ""
