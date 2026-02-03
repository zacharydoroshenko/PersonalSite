#!/usr/bin/python3
import os, uuid, http.cookies, cgi

# 1. Parse incoming data
form = cgi.FieldStorage()
username = form.getvalue("username")

# 2. Handle Cookie / Session ID
cookie = http.cookies.SimpleCookie(os.environ.get("HTTP_COOKIE"))
sid = cookie["PY_SESSID"].value if "PY_SESSID" in cookie else str(uuid.uuid4())

# 3. Save to server-side storage if username exists
sess_file = f"/tmp/python_sess_{sid}"
if username:
    with open(sess_file, "w") as f:
        f.write(username)
else:
    # If no new data, try to read existing data from the session file
    if os.path.exists(sess_file):
        with open(sess_file, "r") as f:
            username = f.read()

# 4. Output Headers and HTML
print(f"Set-Cookie: PY_SESSID={sid}; Path=/")
print("Content-type: text/html\n")
print("<html><head><title>Python Sessions</title></head><body>")
print("<h1>Python Sessions Page 1</h1>")

if username:
    print(f"<p><b>Name:</b> {username}</p>")
else:
    print("<p><b>Name:</b> You do not have a name set</p>")

print("<br>")
print("<a href='sessions2-python.py'>Session Page 2</a><br>")
print("<a href='/index-state.html'>Back to State Entry</a><br>")

print("<form style='margin-top:30px' action='destroy-session-python.py' method='POST'>")
print("<button type='submit'>Destroy Session</button></form>")
print("</body></html>")