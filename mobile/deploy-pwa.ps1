# 1. Build the app
Write-Host "Building Expo web app..."
npx expo export

# 2. Copy to Symfony public directory
Write-Host "Copying to public/pwa..."
if (Test-Path "../public/pwa") { Remove-Item -Recurse -Force "../public/pwa" }
Copy-Item -Recurse dist "../public/pwa"

# 3. Patch index.html
Write-Host "Patching index.html..."
$indexFile = "../public/pwa/index.html"
$content = Get-Content $indexFile
# Patch paths
$content = $content -replace '="/_expo/', '="/pwa/_expo/' -replace '="/favicon.ico"', '="/pwa/favicon.ico"'
# Add global styles to prevent horizontal overflow and fix PWA display
$headEnd = "</head>"
$extraStyles = @"
    <style>
      /* Prevent horizontal scrolling and force content into viewport */
      html, body {
        overflow-x: hidden !important;
        position: relative;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0;
        padding: 0;
        -webkit-overflow-scrolling: touch;
      }
      #root {
        width: 100% !important;
        max-width: 99.9% !important;
        overflow-x: hidden !important;
        margin: 0;
        padding: 0;
      }
    </style>
"@
$content = $content -replace [regex]::Escape($headEnd), ($extraStyles + "`n" + $headEnd)
$content | Set-Content $indexFile

# 4. Patch paths in all JS bundles
Write-Host "Patching JS bundles..."
Get-ChildItem -Path "../public/pwa/_expo/static/js/web/*.js" | ForEach-Object {
    (Get-Content $_.FullName) -replace '"/_expo/', '"/pwa/_expo/' -replace '"/assets/', '"/pwa/assets/' | Set-Content $_.FullName
}

Write-Host "Deployment to public/pwa complete."
