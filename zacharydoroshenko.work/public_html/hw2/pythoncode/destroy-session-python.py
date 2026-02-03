#!/usr/bin/python3
import os
import http.cookies

# 1. Identify the session from the cookie
cookie = http.cookies.SimpleCookie(os.environ.get("HTTP_COOKIE"))
sid = cookie["PY_SESSID"].value if "PY_SESSID" in cookie else None

# 2. Physically remove the session file from /tmp/
if sid:
    sess_file = f"/tmp/python_sess_{sid}"
    if os.path.exists(sess_file):
        os.remove(sess_file)

# 3. Expire the cookie and redirect
print("Set-Cookie: PY_SESSID=; Max-Age=0; Path=/")
print("Content-type: text/html\n")
print("<html><body>")
print("<h1>Python Session Destroyed</h1>")
print("<p>The server-side state file has been deleted and the cookie cleared.</p>")
print("<a href='sessions1-python.py'>Back to Page 1</a><br>")
print("<a href='/hw2/index-state.html'>Back to Dashboard</a>")
print("</body></html>")