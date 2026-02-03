#include <iostream>
#include <string>
#include <fstream>
#include <unistd.h>

using namespace std;

int main() {
    // 1. Check for Cookie or POST data
    char* cookie_env = getenv("HTTP_COOKIE");
    string display_name = "";
    
    // Simplistic check for demo: In real code, parse the cookie and check /tmp/
    bool has_session = (cookie_env != nullptr);

    cout << "Content-type:text/html\r\n\r\n";
    cout << "<html><body><h1>C++ Sessions Page 1</h1>";

    if (has_session) {
        // Assume you parsed the name from your session file in /tmp/
        cout << "<p><b>Name:</b> [Stored Name]</p>"; 
        cout << "<a href=\"sessions2-cpp.cgi\">Session Page 2</a><br>";
        cout << "<form action=\"destroy-session-cpp.cgi\" method=\"POST\"><button type=\"submit\">Destroy Session</button></form>";
    } else {
        cout << "<p>No session found. Please enter a name first.</p>";
        cout << "<a href=\"/hw2/index-state.html\">Back to State Entry</a>";
    }

    cout << "</body></html>";
    return 0;
}