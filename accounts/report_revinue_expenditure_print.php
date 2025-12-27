<?php
require('../db.php');
require('../auth.php');

// Location from cookie (same approach as accounts/header.php)
$selected_client = isset($_COOKIE['client_cook']) ? $_COOKIE['client_cook'] : '';
$location_id = 0;
$client_name = '';
$client_language = isset($_COOKIE['language_cook']) ? $_COOKIE['language_cook'] : 'English';
if ($selected_client !== '') {
    $sel_query = "SELECT * from client_registration where md5_client='$selected_client'";
    $result = mysqli_query($con, $sel_query);
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        $client_name = $row['client_name'];
        $location_id = (int)$row['c_id'];
    }
}

// Language helper
$primaryLanguage = 'english';
if (isset($client_language)) {
    $lang = strtolower($client_language);
    if (in_array($lang, ['tamil', 'sinhala'], true)) {
        $primaryLanguage = $lang;
    }
}

function incomeLabelLang($row, $lang) {
    if ($lang === 'tamil' && !empty($row['revinue_tamil'])) {
        return $row['revinue_code'] . ' - ' . $row['revinue_tamil'];
    }
    if ($lang === 'sinhala' && !empty($row['revinue_sinhala'])) {
        return $row['revinue_code'] . ' - ' . $row['revinue_sinhala'];
    }
    return $row['revinue_code'] . ' - ' . ($row['detail_of_revinue'] ?? '');
}
function formatZeroBlank($val) {
    return (float)$val == 0.0 ? '' : number_format((float)$val, 2);
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : 2025;
$incomeId = isset($_GET['income_account']) ? (int)$_GET['income_account'] : 1;

$summary = ['estimate' => 0.0, 'collection' => 0.0, 'spend' => 0.0];
$rows = [];
$totalDebit = 0.0;
$totalCredit = 0.0;
$endingBalance = 0.0;
$selectedIncomeRow = null;

if ($location_id > 0) {
    // Income account meta
    $incRes = mysqli_query($con, "
        SELECT r_id, revinue_code, detail_of_revinue, revinue_tamil, revinue_sinhala
        FROM revinue_code
        WHERE locaton_id = $location_id AND status = 1 AND r_id = $incomeId
        LIMIT 1
    ");
    $selectedIncomeRow = $incRes ? mysqli_fetch_assoc($incRes) : null;

    // Totals
    $allocRes = mysqli_query($con, "
        SELECT COALESCE(SUM(amount),0) AS total_alloc
        FROM budget_allocation
        WHERE year = $year AND location_id = $location_id AND r_id = $incomeId
    ");
    $alloc = $allocRes ? (float)(mysqli_fetch_assoc($allocRes)['total_alloc'] ?? 0) : 0.0;

    $openRes = mysqli_query($con, "
        SELECT COALESCE(SUM(op_balance),0) AS total_open
        FROM budget_opening_balance
        WHERE year = $year AND location_id = $location_id AND r_id = $incomeId
    ");
    $opening = $openRes ? (float)(mysqli_fetch_assoc($openRes)['total_open'] ?? 0) : 0.0;

    $colRes = mysqli_query($con, "
        SELECT COALESCE(SUM(credit),0) AS total_col
        FROM transaction
        WHERE location_id = $location_id
          AND status = 1
          AND income_account = $incomeId
          AND transaction_type = 'Receipt'
          AND YEAR(tr_date) = $year
    ");
    $collection = $colRes ? (float)(mysqli_fetch_assoc($colRes)['total_col'] ?? 0) : 0.0;

    $spendRes = mysqli_query($con, "
        SELECT COALESCE(SUM(credit),0) AS total_pay
        FROM transaction
        WHERE location_id = $location_id
          AND status = 1
          AND income_account = $incomeId
          AND transaction_type = 'Payment'
          AND YEAR(tr_date) = $year
    ");
    $spend = $spendRes ? (float)(mysqli_fetch_assoc($spendRes)['total_pay'] ?? 0) : 0.0;

    $summary['estimate'] = $alloc + $opening;
    $summary['collection'] = $collection;
    $summary['spend'] = $spend;

    // Detail rows
    $txnSql = "
        SELECT t.id, t.tr_date, t.transaction_type, t.voutcher_number, t.memo, t.credit, t.supplier_id,
               sup.supplier_name,
               bp.voutcher_number AS pay_voucher, bp.cheque_number AS pay_cheque,
               br.receipt_number AS receipt_no
        FROM transaction t
        LEFT JOIN manage_supplier sup ON sup.sup_id = t.supplier_id
        LEFT JOIN bank_payment bp ON bp.record_number = t.transaction_id AND bp.location_id = t.location_id
        LEFT JOIN bank_receipt br ON br.record_no = t.transaction_id AND br.location_id = t.location_id
        WHERE t.location_id = $location_id
          AND t.status = 1
          AND t.income_account = $incomeId
          AND t.transaction_type IN ('Receipt','Payment')
          AND t.credit > 0
          AND YEAR(t.tr_date) = $year
        ORDER BY t.tr_date ASC, (CASE WHEN t.transaction_type = 'Receipt' THEN t.credit ELSE 0 END) DESC, t.id ASC
    ";
    $txnRes = mysqli_query($con, $txnSql);
    $balance = 0.0;
    $sn = 1;
    if ($txnRes) {
        while ($row = mysqli_fetch_assoc($txnRes)) {
            $amount = (float)$row['credit'];
            $isReceipt = $row['transaction_type'] === 'Receipt';
            $debit = $isReceipt ? $amount : 0.0;
            $credit = $isReceipt ? 0.0 : $amount;
            $balance += ($debit - $credit);

            $descParts = [];
            if (!empty($row['supplier_name'])) {
                $descParts[] = $row['supplier_name'];
            }
            if (!empty($row['memo'])) {
                $descParts[] = $row['memo'];
            }
            $desc = trim(implode('. ', $descParts));
            if ($desc === '') {
                $desc = '-';
            } else {
                $desc = rtrim($desc, '.') . '.';
            }

            if ($isReceipt) {
                $ref = $row['receipt_no'] ?? '';
            } else {
                $voucherVal = $row['pay_voucher'] ?? $row['voutcher_number'] ?? '';
                $chequeVal = $row['pay_cheque'] ?? '';
                $refParts = [];
                if ($voucherVal !== '') {
                    $refParts[] = '' . $voucherVal;
                }
                if ($chequeVal !== '') {
                    $refParts[] = '' . $chequeVal;
                }
                $ref = implode(',', $refParts);
            }

            $rows[] = [
                'sn' => $sn++,
                'date' => $row['tr_date'],
                'ref' => $ref,
                'desc' => $desc,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
            ];
            $totalDebit += $debit;
            $totalCredit += $credit;
        }
        $endingBalance = $balance;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Revenue / Expenditure Print</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        font-size: 13px;
        margin: 20px;
    }

    h3,
    h4 {
        margin: 4px 0;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    table th,
    table td {
        border: 1px solid #000;
        padding: 6px;
    }

    .summary {
        margin-top: 10px;
        font-weight: 600;
    }

    .muted {
        color: #555;
    }

    @media print {
        body {
            margin: 0;
        }
    }
    </style>
</head>

<body>
    <div style="text-align:center;">
        <h3><?php echo htmlspecialchars($client_name); ?></h3>
        <h4>Revenue and Expenditure <?php echo htmlspecialchars($year); ?></h4>

    </div>

    <div class="summary">
        <div class="muted">Income Account:
            <?php echo $selectedIncomeRow ? htmlspecialchars(incomeLabelLang($selectedIncomeRow, $primaryLanguage)) : '-'; ?>
        </div>


        <span>Total Estimate: <?php echo number_format($summary['estimate'], 2); ?></span>
        &nbsp; | &nbsp;
        <span>Total Collection: <?php echo number_format($summary['collection'], 2); ?></span>
        &nbsp; | &nbsp;
        <span>Total Spend: <?php echo number_format($summary['spend'], 2); ?></span>
    </div>

    <table width="90%">
        <thead>
            <tr>
                <th style="width:20px;">Sn</th>
                <th style="width:80px;">Date</th>
                <th style="width:70px;">Ref</th>
                <th>Description</th>
                <th style="width:80px;">Debit</th>
                <th style="width:80px;">Credit</th>
                <th style="width:80px;">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr>
                <td colspan="7" style="text-align:center;">No transactions found.</td>
            </tr>
            <?php else: ?>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?php echo (int)$r['sn']; ?></td>
                <td><?php echo htmlspecialchars($r['date']); ?></td>
                <td><?php echo htmlspecialchars($r['ref']); ?></td>
                <td><?php echo htmlspecialchars($r['desc']); ?></td>
                <td style="text-align:right;"><?php echo formatZeroBlank($r['debit']); ?></td>
                <td style="text-align:right;"><?php echo formatZeroBlank($r['credit']); ?></td>
                <td style="text-align:right;"><?php echo number_format($r['balance'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" style="text-align:right;">Totals</th>
                <th style="text-align:right;"><?php echo number_format($totalDebit, 2); ?></th>
                <th style="text-align:right;"><?php echo number_format($totalCredit, 2); ?></th>
                <th style="text-align:right;"><?php echo number_format($endingBalance, 2); ?></th>
            </tr>
        </tfoot>
    </table>

    <script>
    window.onload = function() {
        window.print();
    };
    </script>
</body>

</html>