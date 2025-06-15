<?php
// get_inventory.php
require_once 'includes/db.php'; // adjust path if needed

header('Content-Type: application/json');

// Handle pagination
$limit = $_GET['length'] ?? 25;
$offset = $_GET['start'] ?? 0;

// Handle search
$search = $_GET['search']['value'] ?? '';

// Base query
$sql = "SELECT * FROM inventory";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE item_name LIKE ?";
    $params[] = "%$search%";
}

$sql .= " LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total record count
$totalRecords = $pdo->query("SELECT COUNT(*) FROM inventory")->fetchColumn();

// Get filtered count
if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE item_name LIKE ?");
    $stmt->execute(["%$search%"]);
    $filteredRecords = $stmt->fetchColumn();
} else {
    $filteredRecords = $totalRecords;
}

// Return JSON
echo json_encode([
    "draw" => intval($_GET['draw'] ?? 1),
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $filteredRecords,
    "data" => $data
]);
