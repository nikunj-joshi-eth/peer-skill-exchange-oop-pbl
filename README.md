# Peer-to-Peer Skill Exchange and Tutoring Platform

C++ OOP Problem-Based Learning Project

SDG 4: Quality Education

## Team
- Nikunj Joshi - Student Profiles
- Khushi - Skill Listings
- Mansi - Tutoring Sessions
- Sumit Kumar - Feedback and Progress

## Planned Technologies
- C++
- SQLite
- PHP
- HTML, CSS and JavaScript

## Status
Development in progress.

## Shared database and Windows build
Install MinGW GCC/G++ (C++11) and PHP with PDO SQLite (XAMPP includes it).
From the repository root in PowerShell:
```powershell
./scripts/build.ps1 -FetchSQLite
C:/xampp/php/php.exe scripts/init_db.php
./build/skill_exchange.exe
```
The build downloads SQLite 3.51.3 C source from sqlite.org into ignored `dependencies/`.
Later builds can omit `-FetchSQLite`. The console also initializes the schema on startup;
run it from the repository root. An optional first argument selects a test database.
Both interfaces default to `database/skill_exchange.db`, outside the public dashboard.
Initialization is additive and retains IDs and records. Back up existing databases before schema changes.
The original baseline had no on-disk data to migrate. Completed sessions supply learning hours;
feedback no longer controls progress. Pending sessions can be accepted/rejected; accepted sessions
can be completed. Referenced records cannot be deleted until their dependents are removed.
