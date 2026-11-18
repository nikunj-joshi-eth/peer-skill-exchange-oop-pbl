param(
    [string]$Php = 'C:\xampp\php\php.exe',
    [int]$Port = 8080,
    [string]$DatabasePath = ''
)

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$console = Join-Path $root 'build\skill_exchange.exe'
$dashboard = Join-Path $root 'dashboard'
$schema = Join-Path $root 'database\schema.sql'

if (-not (Test-Path -LiteralPath $Php -PathType Leaf)) {
    $phpCommand = Get-Command $Php -ErrorAction SilentlyContinue
    if ($phpCommand) { $Php = $phpCommand.Source }
    else { throw "PHP not found: $Php. Pass -Php with the PHP executable path." }
}
if (-not (Test-Path -LiteralPath $console -PathType Leaf)) {
    throw "C++ program not built: $console. Run scripts/build.ps1 -FetchSQLite first."
}
if (-not (Test-Path -LiteralPath $schema -PathType Leaf)) { throw 'Database schema is missing.' }
if ($Port -lt 1024 -or $Port -gt 65535) { throw 'Choose a port between 1024 and 65535.' }

if ([string]::IsNullOrWhiteSpace($DatabasePath)) {
    $DatabasePath = Join-Path $root 'database\skill_exchange.db'
} elseif (-not [System.IO.Path]::IsPathRooted($DatabasePath)) {
    $DatabasePath = Join-Path $root $DatabasePath
}
$DatabasePath = [System.IO.Path]::GetFullPath($DatabasePath)
$parent = Split-Path -Parent $DatabasePath
if (-not (Test-Path -LiteralPath $parent -PathType Container)) {
    New-Item -ItemType Directory -Force -Path $parent | Out-Null
}
if (Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue) {
    throw "Port $Port is already listening. Close the other server or pass -Port 8081."
}

# Child PHP server and this PowerShell console inherit the exact same path.
$env:SKILL_EXCHANGE_DB = $DatabasePath
Push-Location $root
$server = $null
try {
    Write-Host "Shared database: $DatabasePath"
    & $Php (Join-Path $root 'scripts\init_db.php') $DatabasePath
    if ($LASTEXITCODE -ne 0) { throw 'SQLite initialization failed.' }

    $server = Start-Process -FilePath $Php -ArgumentList @('-S',"127.0.0.1:$Port",'-t','dashboard') -WorkingDirectory $root -PassThru
    Start-Sleep -Milliseconds 500
    if ($server.HasExited) { throw 'PHP web server exited. Check PHP extensions and port usage.' }

    Write-Host "Web dashboard: http://127.0.0.1:$Port"
    Write-Host 'Starting the C++ console. Choose menu 0 to exit.'
    & $console $DatabasePath
    if ($LASTEXITCODE -ne 0) { throw "C++ console exited with status $LASTEXITCODE." }
} finally {
    if ($null -ne $server -and -not $server.HasExited) {
        Stop-Process -Id $server.Id -ErrorAction SilentlyContinue
    }
    Pop-Location
}
