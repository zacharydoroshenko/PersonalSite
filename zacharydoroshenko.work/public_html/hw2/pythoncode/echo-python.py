#!/usr/bin/python3
import os
import sys
from datetime import datetime


print("Cache-Control: no-cache")
print("Content-type: text/html\n")


protocol   = os.environ.get('SERVER_PROTOCOL', 'N/A')
method     = os.environ.get('REQUEST_METHOD', 'N/A')
query      = os.environ.get('QUERY_STRING', 'N/A')
hostname   = os.environ.get('HTTP_HOST', os.environ.get('SERVER_NAME', 'Unknown'))
user_agent = os.environ.get('HTTP_USER_AGENT', 'Unknown')
ip_address = os.environ.get('REMOTE_ADDR', 'Unknown')
date_time  = datetime.now().strftime("%Y-%m-%d %H:%M:%S")


content_length = int(os.environ.get('CONTENT_LENGTH', 0))
body = sys.stdin.read(content_length) if content_length > 0 else ""


print(f"""<!DOCTYPE html>
<html>
<head><title>Python General Request Echo</title></head>
<body>
    <h1 align="center">Python General Request Echo</h1>
    <hr>
    <p><b>Hostname:</b> {hostname}</p>
    <p><b>Date/Time:</b> {date_time}</p>
    <p><b>User Agent:</b> {user_agent}</p>
    <p><b>Your IP:</b> {ip_address}</p>
    <hr>
    <p><b>HTTP Protocol:</b> {protocol}</p>
    <p><b>HTTP Method:</b> {method}</p>
    <p><b>Query String:</b> {query}</p>
    <p><b>Message Body:</b> {body if body else "(empty)"}</p>
    <br>
    <a href="/hw2/form.html">Back to Form</a>
</body>
</html>""")