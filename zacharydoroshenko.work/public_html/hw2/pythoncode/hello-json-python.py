#!/usr/bin/python3
import os
import json
from datetime import datetime

# 1. Required CGI Headers for JSON
print("Cache-Control: no-cache")
print("Content-Type: application/json\n")

# 2. Collect the data
now = datetime.now()
date_string = now.strftime("%Y-%m-%d %H:%M:%S")
address = os.environ.get('REMOTE_ADDR', '127.0.0.1')

# 3. Create a dictionary (like a Perl hash)
data = {
    "title": "Hello from Python!",
    "heading": "Hello, Python JSON World!",
    "message": "This page was generated with the Python programming language",
    "time": date_string,
    "IP": address
}

# 4. Convert dictionary to JSON string and print
print(json.dumps(data))