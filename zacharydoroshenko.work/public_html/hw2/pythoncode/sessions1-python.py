#!/usr/bin/python3
import os
import cgi

# 1. Parse incoming data
form = cgi.FieldStorage()
input_name = form.getvalue('username')

# 2. Output Headers
print("Content-Type: text/html\n")

# 3. Simple UI
print("<html><body><h1>Python Sessions Page 1</h1>")

if input_name:
    print(f"<p><b>Name:</b> {input_name}</p>")
else:
    print("<p><b>Name:</b> You do not have a name set</p>")

print('<br>')
print('<a href="sessions2-python.py">Session Page 2</a><br>')
print('<a href="/hw2/index-state.html">Back to State Entry</a><br>')

print('<form style="margin-top:30px" action="destroy-session-python.py" method="POST">')
print('<button type="submit">Destroy Session</button></form>')
print("</body></html>")