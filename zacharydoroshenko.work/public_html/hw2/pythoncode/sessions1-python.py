#!/usr/bin/python3
import os
import cgi
import uuid # Used to create a unique Session ID

# 1. Parse incoming data
form = cgi.FieldStorage()
input_name = form.getvalue('username')

# 2. Session Logic: If a name was submitted, save it
session_id = str(uuid.uuid4()) # Create a random unique ID
if input_name:
    # Save the name to a file in /tmp
    with open(f"/tmp/python_sess_{session_id}", "w") as f:
        f.write(input_name)
    # Tell the browser to store this ID in a cookie
    print(f"Set-Cookie: PY_SESSID={session_id}; Path=/")

# 3. Output Headers
print("Content-Type: text/html\n")

# 4. Display Logic
print("<html><body><h1>Python Sessions Page 1</h1>")
if input_name:
    print(f"<p><b>Name:</b> {input_name} (Saved to session!)</p>")
else:
    print("<p><b>Name:</b> You do not have a name set</p>")

print('<br>')
print('<a href="sessions2-python.py">Go to Session Page 2</a><br>')
print('<a href="/hw2/index-state.html">Back to State Entry</a>')
print("</body></html>")