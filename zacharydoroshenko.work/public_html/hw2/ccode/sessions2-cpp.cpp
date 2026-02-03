#include <iostream>
#include <fstream>
#include <string>
#include <cstdlib>

using namespace std;

string get_cookie_value(string name) {
    char* raw_cookies = getenv("HTTP_COOKIE");
    if (!raw_cookies) return "";
    string cookies = raw_cookies;
    size_t pos = cookies.find(name + "=");
    if (pos == string::npos) return "";
    size_t start = pos + name.length() + 1;
    size_t end = cookies.find(";", start);
    return cookies.substr(start, end - start);
}

int main() {
    string sid = get_cookie_value("CPP_SESSID");
    string username = "";

    if (!sid.empty()) {
        ifstream infile("/tmp/cpp_sess_" + sid);
        if (infile) { getline(infile, username); infile.close(); }
    }

    cout << "Content-type: text/html\n\n";
    cout << "<html><body><h1>C++ Sessions Page 2</h1>";
    if (!username.empty()) cout << "<p><b>Name:</b> " << username << "</p>";
    else cout << "<p><b>Name:</b> You do not have a name set</p>";
    
    cout << "<br><a href='sessions1-cpp.cgi'>Session Page 1</a><br>";
    cout << "<a href='/index-state.html'>Back to State Entry</a><br>";
    cout << "<form style='margin-top:30px' action='destroy-session-cpp.cgi' method='POST'>";
    cout << "<button type='submit'>Destroy Session</button></form></body></html>";
    return 0;
}