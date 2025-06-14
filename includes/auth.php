<?php
#require_once 'db.php'; // For DB access if needed (optional)
require_once 'load_env.php'; // Load the .env variables




session_start();

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Get admins and employees from .env
    $admins = explode(',', $_ENV['ADMINS']);
    $employees = explode(',', $_ENV['EMPLOYEES']);

    $found = false;
    $role = ''; 

    // Check if user is an admin
    foreach ($admins as $admin) {
        list($adminUser, $adminPass) = explode(':', $admin);
        if ($username === $adminUser && $password === $adminPass) {
            $found = true;
            $role = 'admin';
            break;
        }
    }

    // Check if user is an employee
    if (!$found) {
        foreach ($employees as $employee) {
            list($empUser, $empPass) = explode(':', $employee);
            if ($username === $empUser && $password === $empPass) {
                $found = true;
                $role = 'employee';
                break;
            }
        }
    }

    // If valid login, set session
    if ($found) {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        header('Location: /biiApp/index.php'); // Adjust path if needed F:\new xampp\htdocs\biiApp
        exit();
    } else {
        $error = "Invalid credentials";
    }
}
?>
