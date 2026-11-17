# Team contribution responsibilities

Project: **Peer-to-Peer Skill Exchange and Tutoring Platform — SDG 4: Quality Education**.

This map preserves the team's assigned responsibilities from the Phase 2 baseline reconstructed from earlier module prototypes. Integration, validation, database and dashboard work connect the modules; this document does not attribute generated implementation or Git commits to individual students.

| Team member | University ID | C++ module | Shared data | Dashboard responsibility |
| --- | --- | --- | --- | --- |
| Nikunj Joshi | 25/SET/BCSE/137 | `StudentProfile` | `students` | Registration, directory, search, profile editing |
| Khushi | 25/SET/BCSE/148 | `SkillListing` | `skill_listings` | Offer/Learn creation, filters, search, listing editing |
| Mansi | 25/SET/BCSE/131 | `TutoringSession` | `tutoring_sessions` | Requests, tutor/learner validation, scheduling, status lifecycle |
| Sumit Kumar | 25/SET/BCSE/145 | `FeedbackProgress` | `feedback`, `learning_progress` view | Eligible reviews, ratings, comments, completed learning hours |

## Shared integration

- `cpp/database.h` and `database/schema.sql` provide the common persistence contract.
- Both applications enforce the same relationships and transitions, backed by database constraints.
- `tests/integration.py` exercises cross-module workflows and both interfaces against one isolated database.
- Students can explain classes, constructors, private data, getters, member functions, vectors, input validation, prepared SQL statements and exception handling using their corresponding module.
- Inheritance and design-pattern frameworks are intentionally unnecessary for these four independent modules.

## Review checkpoints

1. Each member reviews their class and corresponding dashboard workflow.
2. Together, demonstrate registration → Offer listing → tutoring session → completion → feedback.
3. Show that progress exists before feedback and that changes appear across the console and dashboard.
4. Use `TEST_CASES.md` for executed evidence, and distinguish automated checks from unexecuted visual review.

Use the [demonstration guide](DEMONSTRATION_GUIDE.md) for a repeatable team walkthrough. Each member should connect their class to the [OOP explanation](OOP_CONCEPTS.md), its [database rules](DATABASE_DESIGN.md), and the relevant [complexity analysis](TIME_COMPLEXITY.md). Shared integration is documented in [architecture](ARCHITECTURE.md). These are review and explanation responsibilities, not fabricated claims that a particular person authored or executed a particular change.
