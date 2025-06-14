<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

include 'includes/header.php';
?>

<h2>Invoice</h2>
<p>This page will handle invoice creation.</p>
