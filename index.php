<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

echo "Welcome, " . ($_SESSION['username'] ?? 'Guest') . ". Role: " . ($_SESSION['role'] ?? 'None');



include 'includes/header.php';
?>

<h2>Index</h2>
<p>Welcome, <?php echo $_SESSION['username']; ?>!</p>
