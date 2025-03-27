<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] != "faculty") {
    header("Location: index.html");
    exit();
}
echo "<h2>Welcome, Faculty!</h2>";
?>
<a href="logout.php">Logout</a>
