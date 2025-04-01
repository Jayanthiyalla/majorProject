<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

if ($conn) {
    echo "Database Connection Successful!";
} else {
    echo "Database Connection Failed!";
}
?>
