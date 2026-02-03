#include <iostream>
#include <cstdio>
#include <string>
#include <cstdlib>

using namespace std;

int main() {
    // 1. Logic to extract cookie (simplified for brevity)
    char* raw_cookies = getenv("HTTP_COOKIE");
    string sid = ""; 
    if (raw_cookies) {
        // ... extraction logic ...
    }

    // 2. Delete the file from /tmp
    if (!sid.empty()) {
        string filename = "/tmp/cpp_sess_" + sid;
        remove(filename.c_str());
    }

    // 3. Expire the cookie
    cout << "Set-Cookie: CPP_SESSID=; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/\n";
    cout << "Content-type: text/html\n\n";
    cout << "<h1>C++ Session Destroyed</h1>";
    cout << "<a href='sessions1-cpp.cgi'>Back to Page 1</a>";
    return 0;
}