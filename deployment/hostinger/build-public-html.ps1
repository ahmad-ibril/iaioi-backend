param(
    [string] $ApiBaseUrl = "https://iaioi.com/api/v1",
    [string] $GoogleClientId = $env:GOOGLE_CLIENT_ID
)

$ErrorActionPreference = "Stop"

$repoRoot = Resolve-Path (Join-Path $PSScriptRoot "..\..")
$mobileRoot = Join-Path $repoRoot "mobile_app"
$buildRoot = Join-Path $mobileRoot "build\web"

Push-Location $mobileRoot
try {
    if ([string]::IsNullOrWhiteSpace($GoogleClientId)) {
        throw "GOOGLE_CLIENT_ID is required for the production web build."
    }

    flutter build web --release --base-href / `
        --dart-define "API_BASE_URL=$ApiBaseUrl" `
        --dart-define "GOOGLE_CLIENT_ID=$GoogleClientId"
} finally {
    Pop-Location
}

$requiredFiles = @(
    (Join-Path $buildRoot "index.html"),
    (Join-Path $buildRoot ".htaccess"),
    (Join-Path $buildRoot "api\index.php"),
    (Join-Path $buildRoot "manifest.json")
)

foreach ($file in $requiredFiles) {
    if (-not (Test-Path -LiteralPath $file)) {
        throw "Missing deployment file: $file"
    }
}

Write-Host "Hostinger public_html package is ready:"
Write-Host $buildRoot
