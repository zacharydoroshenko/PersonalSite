#include <iostream>
#include <string>
#include <fstream>
#include <stdlib.h>
#include <time.h>

using namespace std;

int main() {
    // 1. Extract POST data
    string postData = "";
    string name = "";
    char* len_str = getenv("CONTENT_LENGTH");
    
    if (len_str != NULL) {
        int len = atoi(len_str);
        char* buffer = new char[len + 1];
        cin.read(buffer, len);
        buffer[len] = '\0';
        postData = buffer;
        delete[] buffer;

        size_t pos = postData.find("username=");
        if (pos != string::npos) {
            name = postData.substr(pos + 9);
        }
    }

    // 2. If name exists, create Session ID, Cookie, and File
    if (!name.empty()) {
        srand(time(NULL));
        string sid = to_string(rand() % 100000);
        
        // Write to /tmp/ for server-side persistence
        ofstream outFile("/tmp/cpp_sess_" + sid);
        outFile << name;
        outFile.close();

        // Send Cookie header before Content-type
        cout << "Set-Cookie: CPP_SESSID=" << sid << "; Path=/\r\n";
    }

    cout << "Content-type:text/html\r\n\r\n";
    cout << "<html><body><h1>C++ Sessions Page 1</h1>";
    
    if (!name.empty()) {
        cout << "<p><b>Name Saved:</b> " << name << "</p>";
    } else {
        cout << "<p>No name entered.</p>";
    }

    cout << "<br><a href=\"sessions2-cpp.cgi\">Go to Page 2</a><br>";
    cout << "<a href=\"/hw2/index-state.html\">Back to Dashboard</a>";
    cout << "</body></html>";

    return 0;
}