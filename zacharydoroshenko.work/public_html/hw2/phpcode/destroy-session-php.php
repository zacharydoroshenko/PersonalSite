<?php
session_start();
session_unset();
session_destroy();
// Use the absolute path to go back to the HTML dashboard
header("Location: /hw2/index-state.html");
exit();
?>