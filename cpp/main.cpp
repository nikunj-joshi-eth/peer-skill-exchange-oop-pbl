// Peer-to-Peer Skill Exchange and Tutoring Platform (SDG 4)
// Reconstructed Phase 2 baseline from previously documented module prototypes.
// Team contributions:
// Nikunj Joshi (25/SET/BCSE/137): Student Profile Management
// Khushi (25/SET/BCSE/148): Skill Listings
// Mansi (25/SET/BCSE/131): Tutoring Session Management
// Sumit Kumar (25/SET/BCSE/145): Feedback and Learning Progress
// Shared SQLite persistence; the original four module classes and menu are retained.

#include <iostream>
#include <string>
#include <limits>
#include <iomanip>
#include <cctype>
#include <cstdlib>
#include <sstream>
#include <cmath>
#include <stdexcept>
#include <vector>
#include "database.h"
using namespace std;

Database* database = nullptr;

string readLine(const string& prompt) {
    cout << prompt;
    string value;
    if (!getline(cin, value)) { cout << "\nInput ended.\n"; exit(0); }
    return value;
}

int readInt(const string& prompt) {
    while (true) {
        istringstream input(readLine(prompt));
        int value; char extra;
        if ((input >> value) && !(input >> extra)) return value;
        cout << "Enter a whole number without trailing text.\n";
    }
}

double readDouble(const string& prompt) {
    while (true) {
        istringstream input(readLine(prompt));
        double value; char extra;
        if ((input >> value) && !(input >> extra) && isfinite(value) && value >= 0 && value <= 10000)
            return value;
        cout << "Enter a finite number from 0 to 10000.\n";
    }
}

bool validDate(const string& value) {
    if (value.size() != 10 || value[4] != '-' || value[7] != '-') return false;
    for (size_t i = 0; i < value.size(); ++i)
        if (i != 4 && i != 7 && !isdigit(static_cast<unsigned char>(value[i]))) return false;
    int year = stoi(value.substr(0,4)), month = stoi(value.substr(5,2)), day = stoi(value.substr(8,2));
    int days[] = {31,28,31,30,31,30,31,31,30,31,30,31};
    if (year < 1900 || year > 9999 || month < 1 || month > 12) return false;
    if (year % 4 == 0 && (year % 100 != 0 || year % 400 == 0)) days[1] = 29;
    return day >= 1 && day <= days[month-1];
}

bool validTime(const string& value) {
    return value.size() == 5 && value[2] == ':' &&
        isdigit(static_cast<unsigned char>(value[0])) && isdigit(static_cast<unsigned char>(value[1])) &&
        isdigit(static_cast<unsigned char>(value[3])) && isdigit(static_cast<unsigned char>(value[4])) &&
        stoi(value.substr(0,2)) < 24 && stoi(value.substr(3,2)) < 60;
}

string readText(const string& prompt) {
    string s;
    do {
        cout << prompt;
        if (!getline(cin, s)) { cout << "\nInput ended.\n"; exit(0); }
        size_t first = s.find_first_not_of(" \t\r\n");
        if (first != string::npos) return s.substr(first, s.find_last_not_of(" \t\r\n") - first + 1);
        cout << "This field cannot be empty.\n";
    } while (true);
}

string lowerText(string s) {
    for (char& c : s) c = static_cast<char>(tolower(static_cast<unsigned char>(c)));
    return s;
}

class StudentProfile {
    int studentId = 0;
    string name, skillsOffered, skillsNeeded;
public:
    StudentProfile() = default;
    explicit StudentProfile(const Row& r) : studentId(stoi(r[0])), name(r[1]), skillsOffered(r[2]), skillsNeeded(r[3]) {}
    void save() const { database->execute("INSERT INTO students VALUES(?,?,?,?)", {to_string(studentId),name,skillsOffered,skillsNeeded}); }
    int getId() const { return studentId; }
    void input(int id) {
        studentId = id;
        name = readText("Student name: ");
        skillsOffered = readText("Skills offered: ");
        skillsNeeded = readText("Skills to learn: ");
    }
    void display() const {
        cout << "\nStudent ID: " << studentId << "\nName: " << name
             << "\nSkills offered: " << skillsOffered
             << "\nSkills needed: " << skillsNeeded << "\n";
    }
};

