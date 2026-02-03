<h1>PHP Environment Variables</h1><hr>
<?php
ksort($_SERVER); // Sort alphabetically
foreach ($_SERVER as $key => $value) {
    echo "<b>$key:</b> $value<br>";
}
?>