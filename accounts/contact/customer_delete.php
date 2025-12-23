<?php
include("../../db.php");
include("../../auth.php");

header('Content-Type: application/json');

$c_id = isset($_POST['c_id']) ? (int)$_POST['c_id'] : 0;
$new_status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
if ($new_status !== 0 && $new_status !== 1) {
    $new_status = 0;
}
if ($c_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid customer.']);
    exit;
}

$sql = "UPDATE accounts_manage_customer SET status = ? WHERE c_id = ? AND location_id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("iii", $new_status, $c_id, $location_id);
$ok = $stmt->execute();

if ($ok) {
    if (function_exists('UserLog')) {
        $actionText = ($new_status === 1) ? 'activated' : 'inactivated';
        UserLog('1', 'Customer status changed', "Customer ID $c_id $actionText");
    }
    $msg = ($new_status === 1) ? 'Customer activated.' : 'Customer inactivated.';
    echo json_encode(['success' => true, 'message' => $msg]);
} else {
    echo json_encode(['success' => false, 'message' => 'Status change failed.']);
}