class SkillListing {
    int listingId = 0, studentId = 0;
    string skillName, type, description;
public:
    SkillListing() = default;
    explicit SkillListing(const Row& r) : listingId(stoi(r[0])), studentId(stoi(r[1])), skillName(r[2]), type(r[3]), description(r[4]) {}
    void save() const { database->execute("INSERT INTO skill_listings VALUES(?,?,?,?,?)", {to_string(listingId),to_string(studentId),skillName,type,description}); }
    int getId() const { return listingId; }
    int getStudentId() const { return studentId; }
    const string& getType() const { return type; }
    const string& getSkillName() const { return skillName; }
    bool matchesType(const string& value) const { return lowerText(type) == lowerText(value); }
    bool matchesSkill(const string& value) const {
        return lowerText(skillName).find(lowerText(value)) != string::npos;
    }
    void input(int id, int ownerId) {
        listingId = id;
        studentId = ownerId;
        skillName = readText("Skill name: ");
        while (true) {
            type = readText("Type (Offer/Learn): ");
            if (lowerText(type) == "offer") { type = "Offer"; break; }
            if (lowerText(type) == "learn") { type = "Learn"; break; }
            cout << "Type must be Offer or Learn.\n";
        }
        description = readText("Description: ");
    }
    void display() const {
        cout << "\nListing ID: " << listingId << "\nStudent ID: " << studentId
             << "\nSkill: " << skillName << "\nType: " << type
             << "\nDescription: " << description << "\n";
    }
};

class TutoringSession {
    int sessionId = 0, listingId = 0, learnerId = 0, tutorId = 0;
    string skill, date, time, status = "Pending";
    double durationHours = 1;
public:
    TutoringSession() = default;
    explicit TutoringSession(const Row& r) : sessionId(stoi(r[0])), listingId(stoi(r[1])), learnerId(stoi(r[2])), tutorId(stoi(r[3])), skill(r[8]), date(r[4]), time(r[5]), status(r[7]), durationHours(stod(r[6])) {}
    double getHours() const { return durationHours; }
    void save() const { database->execute("INSERT INTO tutoring_sessions VALUES(?,?,?,?,?,?,?,?)", {to_string(sessionId),to_string(listingId),to_string(learnerId),to_string(tutorId),date,time,to_string(durationHours),status}); }
    int getId() const { return sessionId; }
    int getLearnerId() const { return learnerId; }
    int getTutorId() const { return tutorId; }
    const string& getStatus() const { return status; }
    void input(int id, const SkillListing& listing, int learner) {
        sessionId = id;
        listingId = listing.getId();
        tutorId = listing.getStudentId();
        learnerId = learner;
        skill = listing.getSkillName();
        do { date = readText("Date (YYYY-MM-DD): "); } while (!validDate(date));
        do { time = readText("Time (HH:MM): "); } while (!validTime(time));
        do { durationHours = readDouble("Duration hours (0 < hours <= 24): "); } while (durationHours <= 0 || durationHours > 24);
        status = "Pending";
    }
    bool updateStatus(const string& next) {
        if (status == "Pending" && (next == "Accepted" || next == "Rejected")) {
            database->execute("UPDATE tutoring_sessions SET status=? WHERE id=?", {next,to_string(sessionId)});
            status = next; return true;
        }
        if (status == "Accepted" && next == "Completed") {
            database->execute("UPDATE tutoring_sessions SET status=? WHERE id=?", {next,to_string(sessionId)});
            status = next; return true;
        }
        return false;
    }
    void display() const {
        cout << "\nSession ID: " << sessionId << "\nListing ID: " << listingId
             << "\nLearner ID: " << learnerId << "\nTutor ID: " << tutorId
             << "\nSkill: " << skill << "\nDate/Time: " << date << " " << time
             << "\nDuration hours: " << durationHours << "\nStatus: " << status << "\n";
    }
};

