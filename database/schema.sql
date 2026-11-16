-- Shared schema: keep both interfaces on this one file.
-- IDs use the C++ signed-int range. Deletes restrict referenced history.
PRAGMA foreign_keys = ON;
CREATE TABLE IF NOT EXISTS students (
 id INTEGER PRIMARY KEY CHECK(id BETWEEN 1 AND 2147483647), name TEXT NOT NULL CHECK(length(trim(name)) BETWEEN 1 AND 200),
 skills_offered TEXT NOT NULL CHECK(length(trim(skills_offered)) BETWEEN 1 AND 1000),
 skills_needed TEXT NOT NULL CHECK(length(trim(skills_needed)) BETWEEN 1 AND 1000)
);
CREATE TABLE IF NOT EXISTS skill_listings (
 id INTEGER PRIMARY KEY CHECK(id BETWEEN 1 AND 2147483647), student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE RESTRICT,
 skill_name TEXT NOT NULL CHECK(length(trim(skill_name)) BETWEEN 1 AND 200),
 type TEXT NOT NULL CHECK(type IN ('Offer','Learn')),
 description TEXT NOT NULL CHECK(length(trim(description)) BETWEEN 1 AND 2000)
);
CREATE TABLE IF NOT EXISTS tutoring_sessions (
 id INTEGER PRIMARY KEY CHECK(id BETWEEN 1 AND 2147483647), listing_id INTEGER NOT NULL REFERENCES skill_listings(id) ON DELETE RESTRICT,
 learner_id INTEGER NOT NULL REFERENCES students(id) ON DELETE RESTRICT,
 tutor_id INTEGER NOT NULL REFERENCES students(id) ON DELETE RESTRICT,
 date TEXT NOT NULL CHECK(length(date)=10 AND date GLOB '[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]' AND substr(date,1,4) BETWEEN '1900' AND '9999' AND date(date,'+0 days') IS NOT NULL AND date(date,'+0 days')=date),
 time TEXT NOT NULL CHECK(length(time)=5 AND time GLOB '[0-9][0-9]:[0-9][0-9]' AND substr(time,1,2) BETWEEN '00' AND '23' AND substr(time,4,2) BETWEEN '00' AND '59'),
 duration_hours REAL NOT NULL CHECK(duration_hours>0 AND duration_hours<=24),
 status TEXT NOT NULL DEFAULT 'Pending' CHECK(status IN ('Pending','Accepted','Rejected','Completed')),
 CHECK(learner_id<>tutor_id)
);
CREATE TABLE IF NOT EXISTS feedback (
 session_id INTEGER PRIMARY KEY REFERENCES tutoring_sessions(id) ON DELETE RESTRICT,
 student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE RESTRICT,
 rating INTEGER NOT NULL CHECK(typeof(rating)='integer' AND rating BETWEEN 1 AND 5),
 comments TEXT NOT NULL CHECK(length(trim(comments)) BETWEEN 1 AND 2000)
);
CREATE TRIGGER IF NOT EXISTS session_insert BEFORE INSERT ON tutoring_sessions BEGIN
 SELECT CASE WHEN NEW.status<>'Pending' THEN RAISE(ABORT,'New sessions must be Pending') END;
 SELECT CASE WHEN NOT EXISTS(SELECT 1 FROM skill_listings WHERE id=NEW.listing_id AND student_id=NEW.tutor_id AND type='Offer') THEN RAISE(ABORT,'Choose an Offer listing owned by this tutor') END;
END;
CREATE TRIGGER IF NOT EXISTS session_update BEFORE UPDATE ON tutoring_sessions BEGIN
 SELECT CASE WHEN NEW.id<>OLD.id OR NEW.listing_id<>OLD.listing_id OR NEW.learner_id<>OLD.learner_id OR NEW.tutor_id<>OLD.tutor_id OR NEW.date<>OLD.date OR NEW.time<>OLD.time OR NEW.duration_hours<>OLD.duration_hours THEN RAISE(ABORT,'Session details are immutable; delete an unreviewed session and reschedule') END;
 SELECT CASE WHEN NOT ((OLD.status='Pending' AND NEW.status IN ('Accepted','Rejected')) OR (OLD.status='Accepted' AND NEW.status='Completed')) THEN RAISE(ABORT,'Invalid status transition') END;
END;
CREATE TRIGGER IF NOT EXISTS listing_update BEFORE UPDATE ON skill_listings WHEN EXISTS(SELECT 1 FROM tutoring_sessions WHERE listing_id=OLD.id) BEGIN
 SELECT CASE WHEN NEW.id<>OLD.id OR NEW.student_id<>OLD.student_id OR NEW.type<>OLD.type THEN RAISE(ABORT,'Booked listing owner and type cannot change') END;
END;
CREATE TRIGGER IF NOT EXISTS feedback_insert BEFORE INSERT ON feedback BEGIN
 SELECT CASE WHEN NOT EXISTS(SELECT 1 FROM tutoring_sessions WHERE id=NEW.session_id AND learner_id=NEW.student_id AND status='Completed') THEN RAISE(ABORT,'Feedback requires the completed session learner') END;
END;
CREATE TRIGGER IF NOT EXISTS feedback_update BEFORE UPDATE ON feedback BEGIN
 SELECT CASE WHEN NEW.session_id<>OLD.session_id OR NEW.student_id<>OLD.student_id THEN RAISE(ABORT,'Feedback identity cannot change') END;
END;
CREATE INDEX IF NOT EXISTS listing_owner ON skill_listings(student_id);
CREATE INDEX IF NOT EXISTS session_learner ON tutoring_sessions(learner_id);
CREATE INDEX IF NOT EXISTS session_tutor ON tutoring_sessions(tutor_id);
CREATE INDEX IF NOT EXISTS session_listing ON tutoring_sessions(listing_id);
CREATE VIEW IF NOT EXISTS learning_progress AS
 SELECT s.id,s.name,COUNT(t.id) AS completed_sessions,COALESCE(SUM(t.duration_hours),0) AS learning_hours
 FROM students s LEFT JOIN tutoring_sessions t ON t.learner_id=s.id AND t.status='Completed' GROUP BY s.id;
