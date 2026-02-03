#include <iostream>
#include <string>
#include <fstream>
#include <stdlib.h>

using namespace std;

int main() {
    char* cookie_env = getenv("HTTP_COOKIE");
    string sid = "";
    string username = "You do not have a name set";
    
    if (cookie_env != NULL) {
        string cookie_str = cookie_env;
        size_t pos = cookie_str.find("CPP_SESSID=");
        if (pos != string::npos) {
            size_t start = pos + 11;
            size_t end = cookie_str.find(";", start);
            // FIX: If no semicolon is found, the ID goes to the end of the string
            if (end == string::npos) {
                sid = cookie_str.substr(start);
            } else {
                sid = cookie_str.substr(start, end - start);
            }
        }
    }

    if (!sid.empty()) {
        string filepath = "/tmp/cpp_sess_" + sid;
        ifstream sessFile(filepath.c_str());
        if (sessFile.is_open()) {
            getline(sessFile, username);
            sessFile.close();
        }
    }

    cout << "Content-type:text/html\r\n\r\n";
    cout << "<html><body><h1>C++ Sessions Page 2</h1>";
    cout << "<p><b>Persistent Name:</b> " << username << "</p>";
    cout << "<br><a href=\"sessions1-cpp.cgi\">Back to Page 1</a><br>";
    cout << "<form action=\"destroy-session-cpp.cgi\" method=\"POST\">";
    cout << "<button type=\"submit\">Destroy Session</button></form>";
    cout << "</body></html>";

    return 0;
}