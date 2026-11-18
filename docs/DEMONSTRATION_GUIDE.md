# Repeatable Windows viva demonstration

This is a procedure to execute, not a claim that an interactive browser demonstration has passed. Use demonstration-only records; never reset or delete the normal database to prepare a viva. Run from the repository root in both PowerShell windows.

## 1. Build and select new demonstration storage

In window A:

```powershell
Get-Command gcc, g++, python
& 'C:\xampp\php\php.exe' -m
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1 -FetchSQLite
if ($LASTEXITCODE -ne 0) { throw 'Build failed' }
New-Item -ItemType Directory -Force build | Out-Null
$env:SKILL_EXCHANGE_DB = Join-Path (Get-Location) ('build\viva-' + [guid]::NewGuid().ToString('N') + '.db')
& 'C:\xampp\php\php.exe' .\scripts\init_db.php $env:SKILL_EXCHANGE_DB
$env:SKILL_EXCHANGE_DB
.\build\skill_exchange.exe $env:SKILL_EXCHANGE_DB
```

Copy the printed absolute database path into window B; both clients must use the identical file:

```powershell
# Replace this value with the exact absolute path printed in window A.
$env:SKILL_EXCHANGE_DB = 'C:\your-repository\build\viva-printed-id.db'
if (!(Test-Path -LiteralPath $env:SKILL_EXCHANGE_DB)) { throw 'Copy the existing demo path from window A first' }
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8080 -t dashboard
```

Open `http://127.0.0.1:8080`. If port 8080 is occupied, use 8081 in both the server command and browser URL. Do not point the document root at the repository root. The unique file name makes each rehearsal independent without overwriting previous records.

## 2. Complete one cross-interface workflow

| Step | Interface and exact action | Expected evidence |
| --- | --- | --- |
| Empty state | Visit all six dashboard sections before adding data | Real zero counts and links explaining prerequisites |
| Register tutor | Console option 1: ID `701`, name `Demo Tutor`, offered `C++`, needed `Mathematics` | Student registered; refresh Students in browser to see #701 |
| Register learner | Dashboard Students: ID `702`, name `Demo Learner`, offered `Mathematics`, to learn `C++` | Two directory records |
| Prove synchronization | Console option 14 to advance a cycle, then option 2 | Both students visible without closing the console |
| Edit/search | Edit #702 in dashboard to `Demo Learner Updated`; search `702`, then clear. Console option 3, ID `702`, after advancing a cycle | Updated name visible in both clients |
| Offer a skill | Console option 4: listing `801`, owner `701`, skill `C++ Basics`, type `Offer`, description `Classes and constructors` | Browser Skills shows the same listing |
| Learn listing | Dashboard Skills: listing `802`, student `702`, skill `Calculus`, type `Learn`, description `Practice derivatives` | Filter Learn and search Calculus; clear filters afterward |
| Console filter/search | Console option 6, `Offer`; option 7, `Basics` | Offer listing found; Learn listing is not an Offer |
| Schedule | Dashboard Sessions: session `901`, Offer #801, learner #702, date `2026-11-20`, time `14:30`, duration `2.5` | Pending status; console option 9 shows it after refresh |
| Accept | Dashboard Accept for #901 | Accepted status |
| Complete | Advance console cycle if needed, option 10, session `901`, choice `3` | Status updated; browser shows Completed |
| Hours before review | Visit Learning Progress; console option 13, learner `702` | One completed session, 2.5 learning hours, no feedback required |
| Feedback | Dashboard Feedback: session #901, rating `5`, comments `Clear explanation of constructors` | Average 5/5 and tutor review; console option 12 shows the comments |
| Edit review | Dashboard edit review #901, rating `4`, comments `Helpful practical examples` | Average 4/5; hours remain 2.5 |
| Restart | Console option 0; stop PHP with Ctrl+C; restart each with the same database path | Students, session, review and hours persist |

The chosen date is deliberately fixed; past dates are supported. The console learner average describes reviews submitted by that learner, while the dashboard tutor average describes reviews received by the tutor.

## 3. Demonstrate safe rejection

Use records from step 2; retain existing IDs and do not remove them yet.

- Register another student with ID 701: duplicate rejected.
- Request self-tutoring with learner 701 on Offer 801: rejected.
- Console option 8, new session 902, listing 802: Learn booking rejected before further input. Dashboard only offers Offer listings in the booking selector.
- For a new console booking 902/801/702, enter `2026-02-30`, then a real date; enter `25:00`, then `09:15`; enter `0`, then `1.5` hours. Invalid fields retry; corrected booking is Pending.
- Try completing Pending #902 directly: rejected. Reject it instead; attempts to accept it afterward fail.
- Console option 11, session 901: duplicate feedback rejected. Session 902 is ineligible for feedback.
- Try deleting student 701 while its listing is referenced: foreign-key protection rejects deletion.
- In the browser, enter a blank required field, inspect the validation, and confirm that server-rejected values remain available for correction. Test both Cancel and Confirm in a delete dialog using an unreferenced temporary profile.

## 4. Optional demonstration cleanup

Keep the demo file for assessment evidence. If demonstrating deletion, remove only the demo chain in dependency order: feedback #901; sessions #901 and #902; listings #801 and #802; students #701 and #702. The dashboard supports these deletes; console option 17 accepts record type 1 student, 2 listing, 3 session, 4 feedback, followed by its ID. SQL restrictions apply in both interfaces. Deletion is permanent and changes the corresponding summaries.

## 5. Automated verification

After leaving the console and stopping the server, run:

```powershell
& 'C:\xampp\php\php.exe' -l dashboard/bootstrap.php
& 'C:\xampp\php\php.exe' -l dashboard/index.php
& 'C:\xampp\php\php.exe' -l scripts/init_db.php
python tests/integration.py --php 'C:\xampp\php\php.exe'
```

The suite uses its own temporary database and sessions, independently of the demonstration path, and writes actual results into `TEST_CASES.md`. A rerun changes that tracked report; review it before any later commit.

## Screenshot and manual-review checklist

All boxes below are intentionally unchecked. Capture real screenshots only after running the relevant workflow. Save them outside the source tree or in a deliberately reviewed submission evidence folder; never substitute generated UI images.

- [ ] `01-overview-desktop.png`: real counts and completed hours.
- [ ] `02-students.png`: tutor, learner, search and edit controls.
- [ ] `03-skills.png`: Offer and Learn listings with filtering.
- [ ] `04-sessions.png`: session participants, date, duration and lifecycle controls.
- [ ] `05-feedback.png`: actual review, average and tutor rating.
- [ ] `06-progress.png`: 2.5 completed hours and one completed session.
- [ ] `07-console.png`: four-module menu and shared-data output.
- [ ] `08-mobile.png`: approximately 360 px wide; readable forms and scrollable tables without whole-page overflow.
- [ ] `09-validation.png`: a genuine rejection with preserved form values.
- [ ] Desktop and mobile checks across all six sections, including 200% zoom and long text.
- [ ] Tab/Shift+Tab navigation, visible focus, skip link, table scrolling and no keyboard trap.
- [ ] Screen-reader labels, table headers, selected navigation and error announcements.
- [ ] Delete dialog Cancel leaves data unchanged; Confirm removes only the selected unreferenced record.
- [ ] Restart persistence demonstrated on the local Windows machine.

Record the date, browser/version, viewport and actual outcome when completing manual checks. Automated HTTP assertions are not evidence of rendered layout or assistive-technology behavior. Do not create a GitHub release until the local Windows demonstration has been confirmed. The official report template has been supplied. The final DOCX is prepared separately and must be updated with genuine screenshots and final test evidence before submission.
