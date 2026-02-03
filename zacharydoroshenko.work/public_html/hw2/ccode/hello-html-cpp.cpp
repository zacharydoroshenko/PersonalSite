#include <iostream>
#include <ctime>
#include <cstdlib>

using namespace std;

int main() {
    // 1. Required CGI Headers
    cout << "Cache-Control: no-cache" << endl;
    cout << "Content-Type: text/html\n\n";

    // 2. HTML Output
    cout << "<!DOCTYPE html>" << endl;
    cout << "<html>" << endl;
    cout << "<head><title>Hello CGI World! This is Zachary!</title></head>" << endl;
    cout << "<body>" << endl;

    cout << "<h1 align=\"center\">Hello HTML World</h1><hr/>" << endl;
    cout << "<p>Hello from Zachary and Team!</p>" << endl;
    cout << "<p>This page was generated with the C++ programming language.</p>" << endl;

    // 3. Date and Time
    time_t now = time(0);
    char* dt = ctime(&now);
    cout << "<p>This program was generated at: " << dt << "</p>" << endl;

    // 4. IP Address (using getenv)
    char* ip_addr = getenv("REMOTE_ADDR");
    cout << "<p>Your current IP Address is: " << (ip_addr ? ip_addr : "IP Not Found") << "</p>" << endl;

    cout << "</body>" << endl;
    cout << "</html>" << endl;

    return 0;
}