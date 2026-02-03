#!/usr/bin/python3
import os, http.cookies

cookie = http.cookies.SimpleCookie(os.environ.get("HTTP_COOKIE"))
sid = cookie["PY_SESSID"].value if "PY_SESSID" in cookie else None

if sid:
    sess_file = f"/tmp/python_sess_{sid}"
    if os.path.exists(sess_file):
        os.remove(sess_file)

# Expire the cookie by setting max-age to 0
print("Set-Cookie: PY_SESSID=; Max-Age=0; Path=/")
print("Content-type: text/html\n")
print("<h1>Python Session Destroyed</h1>")
print("<a href='python-sessions-1.py'>Back to Page 1</a>")