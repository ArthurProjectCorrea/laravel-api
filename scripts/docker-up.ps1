param(
    [switch]$Build
)

if ($Build) {
    docker-compose up -d --build
} else {
    docker-compose up -d
}

Write-Host "Waiting for containers to stabilize..."
Start-Sleep -Seconds 5

Write-Host "Running migrations inside app container..."
docker-compose exec app php artisan migrate --force

Write-Host "Laravel app should be available at http://localhost:8000"
