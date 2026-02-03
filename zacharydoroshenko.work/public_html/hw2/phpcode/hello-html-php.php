<?php
// PHP handles the Content-Type header automatically, 
// but we can set Cache-Control manually.
header("Cache-Control: no-cache");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hello CGI World! This is Zachary!</title>
</head>
<body>
    <h1 align="center">Hello HTML World</h1>
    <hr/>
    <p>Hello from Zachary and Team!</p>
    <p>This page was generated with the PHP programming language.</p>

    <?php
    // Date and Time
    $date = date('l, F j, Y H:i:s');
    echo "<p>This program was generated at: $date</p>";

    // IP Address
    $address = $_SERVER['REMOTE_ADDR'];
    echo "<p>Your current IP Address is: $address</p>";
    ?>
</body>
</html>