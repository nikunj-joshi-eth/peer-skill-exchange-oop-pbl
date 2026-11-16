// Peer-to-Peer Skill Exchange and Tutoring Platform (SDG 4)
// Reconstructed Phase 2 baseline from previously documented module prototypes.
// Team contributions:
// Nikunj Joshi (25/SET/BCSE/137): Student Profile Management
// Khushi (25/SET/BCSE/148): Skill Listings
// Mansi (25/SET/BCSE/131): Tutoring Session Management
// Sumit Kumar (25/SET/BCSE/145): Feedback and Learning Progress
// Note: This baseline uses in-memory arrays (maximum 50 per module).
// Phase 3 will add SQLite-backed persistent storage and a web dashboard.

#include <iostream>
#include <string>
#include <limits>
#include <iomanip>
#include <cctype>
#include <cstdlib>
using namespace std;

const int MAX_RECORDS = 50;

int readInt(const string& prompt) {
    int value;
    while (true) {
        cout << prompt;
        if (cin >> value) {
            cin.ignore(numeric_limits<streamsize>::max(), '\n');
            return value;
        }
        if (cin.eof()) { cout << "\nInput ended.\n"; exit(0); }
        cin.clear();
        cin.ignore(numeric_limits<streamsize>::max(), '\n');
        cout << "Invalid number. Try again.\n";
    }
}

double readDouble(const string& prompt) {
    double value;
    while (true) {
        cout << prompt;
        if (cin >> value && value >= 0 && value <= 10000) {
            cin.ignore(numeric_limits<streamsize>::max(), '\n');
            return value;
        }
        if (cin.eof()) { cout << "\nInput ended.\n"; exit(0); }
        cin.clear();
        cin.ignore(numeric_limits<streamsize>::max(), '\n');
        cout << "Enter a valid non-negative number.\n";
    }
}

string readText(const string& prompt) {
    string s;
    do {
        cout << prompt;
        if (!getline(cin, s)) { cout << "\nInput ended.\n"; exit(0); }
        if (s.find_first_not_of(" \t\r\n") != string::npos) return s;
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
public:
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
        date = readText("Date (YYYY-MM-DD): ");
        time = readText("Time (HH:MM): ");
        status = "Pending";
    }
    bool updateStatus(const string& next) {
        if (status == "Pending" && (next == "Accepted" || next == "Rejected")) {
            status = next; return true;
        }
        if (status == "Accepted" && next == "Completed") {
            status = next; return true;
        }
        return false;
    }
    void display() const {
        cout << "\nSession ID: " << sessionId << "\nListing ID: " << listingId
             << "\nLearner ID: " << learnerId << "\nTutor ID: " << tutorId
             << "\nSkill: " << skill << "\nDate/Time: " << date << " " << time
             << "\nStatus: " << status << "\n";
    }
};

class FeedbackProgress {
    int sessionId = 0, studentId = 0, rating = 0;
    double learningHours = 0;
    string comments;
public:
    int getSessionId() const { return sessionId; }
    int getStudentId() const { return studentId; }
    int getRating() const { return rating; }
    double getHours() const { return learningHours; }
    void input(int session, int student) {
        sessionId = session;
        studentId = student;
        do { rating = readInt("Rating (1-5): "); } while (rating < 1 || rating > 5);
        learningHours = readDouble("Learning hours: ");
        comments = readText("Comments: ");
    }
    void display() const {
        cout << "\nSession ID: " << sessionId << "\nLearner ID: " << studentId
             << "\nRating: " << rating << "/5\nLearning hours: " << learningHours
             << "\nComments: " << comments << "\n";
    }
};

StudentProfile students[MAX_RECORDS];
SkillListing listings[MAX_RECORDS];
TutoringSession sessions[MAX_RECORDS];
FeedbackProgress feedbacks[MAX_RECORDS];
int studentCount = 0, listingCount = 0, sessionCount = 0, feedbackCount = 0;

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
    if (studentCount >= MAX_RECORDS) { cout << "Student limit reached.\n"; return; }
    int id = readInt("New student ID: ");
    if (id <= 0 || findStudent(id) != -1) { cout << "Invalid or duplicate student ID.\n"; return; }
    students[studentCount++].input(id);
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
    if (listingCount >= MAX_RECORDS) { cout << "Listing limit reached.\n"; return; }
    int id = readInt("New listing ID: ");
    if (id <= 0 || findListing(id) != -1) { cout << "Invalid or duplicate listing ID.\n"; return; }
    int owner = readInt("Registered student ID: ");
    if (findStudent(owner) == -1) { cout << "Register this student first.\n"; return; }
    listings[listingCount++].input(id, owner);
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
    if (sessionCount >= MAX_RECORDS) { cout << "Session limit reached.\n"; return; }
    int id = readInt("New session ID: ");
    if (id <= 0 || findSession(id) != -1) { cout << "Invalid or duplicate session ID.\n"; return; }
    int listingId = readInt("Listing ID to book: ");
    int at = findListing(listingId);
    if (at < 0 || listings[at].getType() != "Offer") { cout << "Choose an existing Offer listing.\n"; return; }
    int learnerId = readInt("Learner student ID: ");
    if (findStudent(learnerId) == -1 || learnerId == listings[at].getStudentId()) {
        cout << "Learner must be registered and different from tutor.\n"; return;
    }
    sessions[sessionCount++].input(id, listings[at], learnerId);
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
    if (feedbackCount >= MAX_RECORDS) { cout << "Feedback limit reached.\n"; return; }
    int id = readInt("Completed session ID: ");
    int at = findSession(id);
    if (at < 0 || sessions[at].getStatus() != "Completed") {
        cout << "Feedback is allowed only for completed sessions.\n"; return;
    }
    if (findFeedbackForSession(id) != -1) { cout << "Feedback already exists for this session.\n"; return; }
    feedbacks[feedbackCount++].input(id, sessions[at].getLearnerId());
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
            ++count; ratings += feedbacks[i].getRating(); hours += feedbacks[i].getHours();
        }
    }
    cout << "\nFeedback records: " << count << "\nRecorded learning hours: " << hours;
    cout << "\nAverage rating: " << fixed << setprecision(2)
         << (count ? static_cast<double>(ratings) / count : 0.0) << "/5\n";
}
void showOverview() {
    double sum = 0, totalHours = 0;
    for (int i = 0; i < feedbackCount; ++i) { sum += feedbacks[i].getRating(); totalHours += feedbacks[i].getHours(); }
    cout << "\nStudents: " << studentCount << " | Listings: " << listingCount
         << " | Sessions: " << sessionCount << " | Feedback: " << feedbackCount
         << "\nAverage feedback rating: " << fixed << setprecision(2)
         << (feedbackCount ? sum / feedbackCount : 0.0)
         << "/5 | Logged learning hours: " << totalHours << "\n";
}

int main() {
    cout << "Peer-to-Peer Skill Exchange and Tutoring Platform - Phase 2\n";
    while (true) {
        cout << "\n===== MAIN MENU =====\n"
             << " 1. Register student\n 2. Display students\n 3. Search student\n"
             << " 4. Create skill listing\n 5. Display listings\n 6. Filter listings\n"
             << " 7. Search skill\n 8. Request tutoring session\n 9. Display sessions\n"
             << "10. Update session status\n11. Add feedback\n12. Display feedback\n"
             << "13. Learner progress\n14. Overview statistics\n 0. Exit\n";
        int option = readInt("Select: ");
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
            case 0: cout << "Goodbye!\n"; return 0;
            default: cout << "Invalid menu choice.\n";
        }
    }
}
