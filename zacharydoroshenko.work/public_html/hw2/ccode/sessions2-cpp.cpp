#include <iostream>
#include <string>
#include <fstream>
#include <stdlib.h>

using namespace std;

int main() {
    // 1. Output the mandatory CGI Header
    cout << "Content-type:text/html\r\n\r\n";

    // 2. Retrieve the Session ID (CPP_SESSID) from the Cookie
    char* cookie_env = getenv("HTTP_COOKIE");
    string sid = "";
    string username = "You do not have a name set";
    
    if (cookie_env != NULL) {
        string cookie_str = cookie_env;
        size_t pos = cookie_str.find("CPP_SESSID=");
        if (pos != string::npos) {
            size_t start = pos + 11;
            size_t end = cookie_str.find(";", start);
            sid = cookie_str.substr(start, end - start);
        }
    }

    // 3. Read the name from the server-side file in /tmp/
    if (!sid.empty()) {
        string filepath = "/tmp/cpp_sess_" + sid;
        ifstream sessFile(filepath.c_str());
        if (sessFile.is_open()) {
            getline(sessFile, username);
            sessFile.close();
        }
    }

    // 4. Output HTML
    cout << "<html><head><title>C++ Sessions - Page 2</title></head><body>";
    cout << "<h1>C++ Sessions Page 2</h1>";
    cout << "<p><b>Name:</b> " << username << "</p>";

    cout << "<br>";
    cout << "<a href=\"sessions1-cpp.cgi\">Back to Page 1</a><br>";
    cout << "<a href=\"/hw2/index-state.html\">Back to Dashboard</a><br>";
    
    cout << "<form style=\"margin-top:30px\" action=\"destroy-session-cpp.cgi\" method=\"POST\">";
    cout << "<button type=\"submit\">Destroy Session</button></form>";
    cout << "</body></html>";

    return 0;
}