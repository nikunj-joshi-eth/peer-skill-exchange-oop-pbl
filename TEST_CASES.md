# Executed test results

Run: 2026-11-17T03:50:37.681112+00:00

Environment: Windows; MinGW GCC 6.3.0; SQLite amalgamation 3.51.3; PHP executable `C:/xampp/php/php.exe`; Python standard library.

Result: 40 passed, 0 failed.

| Test | Result | Evidence |
| --- | --- | --- |
| All six empty dashboard pages render over HTTP | PASS | Executed successfully |
| Array query parameters handled without warnings | PASS | Executed successfully |
| Malformed POST fields rejected without mutation | PASS | Executed successfully |
| Missing prerequisites and keyboard navigation exposed | PASS | Executed successfully |
| Existing schema stays stable; both clients repair missing objects | PASS | Executed successfully |
| CSS and JavaScript served successfully | PASS | Executed successfully |
| Console rejects trailing numeric text and empty names | PASS | Executed successfully |
| C++ registration appears in PHP dashboard | PASS | Executed successfully |
| PHP registration writes learner | PASS | Executed successfully |
| PHP changes appear after C++ restart | PASS | Executed successfully |
| Duplicate student IDs rejected in both interfaces | PASS | Executed successfully |
| Empty and malformed student fields rejected | PASS | Executed successfully |
| Profile editing, search and SQL-like input | PASS | Executed successfully |
| C++ listing creation visible on dashboard | PASS | Executed successfully |
| PHP Learn listing and search/type filtering | PASS | Executed successfully |
| Invalid listing owner and type rejected | PASS | Executed successfully |
| Session scheduling and C++ visibility | PASS | Executed successfully |
| Self tutoring, missing learner and Learn bookings rejected | PASS | Executed successfully |
| Invalid date, time, duration and duplicate session rejected | PASS | Executed successfully |
| Feedback before completion and Pending-to-Completed rejected | PASS | Executed successfully |
| PHP accepts session; C++ completes session | PASS | Executed successfully |
| Progress derives from completed hours without feedback | PASS | Executed successfully |
| Invalid ratings and terminal status changes rejected | PASS | Executed successfully |
| PHP feedback visible in C++; output escaping and averages | PASS | Executed successfully |
| Duplicate feedback rejected in both interfaces | PASS | Executed successfully |
| Feedback and skill editing persist | PASS | Executed successfully |
| Booked listing ownership/type immutable and deletion protected | PASS | Executed successfully |
| Failed status forms do not populate create fields | PASS | Executed successfully |
| Validation preserves edited form values | PASS | Executed successfully |
| Missing edit record gives useful error | PASS | Executed successfully |
| Missing or invalid CSRF rejected without mutation | PASS | Executed successfully |
| Database directly rejects foreign keys, bad dates, feedback identity and status bypasses | PASS | Executed successfully |
| Rejected sessions remain terminal | PASS | Executed successfully |
| Console date/time validation and rejection flow | PASS | Executed successfully |
| Initialization is repeatable without changing IDs or records | PASS | Executed successfully |
| Fresh SQLite connection sees persisted feedback | PASS | Executed successfully |
| Restarted PHP serves persisted feedback | PASS | Executed successfully |
| Delete feedback, sessions, listings and students in dependency order | PASS | Executed successfully |
| Foreign key integrity clean | PASS | Executed successfully |
| Unavailable database returns safe actionable HTTP 503 | PASS | Executed successfully |

Reproduce: `python tests/integration.py --php C:/xampp/php/php.exe` after building the console. Tests create and remove an isolated temporary database under `build/`; the real project database is untouched.

Compilation and PHP lint results are reported separately in the review handoff. The checks above exercise the current implementation over real console processes, HTTP requests, and isolated SQLite connections.

Not executed: interactive browser layout/accessibility review, JavaScript confirmation-dialog interaction, or multi-user load testing. No browser automation tool is available in this session. Responsive CSS and confirmation code were inspected; this is not a visual test pass.

Git push results are documented separately in the final handoff.
