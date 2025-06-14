<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

echo "Welcome, " . ($_SESSION['username'] ?? 'Guest') . ". Role: " . ($_SESSION['role'] ?? 'None');



include 'includes/header.php';
?>

<h2>Dashboard (Admin Only)</h2>
<p>Welcome, <?php echo $_SESSION['username']; ?>!</p>
