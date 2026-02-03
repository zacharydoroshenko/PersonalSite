#!/usr/bin/python3
import os
import sys
import cgi

# 1. Parse incoming data or cookies
form = cgi.FieldStorage()
input_name = form.getvalue('username')
cookie_str = os.environ.get('HTTP_COOKIE', '')

# Logic to find or create Session ID would go here...
session_id = "some_id" # Placeholder for your session logic
session_path = f"/tmp/python_sess_{session_id}"

# 2. Check if we have a name (from POST or File)
display_name = None
if input_name:
    display_name = input_name
    # Save display_name to session_path here
elif os.path.exists(session_path):
    with open(session_path, 'r') as f:
        display_name = f.read()

# 3. Output Headers
print("Content-Type: text/html\n")

# 4. Conditional UI
print("<html><body><h1>Python Sessions Page 1</h1>")
if display_name:
    print(f"<p><b>Name:</b> {display_name}</p>")
    print('<a href="sessions2-python.py">Session Page 2</a><br>')
    print('<form action="destroy-session-python.py" method="POST"><button type="submit">Destroy Session</button></form>')
else:
    print("<p>No session found. Please enter a name first.</p>")
    print('<a href="/hw2/index-state.html">Back to State Entry</a>')
print("</body></html>")