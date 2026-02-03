#!/usr/bin/perl
use strict;
use warnings;

print "Cache-Control: no-cache\n";
print "Content-type: text/html\n\n";

# 1. Gather Metadata
my $protocol   = $ENV{SERVER_PROTOCOL} || "N/A";
my $method     = $ENV{REQUEST_METHOD}   || "N/A";
my $query      = $ENV{QUERY_STRING}     || "N/A";
my $hostname   = $ENV{HTTP_HOST}        || $ENV{SERVER_NAME};
my $user_agent = $ENV{HTTP_USER_AGENT}  || "Unknown";
my $ip_address = $ENV{REMOTE_ADDR}       || "Unknown";
my $date_time  = localtime();

# 2. Read Message Body (for POST/PUT)
my $form_data = "";
if ($ENV{CONTENT_LENGTH}) {
    read(STDIN, $form_data, $ENV{CONTENT_LENGTH});
}

print <<END;
<!DOCTYPE html>
<html>
<head><title>Echo Response: $method</title></head>
<body>
    <h1>Echo Response ($method)</h1>
    <hr>
    <p><b>Hostname:</b> $hostname</p>
    <p><b>Date/Time:</b> $date_time</p>
    <p><b>User Agent:</b> $user_agent</p>
    <p><b>Your IP:</b> $ip_address</p>
    <hr>
    <p><b>Protocol:</b> $protocol</p>
    <p><b>Method:</b> $method</p>
    <p><b>Query String:</b> $query</p>
    <p><b>Message Body:</b> $form_data</p>
    <br>
    <a href="/echo-form.html">Back to Form</a>
</body>
</html>
END