<?php
require_once 'includes/db.php'; // Creates the $pdo variable

// Check if all required POST data is received
if (isset($_POST['id'], $_POST['column'], $_POST['value'])) {
    $id = $_POST['id'];
    $column = $_POST['column'];
    $value = $_POST['value'];

    // Whitelist of editable columns to prevent SQL injection
    $allowed_columns = ['item_name', 'dp', 'rp', 'mrp', 'quantity'];

    if (in_array($column, $allowed_columns)) {
        try {
            // Use a prepared statement to safely update the database
            $sql = "UPDATE inventory SET `$column` = :value WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':value' => $value,
                ':id' => $id
            ]);

            // Check if any row was actually updated
            if ($stmt->rowCount() > 0) {
                echo "Update successful.";
            } else {
                echo "No changes made.";
            }
        } catch (PDOException $e) {
            echo "Error: Update failed. " . $e->getMessage();
        }
    } else {
        echo "Error: Invalid column specified for update.";
    }
} else {
    echo "Error: Missing data for update.";
}
?>