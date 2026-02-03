#include <iostream>
#include <fstream>
#include <string>
#include <stdlib.h>
#include <time.h>

using namespace std;

int main() {

    string sid = "";
    string name = "";


    char* len_str = getenv("CONTENT_LENGTH");
    if (len_str != NULL && atoi(len_str) > 0) {
        int len = atoi(len_str);
        char* buffer = new char[len + 1];
        cin.read(buffer, len);
        buffer[len] = '\0';
        string postData = buffer;
        delete[] buffer;

        size_t pos = postData.find("username=");
        if (pos != string::npos) {
            name = postData.substr(pos + 9);

            srand(time(NULL));
            sid = to_string(rand() % 100000);
            

            ofstream outFile("/tmp/cpp_sess_" + sid);
            outFile << name;
            outFile.close();

            cout << "Set-Cookie: CPP_SESSID=" << sid << "; Path=/\r\n";
        }
    } 

    else {
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
    }


    cout << "Content-type:text/html\r\n\r\n";
    cout << "<html><body><h1>C++ Sessions Page 1</h1>";
    if (!name.empty()) {
        cout << "<p><b>Name:</b> " << name << "</p>";
        cout << "<a href=\"sessions2-cpp.cgi\">Go to Page 2</a><br>";
    } else {
        cout << "<p><b>Name:</b> You do not have a name set</p>";
        cout << "<a href=\"/hw2/index-state.html\">Back to State Entry</a><br>";
    }
    cout << "<form style=\"margin-top:30px\" action=\"destroy-session-cpp.cgi\" method=\"POST\">";
    cout << "<button type=\"submit\">Destroy Session</button></form></body></html>";
    return 0;
}