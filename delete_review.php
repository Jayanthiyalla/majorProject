<?php
include 'config.php';

$id = $_GET['id'];
$conn->query("DELETE FROM reviews WHERE id=$id");
header("Location: view_reviews.php");
?>
