<?php

header("Cache-Control: no-cache");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hello CGI World! This is Zachary!</title>
</head>
<body>
    <h1>Hello HTML World</h1>
    <hr/>
    <p>Hello!</p>
    <p>This page was generated with the PHP programming language.</p>

    <?php

    $date = date('l, F j, Y H:i:s');
    echo "<p>This program was generated at: $date</p>";

    $address = $_SERVER['REMOTE_ADDR'];
    echo "<p>Your current IP Address is: $address</p>";
    ?>
</body>
</html>