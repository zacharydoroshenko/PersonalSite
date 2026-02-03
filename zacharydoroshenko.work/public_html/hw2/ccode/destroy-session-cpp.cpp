#include <iostream>
#include <string>
#include <stdio.h> // Required for remove()
#include <stdlib.h>

using namespace std;

int main() {
    // 1. Get the Cookie from the environment
    char* cookie_env = getenv("HTTP_COOKIE");
    string sid = "";
    
    if (cookie_env != NULL) {
        string cookie_str = cookie_env;
        // Search for the specific C++ Session ID (e.g., CPP_SESSID)
        size_t pos = cookie_str.find("CPP_SESSID=");
        if (pos != string::npos) {
            size_t start = pos + 11;
            size_t end = cookie_str.find(";", start);
            sid = cookie_str.substr(start, end - start);
        }
    }

    // 2. Delete the file from /tmp/ if the session ID exists
    if (!sid.empty()) {
        string filepath = "/tmp/cpp_sess_" + sid;
        remove(filepath.c_str()); // This physically deletes the file from the server
    }

    // 3. Output Headers and Expire the Cookie
    cout << "Set-Cookie: CPP_SESSID=; Max-Age=0; Path=/\r\n";
    cout << "Content-type:text/html\r\n\r\n";

    // 4. UI Feedback
    cout << "<html><body>";
    cout << "<h1>C++ Session Destroyed</h1>";
    cout << "<p>The server-side state file and cookies have been cleared.</p>";
    cout << "<a href=\"sessions1-cpp.cgi\">Back to Page 1</a><br>";
    cout << "<a href=\"/hw2/index-state.html\">Back to Dashboard</a>";
    cout << "</body></html>";

    return 0;
}