class FeedbackProgress {
    int sessionId = 0, studentId = 0, rating = 0;
    string comments;
public:
    FeedbackProgress() = default;
    explicit FeedbackProgress(const Row& r) : sessionId(stoi(r[0])), studentId(stoi(r[1])), rating(stoi(r[2])), comments(r[3]) {}
    void save() const { database->execute("INSERT INTO feedback VALUES(?,?,?,?)", {to_string(sessionId),to_string(studentId),to_string(rating),comments}); }
    int getSessionId() const { return sessionId; }
    int getStudentId() const { return studentId; }
    int getRating() const { return rating; }
    void input(int session, int student) {
        sessionId = session;
        studentId = student;
        do { rating = readInt("Rating (1-5): "); } while (rating < 1 || rating > 5);
        comments = readText("Comments: ");
    }
    void display() const {
        cout << "\nSession ID: " << sessionId << "\nLearner ID: " << studentId
             << "\nRating: " << rating << "/5"
             << "\nComments: " << comments << "\n";
    }
};

vector<StudentProfile> students;
vector<SkillListing> listings;
vector<TutoringSession> sessions;
vector<FeedbackProgress> feedbacks;
int studentCount=0, listingCount=0, sessionCount=0, feedbackCount=0;
void refresh() {
    students.clear(); listings.clear(); sessions.clear(); feedbacks.clear();
    for (const auto& r : database->query("SELECT * FROM students ORDER BY id")) students.emplace_back(r);
    for (const auto& r : database->query("SELECT * FROM skill_listings ORDER BY id")) listings.emplace_back(r);
    for (const auto& r : database->query("SELECT t.*,l.skill_name FROM tutoring_sessions t JOIN skill_listings l ON l.id=t.listing_id ORDER BY t.id")) sessions.emplace_back(r);
    for (const auto& r : database->query("SELECT * FROM feedback ORDER BY session_id")) feedbacks.emplace_back(r);
    studentCount=students.size(); listingCount=listings.size(); sessionCount=sessions.size(); feedbackCount=feedbacks.size();
}

int findStudent(int id) {
    for (int i = 0; i < studentCount; ++i) if (students[i].getId() == id) return i;
    return -1;
}
int findListing(int id) {
    for (int i = 0; i < listingCount; ++i) if (listings[i].getId() == id) return i;
    return -1;
}
int findSession(int id) {
    for (int i = 0; i < sessionCount; ++i) if (sessions[i].getId() == id) return i;
    return -1;
}
int findFeedbackForSession(int id) {
    for (int i = 0; i < feedbackCount; ++i) if (feedbacks[i].getSessionId() == id) return i;
    return -1;
}

