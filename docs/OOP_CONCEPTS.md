# OOP concepts used by the implementation

The four recognizable domain classes are defined in `cpp/main.cpp`. This document describes implemented concepts; inheritance and runtime polymorphism are not claimed.

| Class | Private state | Constructor and getter examples | Member behavior |
| --- | --- | --- | --- |
| `StudentProfile` | Student ID, name, offered and needed skills | Default constructor; explicit `Row` constructor; `getId()` | `input()`, `save()`, `display()` |
| `SkillListing` | Listing ID, owner ID, skill name, type, description | Default and row constructors; `getId()`, `getStudentId()`, `getType()`, `getSkillName()` | `input()`, `save()`, `display()`, `matchesType()`, `matchesSkill()` |
| `TutoringSession` | Session, listing, learner and tutor IDs; skill, date, time, status, duration | Default and row constructors; ID, participant, status and hours getters | `input()`, `save()`, `display()`, `updateStatus()` |
| `FeedbackProgress` | Session ID, learner ID, rating, comments | Default and row constructors; `getSessionId()`, `getStudentId()`, `getRating()` | `input()`, `save()`, `display()` |

## Encapsulation and abstraction

Class data are private by default. Outside code reads selected values through public getters. For example, session creation receives a `const SkillListing&` and obtains the listing owner and skill name through getters. `TutoringSession::updateStatus()` encapsulates the permitted lifecycle rather than exposing a public status variable.

`Database::query()` hides SQLite statement preparation, binding and iteration behind a small interface. Domain `save()` methods supply prepared SQL and values. Database constraints remain necessary because PHP is a second writer and may change data while C++ is running.

Encapsulation is not absolute architectural isolation: menu-level `editStudent()` and `editListing()` issue SQL directly, and progress is computed by `showProgress()` using session and feedback getters. `FeedbackProgress` models stored feedback; it does not store a second mutable learning-hours total. These are explicit boundaries of the current design.

## Constructors and objects

Default constructors support creating a new record, for example `StudentProfile record; record.input(id); record.save();`. Explicit row constructors reconstruct objects from database rows. Initializer lists assign fields at construction, with `stoi`/`stod` converting persisted numbers. `refresh()` uses `emplace_back(r)` to construct objects in vectors.

The `explicit` keyword prevents a row from being implicitly converted into a domain object. The compiler generates the ordinary copy operations for domain objects; the code does not define custom copy constructors or claim constructor overloading beyond default and row construction.

## Const correctness and references

Read-only getters, `save()` and `display()` use `const` member functions where appropriate. String getters return `const string&` to avoid copying and prevent mutation through the getter. `TutoringSession::input()` accepts a const reference to an existing listing. Getter references must not be retained across vector refreshes because clearing a vector invalidates references to its elements.

## Composition, ownership and lifetime

Vectors hold collections of domain objects. `Database` contains and owns a SQLite connection, demonstrating resource encapsulation and RAII: its constructor opens the connection and its destructor closes it on normal scope exit. Its copy constructor and assignment operator are deleted to prevent duplicate ownership. Prepared statements are finalized even if execution throws.

The global `database` pointer is non-owning and valid while `storage` in `main()` is alive. Input EOF currently calls `exit(0)`, which bypasses destructors for automatic local variables; the operating system reclaims process resources. Normal menu option 0 returns from `main()` and invokes the destructor. Do not claim that every termination path demonstrates automatic stack unwinding.

## Other syllabus concepts

- Functions, selection and loops coordinate menu operations and validation.
- Standard strings, vectors and range-based loops replace fixed 50-record arrays.
- Linear searches find IDs in vectors; substring matching supports skill searches.
- Exceptions separate failed SQL operations from successful menu paths.
- File input reads the shared SQL schema; SQLite provides durable record storage.
- Header guards prevent repeated inclusion of the database wrapper.

Inheritance, virtual dispatch, operator overloading, multiple inheritance and template metaprogramming are not part of this implementation. The standard library internally uses templates, but this project defines no custom class templates. Each member should explain the real code in their assigned module rather than inventing features to match a syllabus list.
