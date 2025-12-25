<?php
require_once '../../db.php';
require_once '../../auth.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'msg' => 'No input']);
    exit;
}

$type   = $input['type'] ?? '';
$year   = isset($input['year']) ? (int)$input['year'] : 0;
$r_id   = isset($input['r_id']) ? (int)$input['r_id'] : 0;
$amount = isset($input['amount']) ? (float)$input['amount'] : 0;

// Resolve location_id from payload, session/header, or cookie fallback
$loc = isset($input['location_id']) ? (int)$input['location_id'] : 0;
if ($loc === 0) {
    $loc = isset($location_id) ? (int)$location_id : 0;
}
if ($loc === 0 && isset($_COOKIE['client_cook'])) {
    $selected_client = mysqli_real_escape_string($con, $_COOKIE['client_cook']);
    $cRes = mysqli_query($con, "SELECT c_id FROM client_registration WHERE md5_client='$selected_client' LIMIT 1");
    if ($cRes && mysqli_num_rows($cRes) > 0) {
        $cRow = mysqli_fetch_assoc($cRes);
        $loc = (int)$cRow['c_id'];
    }
}

function respondError($msg) {
    http_response_code(200);
    echo json_encode(['success' => false, 'msg' => $msg]);
    exit;
}

if ($loc === 0) {
    respondError('No location selected');
}

if ($type === 'allocation') {
    $ex_id = isset($input['ex_id']) ? (int)$input['ex_id'] : 0;
    if ($year === 0 || $r_id === 0 || $ex_id === 0) {
        respondError('Missing fields');
    }
    $chk = mysqli_query($con, "SELECT id FROM budget_allocation WHERE year=$year AND r_id=$r_id AND ex_id=$ex_id AND location_id=$loc");
    if ($chk === false) {
        respondError('DB error: ' . mysqli_error($con));
    }
    if (mysqli_num_rows($chk) > 0) {
        if (!mysqli_query($con, "UPDATE budget_allocation SET amount=$amount WHERE year=$year AND r_id=$r_id AND ex_id=$ex_id AND location_id=$loc")) {
            respondError('DB error: ' . mysqli_error($con));
        }
    } else {
        if (!mysqli_query($con, "INSERT INTO budget_allocation (location_id, year, r_id, ex_id, amount) VALUES ($loc, $year, $r_id, $ex_id, $amount)")) {
            respondError('DB error: ' . mysqli_error($con));
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($type === 'opening') {
    if ($year === 0 || $r_id === 0) {
        respondError('Missing fields');
    }
    $chk = mysqli_query($con, "SELECT id FROM budget_opening_balance WHERE year=$year AND r_id=$r_id AND location_id=$loc");
    if ($chk === false) {
        respondError('DB error: ' . mysqli_error($con));
    }
    if (mysqli_num_rows($chk) > 0) {
        if (!mysqli_query($con, "UPDATE budget_opening_balance SET op_balance=$amount WHERE year=$year AND r_id=$r_id AND location_id=$loc")) {
            respondError('DB error: ' . mysqli_error($con));
        }
    } else {
        if (!mysqli_query($con, "INSERT INTO budget_opening_balance (location_id, year, r_id, op_balance) VALUES ($loc, $year, $r_id, $amount)")) {
            respondError('DB error: ' . mysqli_error($con));
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

respondError('Invalid type');