void addStudent() {
    int id = readInt("New student ID: ");
    if (id <= 0 || findStudent(id) != -1) { cout << "Invalid or duplicate student ID.\n"; return; }
    StudentProfile record; record.input(id); record.save();
    cout << "Student registered.\n";
}
void showStudents() {
    if (!studentCount) cout << "No students yet.\n";
    for (int i = 0; i < studentCount; ++i) students[i].display();
}
void searchStudent() {
    int at = findStudent(readInt("Student ID to search: "));
    if (at < 0) cout << "Student not found.\n";
    else students[at].display();
}
void addListing() {
    int id = readInt("New listing ID: ");
    if (id <= 0 || findListing(id) != -1) { cout << "Invalid or duplicate listing ID.\n"; return; }
    int owner = readInt("Registered student ID: ");
    if (findStudent(owner) == -1) { cout << "Register this student first.\n"; return; }
    SkillListing record; record.input(id, owner); record.save();
    cout << "Skill listing created.\n";
}
void showListings() {
    if (!listingCount) cout << "No listings yet.\n";
    for (int i = 0; i < listingCount; ++i) listings[i].display();
}
void filterListings() {
    string type = readText("Filter type (Offer/Learn): ");
    if (lowerText(type) != "offer" && lowerText(type) != "learn") { cout << "Invalid type.\n"; return; }
    bool found = false;
    for (int i = 0; i < listingCount; ++i)
        if (listings[i].matchesType(type)) { listings[i].display(); found = true; }
    if (!found) cout << "No matching listings.\n";
}
void searchSkills() {
    string keyword = readText("Skill keyword: ");
    bool found = false;
    for (int i = 0; i < listingCount; ++i)
        if (listings[i].matchesSkill(keyword)) { listings[i].display(); found = true; }
    if (!found) cout << "No matching skills.\n";
}
void addSession() {
    int id = readInt("New session ID: ");
    if (id <= 0 || findSession(id) != -1) { cout << "Invalid or duplicate session ID.\n"; return; }
    int listingId = readInt("Listing ID to book: ");
    int at = findListing(listingId);
    if (at < 0 || listings[at].getType() != "Offer") { cout << "Choose an existing Offer listing.\n"; return; }
    int learnerId = readInt("Learner student ID: ");
    if (findStudent(learnerId) == -1 || learnerId == listings[at].getStudentId()) {
        cout << "Learner must be registered and different from tutor.\n"; return;
    }
    TutoringSession record; record.input(id, listings[at], learnerId); record.save();
    cout << "Session requested (Pending).\n";
}
void showSessions() {
    if (!sessionCount) cout << "No sessions yet.\n";
    for (int i = 0; i < sessionCount; ++i) sessions[i].display();
}
void changeSessionStatus() {
    int at = findSession(readInt("Session ID: "));
    if (at < 0) { cout << "Session not found.\n"; return; }
    cout << "1. Accept  2. Reject  3. Complete\n";
    int option = readInt("Choice: ");
    string next = (option == 1 ? "Accepted" : option == 2 ? "Rejected" : option == 3 ? "Completed" : "Invalid");
    if (sessions[at].updateStatus(next)) cout << "Status updated.\n";
    else cout << "Invalid status transition. Pending -> Accepted/Rejected; Accepted -> Completed.\n";
}
void addFeedback() {
    int id = readInt("Completed session ID: ");
    int at = findSession(id);
    if (at < 0 || sessions[at].getStatus() != "Completed") {
        cout << "Feedback is allowed only for completed sessions.\n"; return;
    }
    if (findFeedbackForSession(id) != -1) { cout << "Feedback already exists for this session.\n"; return; }
    FeedbackProgress record; record.input(id, sessions[at].getLearnerId()); record.save();
    cout << "Feedback saved.\n";
}
void showFeedback() {
    if (!feedbackCount) cout << "No feedback yet.\n";
    for (int i = 0; i < feedbackCount; ++i) feedbacks[i].display();
}
void showProgress() {
    int studentId = readInt("Learner student ID: ");
    if (findStudent(studentId) == -1) { cout << "Student not found.\n"; return; }
    int count = 0, ratings = 0;
    double hours = 0;
    for (int i = 0; i < feedbackCount; ++i) {
        if (feedbacks[i].getStudentId() == studentId) {
            ++count; ratings += feedbacks[i].getRating(); 
        }
    }
    int completed = 0;
    for (const auto& session : sessions) if (session.getLearnerId() == studentId && session.getStatus() == "Completed") { ++completed; hours += session.getHours(); }
    cout << "\nCompleted sessions: " << completed;
    cout << "\nFeedback records: " << count << "\nRecorded learning hours: " << hours;
    cout << "\nAverage rating: " << fixed << setprecision(2)
         << (count ? static_cast<double>(ratings) / count : 0.0) << "/5\n";
}
void showOverview() {
    double sum = 0, totalHours = 0;
    for (int i = 0; i < feedbackCount; ++i) { sum += feedbacks[i].getRating();  }
    for (const auto& session : sessions) if (session.getStatus() == "Completed") totalHours += session.getHours();
    cout << "\nStudents: " << studentCount << " | Listings: " << listingCount
         << " | Sessions: " << sessionCount << " | Feedback: " << feedbackCount
         << "\nAverage feedback rating: " << fixed << setprecision(2)
         << (feedbackCount ? sum / feedbackCount : 0.0)
         << "/5 | Logged learning hours: " << totalHours << "\n";
}

