#!/usr/bin/python3
import os
import cgi
import uuid
import http.cookies

# 1. Parse incoming form data
form = cgi.FieldStorage()
input_name = form.getvalue('username')

# 2. Session Initialization
# Generate a new ID and cookie if a name is provided
session_id = str(uuid.uuid4())
if input_name:
    # Save data to server-side file
    with open(f"/tmp/python_sess_{session_id}", "w") as f:
        f.write(input_name)
    # Set cookie for the browser
    print(f"Set-Cookie: PY_SESSID={session_id}; Path=/")

# 3. Output Headers
print("Content-Type: text/html\n")

# 4. UI
print("<html><body><h1>Python Sessions Page 1</h1>")
if input_name:
    print(f"<p><b>Name:</b> {input_name} (Saved to session)</p>")
else:
    print("<p><b>Name:</b> You do not have a name set</p>")

print('<br>')
print('<a href="sessions2-python.py">Session Page 2</a><br>')
print('<a href="/hw2/index-state.html">Back to State Entry</a>')

print('<form style="margin-top:30px" action="destroy-session-python.py" method="POST">')
print('<button type="submit">Destroy Session</button></form>')
print("</body></html>")