#!/usr/bin/python3
import os
import cgi
import uuid
import http.cookies

form = cgi.FieldStorage()
input_name = form.getvalue('username')

cookie = http.cookies.SimpleCookie(os.environ.get("HTTP_COOKIE"))
sid = cookie["PY_SESSID"].value if "PY_SESSID" in cookie else None


if input_name:

    sid = str(uuid.uuid4())
    with open(f"/tmp/python_sess_{sid}", "w") as f:
        f.write(input_name)
    print(f"Set-Cookie: PY_SESSID={sid}; Path=/")
    display_name = input_name
elif sid:

    sess_file = f"/tmp/python_sess_{sid}"
    if os.path.exists(sess_file):
        with open(sess_file, "r") as f:
            display_name = f.read()
    else:
        display_name = None
else:
    display_name = None


print("Content-Type: text/html\n")


print("<html><body><h1>Python Sessions Page 1</h1>")
if display_name:
    print(f"<p><b>Name:</b> {display_name}</p>")
    print('<br><a href="sessions2-python.py">Go to Session Page 2</a><br>')
else:
    print("<p><b>Name:</b> You do not have a name set</p>")
    print('<a href="/hw2/index-state.html">Back to State Entry</a><br>')

print('<form style="margin-top:30px" action="destroy-session-python.py" method="POST">')
print('<button type="submit">Destroy Session</button></form>')
print("</body></html>")