#include <iostream>
#include <string>
#include <stdio.h>
#include <stdlib.h>

using namespace std;

int main() {
    char* cookie_env = getenv("HTTP_COOKIE");
    string sid = "";
    
    if (cookie_env != NULL) {
        string cookie_str = cookie_env;
        size_t pos = cookie_str.find("CPP_SESSID=");
        if (pos != string::npos) {
            size_t start = pos + 11;
            size_t end = cookie_str.find(";", start);
            // FIX: Handle the case where CPP_SESSID is the last cookie
            if (end == string::npos) {
                sid = cookie_str.substr(start);
            } else {
                sid = cookie_str.substr(start, end - start);
            }
        }
    }

    if (!sid.empty()) {
        string filepath = "/tmp/cpp_sess_" + sid;
        remove(filepath.c_str()); // Deletes the server-side file
    }

    cout << "Set-Cookie: CPP_SESSID=; Max-Age=0; Path=/\r\n"; // Expire the browser cookie
    cout << "Content-type:text/html\r\n\r\n";
    cout << "<html><body><h1>Session Destroyed</h1>";
    cout << "<a href=\"/hw2/index-state.html\">Return to Dashboard</a>";
    cout << "</body></html>";

    return 0;
}