void editStudent() {
    int id=readInt("Student ID: ");
    if (findStudent(id)<0) { cout << "Student not found.\n"; return; }
    string name=readText("Student name: "), offered=readText("Skills offered: "), needed=readText("Skills to learn: ");
    database->execute("UPDATE students SET name=?,skills_offered=?,skills_needed=? WHERE id=?", {name,offered,needed,to_string(id)});
    cout << "Student updated.\n";
}
void editListing() {
    int id=readInt("Listing ID: ");
    if (findListing(id)<0) { cout << "Listing not found.\n"; return; }
    string skill=readText("Skill name: "), description=readText("Description: ");
    database->execute("UPDATE skill_listings SET skill_name=?,description=? WHERE id=?", {skill,description,to_string(id)});
    cout << "Listing updated.\n";
}
void deleteRecord() {
    cout << "1. Student  2. Listing  3. Session  4. Feedback\n";
    int kind=readInt("Record type: ");
    if (kind<1 || kind>4) { cout << "Invalid record type.\n"; return; }
    string tables[]={"students","skill_listings","tutoring_sessions","feedback"};
    int id=readInt("ID (session ID for feedback): ");
    string column=kind==4 ? "session_id" : "id";
    if (database->query("SELECT * FROM "+tables[kind-1]+" WHERE "+column+"=?",{to_string(id)}).empty()) { cout << "Record not found.\n"; return; }
    database->execute("DELETE FROM "+tables[kind-1]+" WHERE "+column+"=?",{to_string(id)});
    cout << "Record deleted.\n";
}

int main(int argc, char* argv[]) {
    try {
    string path = argc > 1 ? argv[1] : "database/skill_exchange.db";
    Database storage(path); database=&storage;
    storage.initialize("database/schema.sql");
    cout << "Peer-to-Peer Skill Exchange and Tutoring Platform - SDG 4\n";
    while (true) {
        refresh();
        cout << "\n===== MAIN MENU =====\n"
             << " 1. Register student\n 2. Display students\n 3. Search student\n"
             << " 4. Create skill listing\n 5. Display listings\n 6. Filter listings\n"
             << " 7. Search skill\n 8. Request tutoring session\n 9. Display sessions\n"
             << "10. Update session status\n11. Add feedback\n12. Display feedback\n"
             << "13. Learner progress\n14. Overview statistics\n15. Edit student\n16. Edit listing\n17. Delete record\n 0. Exit\n";
        int option = readInt("Select: ");
        try {
        switch (option) {
            case 1: addStudent(); break;
            case 2: showStudents(); break;
            case 3: searchStudent(); break;
            case 4: addListing(); break;
            case 5: showListings(); break;
            case 6: filterListings(); break;
            case 7: searchSkills(); break;
            case 8: addSession(); break;
            case 9: showSessions(); break;
            case 10: changeSessionStatus(); break;
            case 11: addFeedback(); break;
            case 12: showFeedback(); break;
            case 13: showProgress(); break;
            case 14: showOverview(); break;
            case 15: editStudent(); break;
            case 16: editListing(); break;
            case 17: deleteRecord(); break;
            case 0: cout << "Goodbye!\n"; return 0;
            default: cout << "Invalid menu choice.\n";
        }
        } catch (const exception& error) { cout << "Operation failed: " << error.what() << "\n"; }
    }
    } catch (const exception& error) { cerr << "Startup failed: " << error.what() << "\n"; return 1; }
}
