#!/usr/bin/python3
import os
import http.cookies

# 1. Identify the session
cookie = http.cookies.SimpleCookie(os.environ.get("HTTP_COOKIE"))
sid = cookie["PY_SESSID"].value if "PY_SESSID" in cookie else None

# 2. Remove the server-side file
if sid:
    sess_file = f"/tmp/python_sess_{sid}"
    if os.path.exists(sess_file):
        os.remove(sess_file)

# 3. Expire the cookie and provide feedback
print("Set-Cookie: PY_SESSID=; Max-Age=0; Path=/")
print("Content-type: text/html\n")
print("<html><body><h1>Session Destroyed</h1>")
print("<a href='/hw2/index-state.html'>Back to State Entry</a><br />")
print("<a href='sessions1-python.py'>Back to Page 1</a><br />")
print("<a href='sessions2-python.py'>Back to Page 2</a>")
print("</body></html>")