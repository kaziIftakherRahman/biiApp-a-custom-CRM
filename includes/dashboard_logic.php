<?php
session_start();
// === FIX: SET TIMEZONE TO BANGLADESH STANDARD TIME UTC +6 ===
date_default_timezone_set('Asia/Dhaka');

require_once 'includes/db.php'; // Your PDO connection file

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access Denied']);
    exit;
}

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

try {
    // The rest of the file is exactly the same as before.
    // The timezone setting at the top will apply to all database interactions.
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        switch ($action) {
            case 'get_stats':
                $sql = "
                    SELECT
                        (SELECT COALESCE(SUM(CASE WHEN TRIM(type) IN ('sale', 'manual_cash_in', 'due_cleared') THEN amount WHEN TRIM(type) = 'manual_cash_out' THEN -amount ELSE 0 END), 0) FROM transactions) AS totalBalance,
                        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE TRIM(type) IN ('sale', 'manual_cash_in', 'due_cleared')) AS totalMoneyIn,
                        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE TRIM(type) = 'manual_cash_out') AS totalMoneyOut,
                        (SELECT COALESCE(SUM(due_amount), 0) FROM dues WHERE status = 'outstanding') AS totalDue,
                        (SELECT COALESCE(SUM(CASE WHEN TRIM(type) IN ('sale', 'manual_cash_in', 'due_cleared') THEN amount WHEN TRIM(type) = 'manual_cash_out' THEN -amount ELSE 0 END), 0) FROM transactions WHERE DATE(created_at) = CURDATE()) AS todayNetChange
                ";
                $stmt = $pdo->query($sql);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($stats) { echo json_encode($stats); } else { echo json_encode(['totalBalance' => 0, 'totalMoneyIn' => 0, 'totalMoneyOut' => 0, 'totalDue' => 0, 'todayNetChange' => 0]); }
                break;

            case 'get_transactions':
                $limit = 10;
                $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
                $offset = ($page - 1) * $limit;
                $params = [];
                $baseSql = "FROM transactions";
                $whereClauses = [];
                if (!empty($_GET['filter_type'])) {
                    switch ($_GET['filter_type']) {
                        case 'cash_in': $whereClauses[] = "TRIM(type) IN ('sale', 'manual_cash_in', 'due_cleared')"; break;
                        case 'cash_out': $whereClauses[] = "TRIM(type) = 'manual_cash_out'"; break;
                        case 'due': $whereClauses[] = "TRIM(type) IN ('due_added', 'due_cleared')"; break;
                    }
                }
                if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                     $whereClauses[] = "DATE(created_at) BETWEEN ? AND ?";
                     $params[] = $_GET['start_date'];
                     $params[] = $_GET['end_date'];
                }
                $whereSql = "";
                if (!empty($whereClauses)) {
                    $whereSql = " WHERE " . implode(' AND ', $whereClauses);
                }
                $countStmt = $pdo->prepare("SELECT COUNT(*) " . $baseSql . $whereSql);
                $countStmt->execute($params);
                $totalRecords = (int)$countStmt->fetchColumn();
                $totalPages = ceil($totalRecords / $limit);
                $dataStmt = $pdo->prepare("SELECT id, type, amount, description, user_id, created_at " . $baseSql . $whereSql . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
                foreach ($params as $key => $value) { $dataStmt->bindValue($key + 1, $value); }
                $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $dataStmt->execute();
                $transactions = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['pagination' => ['currentPage' => $page, 'totalPages' => $totalPages, 'totalRecords' => $totalRecords], 'transactions' => $transactions]);
                break;
            case 'get_rifat_transactions':
                $stmt = $pdo->query("SELECT created_at, type, amount FROM transactions WHERE TRIM(user_id) = 'rifat25' ORDER BY created_at DESC");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
            case 'get_outstanding_dues':
                $stmt = $pdo->query("SELECT id, company_name, due_amount FROM dues WHERE status = 'outstanding' AND due_amount > 0 ORDER BY created_at ASC");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = $_POST;
        $user_id = $_SESSION['username'];
        $response = ['success' => false, 'message' => 'Invalid action'];
        switch ($action) {
            case 'add_balance': $stmt = $pdo->prepare("INSERT INTO transactions (type, amount, description, user_id) VALUES ('manual_cash_in', ?, ?, ?)"); $stmt->execute([$data['amount'], 'Manual balance add', $user_id]); $response = ['success' => true, 'message' => 'Balance added successfully.']; break;
            case 'out_balance': $stmt = $pdo->prepare("INSERT INTO transactions (type, amount, description, user_id) VALUES ('manual_cash_out', ?, ?, ?)"); $stmt->execute([$data['amount'], 'Manual balance out', $user_id]); $response = ['success' => true, 'message' => 'Balance removed successfully.']; break;
            case 'add_due': $stmt_due = $pdo->prepare("INSERT INTO dues (company_name, due_amount) VALUES (?, ?)"); $stmt_due->execute([$data['company'], $data['amount']]); $description = "Due added for " . $data['company']; $stmt_trans = $pdo->prepare("INSERT INTO transactions (type, amount, description, user_id) VALUES ('due_added', ?, ?, ?)"); $stmt_trans->execute([$data['amount'], $description, $user_id]); $response = ['success' => true, 'message' => 'Due added successfully.']; break;
            case 'pay_due': $due_id = $data['due_id']; $payment_amount = $data['amount']; $pdo->beginTransaction(); $stmt = $pdo->prepare("SELECT company_name, due_amount FROM dues WHERE id = ?"); $stmt->execute([$due_id]); $due = $stmt->fetch(PDO::FETCH_ASSOC); if (!$due || $payment_amount <= 0 || $payment_amount > $due['due_amount']) { $pdo->rollBack(); $response = ['success' => false, 'message' => 'Invalid payment amount or due ID.']; } else { $new_due_amount = $due['due_amount'] - $payment_amount; $stmt_update = $pdo->prepare("UPDATE dues SET due_amount = ? WHERE id = ?"); $stmt_update->execute([$new_due_amount, $due_id]); if ($new_due_amount <= 0) { $stmt_status = $pdo->prepare("UPDATE dues SET status = 'paid' WHERE id = ?"); $stmt_status->execute([$due_id]); } $description = "Payment for due from " . $due['company_name']; $stmt_trans = $pdo->prepare("INSERT INTO transactions (type, amount, description, user_id) VALUES ('due_cleared', ?, ?, ?)"); $stmt_trans->execute([$payment_amount, $description, $user_id]); $pdo->commit(); $response = ['success' => true, 'message' => 'Due payment recorded successfully.']; } break;
        }
        echo json_encode($response);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}
?>