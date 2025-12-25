<?php
require_once '../db.php';

header('Content-Type: application/json');

$locationId = isset($_GET['location']) ? (int)$_GET['location'] : 0;
$mainId     = isset($_GET['main_id']) ? (int)$_GET['main_id'] : 0;

function generateRevenueCode(mysqli $con, int $locationId, int $mainId = 0): string {
    if ($mainId === 0) {
        $res = mysqli_query($con, "
            SELECT MAX(CAST(SUBSTRING(revinue_code, 2) AS UNSIGNED)) AS max_num
            FROM revinue_code
            WHERE locaton_id = $locationId AND main_cat_id = 0
        ");
        $row = mysqli_fetch_assoc($res);
        $next = (int)($row['max_num'] ?? 0) + 1;
        return 'S' . $next;
    }

    $parentRes = mysqli_query($con, "
        SELECT revinue_code
        FROM revinue_code
        WHERE r_id = $mainId AND locaton_id = $locationId
    ");
    $parent = mysqli_fetch_assoc($parentRes);
    if (!$parent) {
        return '';
    }
    $parentCode = $parent['revinue_code'];

    $res = mysqli_query($con, "
        SELECT MAX(CAST(SUBSTRING_INDEX(revinue_code, '.', -1) AS UNSIGNED)) AS max_sub
        FROM revinue_code
        WHERE locaton_id = $locationId AND main_cat_id = $mainId
    ");
    $row = mysqli_fetch_assoc($res);
    $next = (int)($row['max_sub'] ?? 0) + 1;
    return $parentCode . '.' . $next;
}

$code = ($locationId > 0) ? generateRevenueCode($con, $locationId, $mainId) : '';
echo json_encode(['code' => $code]);
exit;
