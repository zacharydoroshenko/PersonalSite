#include <iostream>
#include <string>
#include <stdio.h>
#include <stdlib.h>

using namespace std;

int main() {
    char* cookie_env = getenv("HTTP_COOKIE");
    if (cookie_env != NULL) {
        string cookie_str = cookie_env;
        size_t pos = cookie_str.find("CPP_SESSID=");
        if (pos != string::npos) {
            size_t start = pos + 11;
            size_t end = cookie_str.find(";", start);
            string sid = (end == string::npos) ? cookie_str.substr(start) : cookie_str.substr(start, end - start);
            
            string filepath = "/tmp/cpp_sess_" + sid;
            remove(filepath.c_str()); 
        }
    }

    cout << "Set-Cookie: CPP_SESSID=; Max-Age=0; Path=/\r\n";
    cout << "Content-type:text/html\r\n\r\n";
    cout << "<html><body><h1>C++ Session Destroyed</h1>";
    cout << "<a href=\"/hw2/index-state.html\">Back to State Entry</a><br />";
    cout << "<a href=\"sessions1-cpp.cgi\">Back to Page 1</a></body></html>";
    return 0;
} 