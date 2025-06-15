<?php
// This file connects to your database and provides the $pdo variable
require_once 'includes/db.php';

// Basic server-side validation
if (empty($_POST['item_name']) || !isset($_POST['dp'], $_POST['rp'], $_POST['mrp'], $_POST['quantity'])) {
    http_response_code(400); // Bad Request
    echo "Error: Missing required item data.";
    exit;
}

// Assign POST variables
$item_name = $_POST['item_name'];
// Use null coalescing operator (??) to default empty optional fields to NULL
$dp = !empty($_POST['dp']) ? $_POST['dp'] : null;
$rp = !empty($_POST['rp']) ? $_POST['rp'] : null;
$mrp = !empty($_POST['mrp']) ? $_POST['mrp'] : null;
$quantity = $_POST['quantity'];

try {
    // SQL statement to insert the new item
    $sql = "INSERT INTO inventory (item_name, dp, rp, mrp, quantity) VALUES (:item_name, :dp, :rp, :mrp, :quantity)";
    
    // Prepare the statement
    $stmt = $pdo->prepare($sql);
    
    // Execute the statement with the form data
    $stmt->execute([
        ':item_name' => $item_name,
        ':dp' => $dp,
        ':rp' => $rp,
        ':mrp' => $mrp,
        ':quantity' => $quantity
    ]);
    
    echo "Item added successfully. ID: " . $pdo->lastInsertId();

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    // Provide a more detailed error for debugging
    echo "Error: Could not add item. " . $e->getMessage();
}
?>