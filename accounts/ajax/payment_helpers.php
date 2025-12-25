<?php
require_once '../../db.php';
require_once '../../auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$bankId = isset($_GET['bank_id']) ? (int)$_GET['bank_id'] : 0;
$locationFilter = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;
if ($locationFilter === 0) {
    $locationFilter = isset($location_id) && $location_id !== '' ? (int)$location_id : 0;
}

if ($action === 'voucher_next' && $bankId > 0) {
    $res = mysqli_query($con, "
        SELECT MAX(CAST(voutcher_number AS UNSIGNED)) AS mx
        FROM bank_payment
        WHERE bank_id = $bankId AND location_id = $locationFilter
    ");
    $row = $res ? mysqli_fetch_assoc($res) : ['mx' => 0];
    $next = (int)($row['mx'] ?? 0) + 1;
    echo json_encode(['voucher' => $next]);
    exit;
}

if ($action === 'cheque_next' && $bankId > 0) {
    $res = mysqli_query($con, "
        SELECT MAX(cheque_number) AS mx
        FROM bank_payment
        WHERE bank_id = $bankId AND location_id = $locationFilter
    ");
    $row = $res ? mysqli_fetch_assoc($res) : ['mx' => 0];
    $next = (int)($row['mx'] ?? 0) + 1;
    echo json_encode(['cheque' => $next]);
    exit;
}

echo json_encode(['error' => 'Invalid request']);
