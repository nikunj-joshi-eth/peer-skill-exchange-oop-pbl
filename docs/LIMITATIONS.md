# Supported scope, limitations and future work

The application is a local university demonstration of OOP, shared persistence and peer learning workflows. It is not a public multi-user service.

## Current limitations

- No authentication, authorization, tutor identity verification, private accounts or audit log. Every person with local access can manage records. CSRF protection is a form safeguard, not access control. Bind PHP to loopback and serve only `dashboard/`.
- No session overlap detection, timezone handling, reminders, payment, chat or automatic tutor matching. Past dates are permitted for demonstration records. Hours represent completed scheduled duration, not independently measured attendance.
- User-assigned positive IDs must be unique. Booking details cannot be edited. Correct them by deleting the session when dependencies permit and creating it again.
- Profile skills are free text and independent of skill listings. The console edits listing title/description; the dashboard can also change an unbooked listing's owner/type and can edit feedback. These deliberate interface differences are retained.
- C++ reloads all vectors per menu cycle and PHP renders unpaginated results. No load or concurrency benchmark has been performed. Both clients have a five-second busy timeout; conflicting writes can still fail.
- C++ may display values from the snapshot loaded before a menu prompt. Advance to the next menu cycle after making a PHP change; reload any open web form after using C++. There is no live push or stale-edit conflict detection.
- SQLite setup is additive, not a migration tool. Existing object names do not prove that an arbitrary older database has compatible definitions. Back up before deliberate schema changes.
- C++ console encoding depends on the Windows terminal code page. Search case folding is byte-oriented for C++ and SQLite LIKE is not complete Unicode linguistic search. PHP and SQLite validate stored field lengths; console overlength fields can produce a database error rather than an inline retry.
- Console EOF calls `exit(0)`; use menu 0 for normal stack cleanup. A rare database error during refresh is treated as an outer/startup failure and exits the console.
- With JavaScript disabled, delete submits without a confirmation dialog. Database restrictions still apply. Interactive focus behavior, screen-reader announcements, visual layout/zoom and browser confirmation controls require manual review.
- Stored record deletion affects current summaries; records are not an immutable academic transcript. Local database/session files and downloaded dependencies are intentionally excluded from Git.

## Possible future scope (not implemented)

Subject to agreed requirements: pagination and query profiling, overlap checks with explicit timezone rules, a versioned database migration/backup workflow, richer Unicode search, optimistic edit conflict checks and testable separation of menu input from domain services. Authentication and roles would be a separate deployment project, not a feature added to this finalization.

## Submission evidence

Use [TEST_CASES.md](../TEST_CASES.md) for actual integration results and the [demonstration guide](DEMONSTRATION_GUIDE.md) for screenshots and manual checks. No screenshots, browser passes, individual commit contributions, official university DOCX report or GitHub release are fabricated. The final university report awaits its official template; a release awaits confirmation of the local Windows demonstration.
