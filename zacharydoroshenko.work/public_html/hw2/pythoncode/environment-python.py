#!/usr/bin/python3
import os

print("Content-type: text/html\n\n")
print("<h1>Python Environment Variables</h1><hr>")

for key in sorted(os.environ.keys()):
    print(f"<b>{key}:</b> {os.environ[key]}<br>")