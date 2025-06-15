<?php
require_once 'includes/db.php'; // Creates the $pdo variable

// Check if the 'ids' array is sent
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = $_POST['ids'];

    if (empty($ids)) {
        echo "No IDs provided.";
        exit;
    }

    try {
        // Create a string of question mark placeholders, e.g., ?,?,?
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Prepare the DELETE statement with the IN clause
        $sql = "DELETE FROM inventory WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);

        // Execute the statement, passing the array of IDs directly
        $stmt->execute($ids);

        echo $stmt->rowCount() . " rows deleted successfully.";

    } catch (PDOException $e) {
        echo "Error: Delete failed. " . $e->getMessage();
    }
} else {
    echo "Error: Invalid or no data received.";
}
?>