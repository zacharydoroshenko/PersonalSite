#!/usr/bin/python3
import os
from datetime import datetime

# Headers
print("Cache-Control: no-cache")
print("Content-Type: text/html\n") # Note: Python handles the extra \n well

# HTML Content
print("<!DOCTYPE html>")
print("<html>")
print("<head>")
print("<title>Hello CGI World! This is Zachary!</title>")
print("</head>")
print("<body>")

print("<h1 align='center'>Hello HTML World</h1><hr/>")
print("<p>Hello!</p>")
print("<p>This page was generated with the Python programming language.</p>")

# Date and Time
now = datetime.now()
date_string = now.strftime("%A, %B %d, %Y %H:%M:%S")
print(f"<p>This program was generated at: {date_string}</p>")

# IP Address
address = os.environ.get('REMOTE_ADDR', 'IP Not Found')
print(f"<p>Your current IP Address is: {address}</p>")

print("</body>")
print("</html>")