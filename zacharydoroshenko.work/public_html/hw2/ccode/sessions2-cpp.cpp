#include <iostream>
#include <string>
#include <fstream>
#include <stdlib.h>

using namespace std;

int main() {
    string sid = "";
    string name = "";
    char* cookie_env = getenv("HTTP_COOKIE");

    if (cookie_env != NULL) {
        string cookie_str = cookie_env;
        size_t pos = cookie_str.find("CPP_SESSID=");
        if (pos != string::npos) {
            size_t start = pos + 11;
            size_t end = cookie_str.find(";", start);
            sid = (end == string::npos) ? cookie_str.substr(start) : cookie_str.substr(start, end - start);
            
            ifstream sessFile("/tmp/cpp_sess_" + sid);
            if (sessFile.is_open()) {
                getline(sessFile, name);
                sessFile.close();
            }
        }
    }

    cout << "Content-type:text/html\r\n\r\n";
    cout << "<html><body><h1>C++ Sessions Page 2</h1>";
    if (!name.empty()) {
        cout << "<p><b>Name:</b> " << name << "</p>";
    } else {
        cout << "<p><b>Name:</b> You do not have a name set</p>";
    }
    cout << "<br><a href=\"sessions1-cpp.cgi\">Back to Page 1</a><br>";
    cout << "<form action=\"destroy-session-cpp.cgi\" method=\"POST\">";
    cout << "<button type=\"submit\">Destroy Session</button></form></body></html>";
    return 0;
}