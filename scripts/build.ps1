param([switch]$FetchSQLite)
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot/..
$source = 'dependencies/sqlite-amalgamation-3510300'
if (!(Test-Path "$source/sqlite3.c")) {
    if (!$FetchSQLite) { throw 'SQLite source missing. Run ./scripts/build.ps1 -FetchSQLite' }
    New-Item -ItemType Directory -Force dependencies | Out-Null
    Invoke-WebRequest 'https://www.sqlite.org/2026/sqlite-amalgamation-3510300.zip' -OutFile dependencies/sqlite.zip
    Expand-Archive dependencies/sqlite.zip -DestinationPath dependencies -Force
}
New-Item -ItemType Directory -Force build | Out-Null
gcc -O2 -DSQLITE_THREADSAFE=0 -c "$source/sqlite3.c" -o build/sqlite3.o
if ($LASTEXITCODE -ne 0) { throw 'SQLite compilation failed' }
g++ -std=c++11 -Wall -Wextra -pedantic -I $source cpp/main.cpp build/sqlite3.o -o build/skill_exchange.exe
if ($LASTEXITCODE -ne 0) { throw 'C++ compilation failed' }
