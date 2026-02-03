#!/usr/bin/python3
import os, http.cookies

# 1. Retrieve Session ID from Cookie
cookie = http.cookies.SimpleCookie(os.environ.get("HTTP_COOKIE"))
sid = cookie["PY_SESSID"].value if "PY_SESSID" in cookie else None

username = None
if sid:
    sess_file = f"/tmp/python_sess_{sid}"
    if os.path.exists(sess_file):
        with open(sess_file, "r") as f:
            username = f.read()

# 2. Output HTML
print("Content-type: text/html\n")
print("<html><head><title>Python Sessions</title></head><body>")
print("<h1>Python Sessions Page 2</h1>")

if username:
    print(f"<p><b>Name:</b> {username}</p>")
else:
    print("<p><b>Name:</b> You do not have a name set</p>")

print("<br>")
print("<a href='sessions1-python.py'>Session Page 1</a><br>")
print("<a href='/index-state.html'>Back to State Entry</a><br>")

print("<form style='margin-top:30px' action='destroy-session-python.py' method='POST'>")
print("<button type='submit'>Destroy Session</button></form>")
print("</body></html>")