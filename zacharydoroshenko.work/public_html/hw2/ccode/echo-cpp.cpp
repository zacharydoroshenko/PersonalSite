#include <iostream>
#include <string>
#include <ctime>
#include <cstdlib>

using namespace std;

int main() {
    cout << "Cache-Control: no-cache" << endl;
    cout << "Content-type: text/html\n\n";

    string protocol = getenv("SERVER_PROTOCOL") ? getenv("SERVER_PROTOCOL") : "N/A";
    string method = getenv("REQUEST_METHOD") ? getenv("REQUEST_METHOD") : "N/A";
    string query = getenv("QUERY_STRING") ? getenv("QUERY_STRING") : "N/A";
    string hostname = getenv("HTTP_HOST") ? getenv("HTTP_HOST") : "N/A";
    string user_agent = getenv("HTTP_USER_AGENT") ? getenv("HTTP_USER_AGENT") : "Unknown";
    string ip_address = getenv("REMOTE_ADDR") ? getenv("REMOTE_ADDR") : "Unknown";

    time_t now = time(0);
    char* dt = ctime(&now);

    string body = "";
    char* content_length_str = getenv("CONTENT_LENGTH");
    if (content_length_str) {
        int length = atoi(content_length_str);
        if (length > 0) {
            char* buffer = new char[length + 1];
            cin.read(buffer, length);
            buffer[length] = '\0';
            body = buffer;
            delete[] buffer;
        }
    }

    cout << "<!DOCTYPE html>\n<html>\n<head><title>C++ General Echo</title></head>\n<body>\n";
    cout << "<h1 align=\"center\">C++ General Request Echo</h1><hr>\n";

    cout << "<p><b>Hostname:</b> " << hostname << "</p>\n";
    cout << "<p><b>Date/Time:</b> " << dt << "</p>\n";
    cout << "<p><b>User Agent:</b> " << user_agent << "</p>\n";
    cout << "<p><b>Your IP:</b> " << ip_address << "</p>\n";
    cout << "<hr>\n";

    cout << "<p><b>HTTP Protocol:</b> " << protocol << "</p>\n";
    cout << "<p><b>HTTP Method:</b> " << method << "</p>\n";
    cout << "<p><b>Query String:</b> " << query << "</p>\n";
    cout << "<p><b>Message Body:</b> " << (body.empty() ? "(empty)" : body) << "</p>\n";

    cout << "<br><a href=\"/hw2/form.html\">Back to Form</a>\n";
    cout << "</body>\n</html>" << endl;

    return 0;
}