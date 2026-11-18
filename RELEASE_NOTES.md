# Release notes — v1.0.0 candidate

**Peer-to-Peer Skill Exchange and Tutoring Platform (PeerLearn)**  
Educational local C++ Object-Oriented Programming project supporting SDG 4: Quality Education.

## Included features
- Four C++ OOP classes: `StudentProfile`, `SkillListing`, `TutoringSession`, and `FeedbackProgress`.
- Menu-driven console and six-section local PHP dashboard.
- Single shared SQLite database with foreign keys, checks and lifecycle triggers.
- Student profiles, Offer/Learn skill search, bookings, status transitions, feedback and learning-hour summaries.
- Windows PowerShell build script, schema initializer and documented live demonstration.
- Automated cross-interface integration tests and academic technical documentation.

## Verified at the reviewed code checkpoint
- `TEST_CASES.md` records **46 passed / 0 failed** real console/HTTP/SQLite tests on Windows.
- The source-only Windows clean-export build, PHP syntax checks, and isolated integration tests were reported successful on 17 November 2026.
- The GitHub Actions workflow provides another cross-platform check; its status must be read from Actions before claiming it passed.

## Limitations
- **Local demonstration only. Do not host this PHP dashboard on the public internet.** There is no login or user authorization.
- Booking overlap detection, multi-user stress tests, accessibility and rendered browser interaction checks are not complete.
- The release source does not include real student database records. A new machine must build and initialize locally.

## Installation
See the repository `README.md` for prerequisites, Windows setup and run commands. Windows requires GCC/G++, PHP with PDO SQLite and mbstring, and Python 3 for optional tests.

## Before publishing this release
- [ ] Verify the new GitHub Actions check on the intended release commit.
- [ ] Perform the manual browser and Windows demonstration in `docs/DEMONSTRATION_GUIDE.md`.
- [ ] Check the repository contains no private student data, passwords, local database, or screenshots with personal information.
- [ ] Confirm team members agree to public display of their names and university IDs.
- [ ] Confirm required academic report and screenshots are complete.
- [ ] Create a GitHub release tagged `v1.0.0` at the verified commit. Attach only reviewed files.
