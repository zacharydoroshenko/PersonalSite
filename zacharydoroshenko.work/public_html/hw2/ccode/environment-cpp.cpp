#include <iostream>
using namespace std;

int main(int argc, char** argv, char** envp) {
    cout << "Content-type: text/html\n\n";
    cout << "<h1>C++ Environment Variables</h1><hr>";

    for (char** env = envp; *env != 0; env++) {
        char* thisEnv = *env;
        cout << thisEnv << "<br>";
    }
    return 0;
}