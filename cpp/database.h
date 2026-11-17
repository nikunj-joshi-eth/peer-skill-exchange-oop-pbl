#ifndef SKILL_DATABASE_H
#define SKILL_DATABASE_H
#include "sqlite3.h"
#include <string>
#include <vector>
#include <fstream>
#include <sstream>
#include <stdexcept>
using Row = std::vector<std::string>;
class Database {
    sqlite3* handle = nullptr;
public:
    explicit Database(const std::string& path) {
        if (sqlite3_open(path.c_str(), &handle) != SQLITE_OK) {
            std::string error = handle ? sqlite3_errmsg(handle) : "Cannot allocate database";
            if (handle) sqlite3_close(handle);
            throw std::runtime_error(error);
        }
        sqlite3_busy_timeout(handle, 5000);
        try { execute("PRAGMA foreign_keys=ON"); } catch (...) { sqlite3_close(handle); throw; }
    }
    ~Database() { sqlite3_close(handle); }
    Database(const Database&) = delete;
    Database& operator=(const Database&) = delete;
    std::vector<Row> query(const std::string& sql, const Row& values = {}) {
        sqlite3_stmt* statement = nullptr;
        if (sqlite3_prepare_v2(handle, sql.c_str(), -1, &statement, nullptr) != SQLITE_OK)
            throw std::runtime_error(sqlite3_errmsg(handle));
        try {
            for (size_t i=0; i<values.size(); ++i)
                if (sqlite3_bind_text(statement, static_cast<int>(i+1), values[i].c_str(), static_cast<int>(values[i].size()), SQLITE_TRANSIENT) != SQLITE_OK)
                    throw std::runtime_error(sqlite3_errmsg(handle));
            std::vector<Row> rows;
            int result;
            while ((result=sqlite3_step(statement)) == SQLITE_ROW) {
                Row row;
                for (int i=0; i<sqlite3_column_count(statement); ++i) {
                    const unsigned char* value=sqlite3_column_text(statement,i);
                    row.push_back(value ? std::string(reinterpret_cast<const char*>(value), sqlite3_column_bytes(statement,i)) : "");
                }
                rows.push_back(row);
            }
            if (result != SQLITE_DONE) throw std::runtime_error(sqlite3_errmsg(handle));
            sqlite3_finalize(statement);
            return rows;
        } catch (...) { sqlite3_finalize(statement); throw; }
    }
    void execute(const std::string& sql, const Row& values = {}) { query(sql,values); }
    void initialize(const std::string& schema) {
        if (query("SELECT COUNT(*) FROM sqlite_master WHERE name IN ('students','skill_listings','tutoring_sessions','feedback','learning_progress','session_insert','session_update','listing_update','feedback_insert','feedback_update','listing_owner','session_learner','session_tutor','session_listing')")[0][0] == "14") return;
        std::ifstream file(schema);
        if (!file) throw std::runtime_error("Cannot read schema: " + schema);
        std::stringstream text; text << file.rdbuf();
        char* error=nullptr;
        if (sqlite3_exec(handle,text.str().c_str(),nullptr,nullptr,&error)!=SQLITE_OK) {
            std::string message=error; sqlite3_free(error); throw std::runtime_error(message);
        }
    }
};
#endif
