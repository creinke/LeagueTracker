# Parameters for easy reuse
$SourceDir = "C:\wamp64\www\leaguetracker7"
$TargetDir = "C:\wamp64\www\staging"

Write-Host "--- Starting Staging Deployment ---" -ForegroundColor Cyan

# 1. Verify source exists
if (-not (Test-Path $SourceDir)) {
    Write-Error "Source directory not found: $SourceDir"
    return
}

# 2. Refresh Staging Directory
if (Test-Path $TargetDir) {
    Write-Host "Cleaning target directory: $TargetDir" -ForegroundColor Yellow
    # We remove contents instead of the root folder to avoid file lock issues with WAMP
    try {
        Get-ChildItem -Path $TargetDir | Remove-Item -Recurse -Force -ErrorAction Stop
    } catch {
        Write-Warning "Could not remove some files in staging. They might be locked by Apache/WAMP."
    }
} else {
    Write-Host "Creating target directory: $TargetDir" -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $TargetDir | Out-Null
}

# 3. Define Includes
$Includes = @(
    "assets", "bin", "config", "data", "migrations", "public", 
    "src", "templates", "translations", "composer.json",
    "composer.lock", "symfony.lock", "importmap.php", ".env",
    ".env.prod", "deploy-leaguetracker.sh"
)

# 4. Copy Essential Files
Write-Host "Copying files to staging..." -ForegroundColor Yellow
foreach ($Item in $Includes) {
    $SourcePath = Join-Path $SourceDir $Item
    if (Test-Path $SourcePath) {
        Copy-Item -Path $SourcePath -Destination $TargetDir -Recurse -Force
    }
}

# 5. Rename .env.prod to .env.local to simulate production environment
$envProdPath = Join-Path $TargetDir ".env.prod"
$envLocalPath = Join-Path $TargetDir ".env.local"

if (Test-Path $envProdPath) {
    Copy-Item $envProdPath $envLocalPath -Force
    Write-Host ".env.prod copied to .env.local for staging"
} else {
    Write-Host "WARNING: .env.prod not found in staging directory"
}

# 6. Cleanup Clutter from Staging (Fine-grained exclusions)
Write-Host "Cleaning up development clutter from staging..." -ForegroundColor Yellow
$Exclusions = @(
    ".git", ".idea", ".gitignore", "tests", "phpunit.xml.dist",
    "var/cache/*", "var/log/*"
)

foreach ($Excl in $Exclusions) {
    $ExclPath = Join-Path $TargetDir $Excl
    if (Test-Path $ExclPath) {
        Remove-Item -Path $ExclPath -Recurse -Force -ErrorAction SilentlyContinue
    }
}

# Ensure var subdirectories exist (Symfony requires them)
$VarDirs = @("cache", "log")
foreach ($Dir in $VarDirs) {
    $Path = Join-Path $TargetDir "var/$Dir"
    if (-not (Test-Path $Path)) {
        New-Item -ItemType Directory -Path $Path | Out-Null
    }
}

# 7. Run Composer in Staging
Write-Host "Installing dependencies in staging..." -ForegroundColor Yellow
$OriginalDir = Get-Location
Set-Location $TargetDir

# Using composer.phar if it exists in source, or just 'composer' if global
$ComposerCommand = "composer"
if (Test-Path "$SourceDir\composer.phar") {
    $ComposerCommand = "php `"$SourceDir\composer.phar`""
}

try {
    Invoke-Expression "$ComposerCommand install --no-dev --optimize-autoloader"
} catch {
    Write-Error "Composer installation failed."
}

Write-Host "`n--- Staging deployment complete! ---" -ForegroundColor Green
Write-Host "Target: $TargetDir"
Set-Location $OriginalDir
