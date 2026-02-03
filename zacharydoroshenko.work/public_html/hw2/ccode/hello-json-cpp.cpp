#include <iostream>
#include <ctime>
#include <cstdlib>

using namespace std;

int main() {
    // 1. Required CGI Headers for JSON
    cout << "Cache-Control: no-cache" << endl;
    cout << "Content-Type: application/json\n\n";

    // 2. Collect Data
    time_t now = time(0);
    char* dt = ctime(&now);
    // Remove the newline character that ctime adds by default
    string date_str = dt;
    if (!date_str.empty() && date_str[date_str.length()-1] == '\n') {
        date_str.erase(date_str.length()-1);
    }

    char* ip_addr = getenv("REMOTE_ADDR");
    string ip = (ip_addr ? ip_addr : "127.0.0.1");

    // 3. Manually construct the JSON string
    // We use \" to include actual double quotes inside the string
    cout << "{" << endl;
    cout << "  \"title\": \"Hello from C++! \"," << endl;
    cout << "  \"heading\": \"Hello, C++ JSON World!\"," << endl;
    cout << "  \"message\": \"This page was generated with the C++ programming language\"," << endl;
    cout << "  \"time\": \"" << date_str << "\"," << endl;
    cout << "  \"IP\": \"" << ip << "\"" << endl;
    cout << "}" << endl;

    return 0;
}