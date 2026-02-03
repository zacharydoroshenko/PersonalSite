#include <iostream>
#include <string>
#include <stdlib.h>

using namespace std;

int main() {
    // 1. Output the mandatory CGI Header
    cout << "Content-type:text/html\r\n\r\n";

    // 2. Read POST data (simplified for the homework requirement)
    string postData = "";
    string name = "You do not have a name set";
    
    char* len_str = getenv("CONTENT_LENGTH");
    if (len_str != NULL) {
        int len = atoi(len_str);
        char* buffer = new char[len + 1];
        cin.read(buffer, len);
        buffer[len] = '\0';
        postData = buffer;
        delete[] buffer;

        // Extract value after "username="
        size_t pos = postData.find("username=");
        if (pos != string::npos) {
            name = postData.substr(pos + 9);
            // In a real session, you would write this 'name' to a file in /tmp/ here
        }
    }

    // 3. Simple UI
    cout << "<html><body>";
    cout << "<h1>C++ Sessions Page 1</h1>";
    cout << "<p><b>Name:</b> " << name << "</p>";
    cout << "<br>";
    cout << "<a href=\"sessions2-cpp.cgi\">Session Page 2</a><br>";
    cout << "<a href=\"/hw2/index-state.html\">Back to State Entry</a><br>";
    
    cout << "<form style=\"margin-top:30px\" action=\"destroy-session-cpp.cgi\" method=\"POST\">";
    cout << "<button type=\"submit\">Destroy Session</button></form>";
    cout << "</body></html>";

    return 0;
}