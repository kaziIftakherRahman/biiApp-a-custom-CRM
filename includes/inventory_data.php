<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Handle form action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
    $name = $_POST['name'];
    $quantity = $_POST['quantity'];
    $rp = $_POST['rp'];

    echo "<pre>";
    echo "Received Item:\n";
    echo "Name: $name\n";
    echo "Quantity: $quantity\n";
    echo "Retail Price: $rp\n";
    echo "</pre>";
}
