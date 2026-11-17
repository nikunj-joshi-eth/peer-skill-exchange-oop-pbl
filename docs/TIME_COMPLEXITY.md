# Operation-wise time complexity

Let **S** be students, **L** listings, **T** sessions, **F** feedback records, **K** total text length inspected, and **R** rows returned. Bounds below describe application work and typical B-tree access, not measured performance. Disk I/O, locks, SQL query plans, string conversion and rendering also contribute.

## C++ in-memory operations

| Operation | Time after the vectors have been refreshed | Explanation |
| --- | --- | --- |
| `findStudent`, student ID search | O(S) | Sequential vector scan; no hash map or binary search |
| `findListing`, `findSession`, `findFeedbackForSession` | O(L), O(T), O(F) | Sequential ID scans |
| Display one collection | O(N + output text) | Visits every element of that collection |
| Filter listings by Offer/Learn | O(L) | Type strings have fixed small bounded length |
| Search skill substring | O(K × Q) conservative worst case | Q is query length; lowercase copies and ordinary substring searches inspect skill text; no full-text index |
| Register student | O(S) plus insert cost | Duplicate scan precedes persistence |
| Create listing | O(L + S) plus insert cost | Checks duplicate listing and owner |
| Create session | O(T + L + S) plus insert cost | Checks duplicate, Offer listing, learner and tutor distinction |
| Change status | O(T) plus update cost | Find session, check transition, persist |
| Submit feedback | O(T + F) plus insert cost | Completed-session lookup and duplicate-review scan |
| Learner progress | O(S + T + F) | Verify student; aggregate completed sessions and submitted ratings |
| Overview | O(T + F) | Stored vector sizes provide counts; totals still require scans |
| Edit profile/listing | O(S) / O(L) plus update cost | In-memory existence lookup then SQL update |
| Delete | Database lookup and delete cost | `deleteRecord()` queries SQL instead of using vector find helpers |

**Every menu cycle also runs `refresh()`**, including cycles which only display an error. It materializes all four collections, so at least O(S + L + T + F + text bytes) work and memory are needed. Session loading joins listings; typical indexed joins add up to O(T log L). The SQL planner may choose another access path or sort. Full refresh cost must be added when discussing an end-to-end menu operation; an O(S) search is not the whole cost of the menu cycle.

## Database and dashboard operations

| Operation | Expected cost and qualifications |
| --- | --- |
| Primary-key lookup | Typically O(log N) B-tree page accesses, plus row bytes; not guaranteed O(1) |
| Insert or primary-key update | Typically O(log N) per affected B-tree, plus constraint checks, triggers, related-key lookups and commit I/O |
| Delete | Primary-key lookup plus dependent-record checks; indexed references typically cost O(log M + matching references), with deletion restricted if references exist |
| Listing/session/feedback joins | Often one scan of driving rows plus indexed lookups, e.g. O(T log L + T log S) for a session list, before sorting/output |
| Student and skill search | Leading-wildcard `LIKE '%query%'` generally scans candidate text; approximately O(K × Q) conservative text-matching work. The student search OR conditions can prevent an ID-only index optimization |
| Session schedule ordering | A result sort can cost O(T log T) without a matching date/time index |
| Learning progress view | Typical indexed learner joins visit students and their sessions, roughly O(S log T + T), with grouping and final sort potentially adding work |
| Average ratings | Scan relevant feedback plus participant lookups; grouping/sorting may require temporary structures |
| Render a page | At least O(R + rendered text); `fetchAll()` holds result rows in memory |

PHP currently loads all students for select options on every page, adding O(S + student text) memory and work even to pages that do not display a student selector. There is no pagination, so large lists increase both server memory and HTML size. The fixed 14-object schema-presence check is small in the project schema; it does not load domain records or execute DDL when all objects exist.

These statements explain the current implementation, not an argument for replacing the educational vectors with a framework. Future scaling options include refreshing only relevant collections, indexed/paginated queries and profiling representative data before adding indexes.
