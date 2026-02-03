#include <iostream>
#include <fstream>
#include <string>
#include <cstdlib>

using namespace std;

int main() {
    // 1. Get/Generate Session ID
    string sid = "54321"; // Simple static ID for demo; in production use a UUID
    
    // 2. Read POST data from STDIN for 'username'
    string username = "";
    char* content_length_str = getenv("CONTENT_LENGTH");
    if (content_length_str) {
        int len = atoi(content_length_str);
        char* buffer = new char[len + 1];
        cin.read(buffer, len);
        buffer[len] = '\0';
        string post_data = buffer;
        delete[] buffer;

        // Simple parse for "username=VALUE"
        size_t pos = post_data.find("username=");
        if (pos != string::npos) {
            username = post_data.substr(pos + 9);
            // Replace '+' with space (basic URL decoding)
            for(size_t i = 0; i < username.length(); ++i) if(username[i] == '+') username[i] = ' ';
        }
    }

    // 3. Save to File in /tmp
    if (!username.empty()) {
        ofstream outfile("/tmp/cpp_sess_" + sid);
        outfile << username;
        outfile.close();
    } else {
        // Try to read existing
        ifstream infile("/tmp/cpp_sess_" + sid);
        if (infile) { getline(infile, username); infile.close(); }
    }

    // 4. Output Headers & HTML
    cout << "Set-Cookie: CPP_SESSID=" << sid << "; Path=/\n";
    cout << "Content-type: text/html\n\n";
    cout << "<html><head><title>C++ Sessions</title></head><body>";
    cout << "<h1>C++ Sessions Page 1</h1>";
    
    if (!username.empty()) cout << "<p><b>Name:</b> " << username << "</p>";
    else cout << "<p><b>Name:</b> You do not have a name set</p>";

    cout << "<br><a href='sessions2-cpp.cgi'>Session Page 2</a><br>";
    cout << "<a href='/hw2/index-state.html'>Back to State Entry</a><br>";
    cout << "<form style='margin-top:30px' action='destroy-session-cpp.cgi' method='POST'>";
    cout << "<button type='submit'>Destroy Session</button></form></body></html>";
    
    return 0;
}