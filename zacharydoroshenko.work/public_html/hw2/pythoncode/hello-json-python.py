#!/usr/bin/python3
import os
import json
from datetime import datetime


print("Cache-Control: no-cache")
print("Content-Type: application/json\n")


now = datetime.now()
date_string = now.strftime("%Y-%m-%d %H:%M:%S")
address = os.environ.get('REMOTE_ADDR', '127.0.0.1')


data = {
    "title": "Hello from Python!",
    "heading": "Hello, Python JSON World!",
    "message": "This page was generated with the Python programming language",
    "time": date_string,
    "IP": address
}


print(json.dumps(data))