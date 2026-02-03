#!/usr/bin/python3
import os
import http.cookies

# 1. Retrieve Session ID from Cookie
cookie = http.cookies.SimpleCookie(os.environ.get("HTTP_COOKIE"))
sid = cookie["PY_SESSID"].value if "PY_SESSID" in cookie else None

username = "You do not have a name set"

# 2. Read name from the server-side file
if sid:
    sess_file = f"/tmp/python_sess_{sid}"
    if os.path.exists(sess_file):
        with open(sess_file, "r") as f:
            username = f.read()

# 3. Output HTML
print("Content-type: text/html\n")
print("<html><body><h1>Python Sessions Page 2</h1>")
print(f"<p><b>Name:</b> {username}</p>")

print("<br>")
print("<a href='sessions1-python.py'>Back to Page 1</a><br>")
print("<a href='/hw2/index-state.html'>Back to State Entry</a><br>")

print("<form style='margin-top:30px' action='destroy-session-python.py' method='POST'>")
print("<button type='submit'>Destroy Session</button></form>")
print("</body></html>")