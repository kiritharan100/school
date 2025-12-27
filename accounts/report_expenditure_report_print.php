<?php
require('../db.php');
require('../auth.php');

// Location from cookie (mirror accounts/header.php)
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
function expLabelLang($row, $lang) {
    if ($lang === 'tamil' && !empty($row['ex_tamil'])) {
        return $row['ex_code'] . ' - ' . $row['ex_tamil'];
    }
    if ($lang === 'sinhala' && !empty($row['ex_sinhala'])) {
        return $row['ex_code'] . ' - ' . $row['ex_sinhala'];
    }
    return $row['ex_code'] . ' - ' . ($row['ex_english'] ?? '');
}
function formatZeroBlank($val) {
    return (float)$val == 0.0 ? '' : number_format((float)$val, 2);
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$expenseId = isset($_GET['expense_account']) ? (int)$_GET['expense_account'] : 0;

$summary = ['estimate' => 0.0, 'spent' => 0.0, 'balance' => 0.0];
$rows = [];
$totalCredit = 0.0;
$endingBalance = 0.0;
$selectedExpenseRow = null;

if ($location_id > 0 && $expenseId > 0) {
    $expRes = mysqli_query($con, "
        SELECT ex_id, ex_code, ex_english, ex_tamil, ex_sinhala
        FROM expenditure_code
        WHERE location_id = $location_id AND status = 1 AND ex_id = $expenseId
        LIMIT 1
    ");
    $selectedExpenseRow = $expRes ? mysqli_fetch_assoc($expRes) : null;

    $estRes = mysqli_query($con, "
        SELECT COALESCE(SUM(amount),0) AS total_est
        FROM budget_allocation
        WHERE year = $year AND location_id = $location_id AND ex_id = $expenseId
    ");
    $estimate = $estRes ? (float)(mysqli_fetch_assoc($estRes)['total_est'] ?? 0) : 0.0;

    $spentRes = mysqli_query($con, "
        SELECT COALESCE(SUM(credit),0) AS total_spent
        FROM transaction
        WHERE location_id = $location_id
          AND status = 1
          AND expenses_account = $expenseId
          AND transaction_type = 'Payment'
          AND credit > 0
          AND YEAR(tr_date) = $year
    ");
    $spentTotal = $spentRes ? (float)(mysqli_fetch_assoc($spentRes)['total_spent'] ?? 0) : 0.0;

    $summary['estimate'] = $estimate;
    $summary['spent'] = $spentTotal;
    $summary['balance'] = $estimate - $spentTotal;

    $txnSql = "
        SELECT t.id, t.tr_date, t.voutcher_number, t.memo, t.credit, t.supplier_id,
               sup.supplier_name,
               bp.voutcher_number AS pay_voucher, bp.cheque_number AS pay_cheque
        FROM transaction t
        LEFT JOIN manage_supplier sup ON sup.sup_id = t.supplier_id
        LEFT JOIN bank_payment bp ON bp.record_number = t.transaction_id AND bp.location_id = t.location_id
        WHERE t.location_id = $location_id
          AND t.status = 1
          AND t.expenses_account = $expenseId
          AND t.transaction_type = 'Payment'
          AND t.credit > 0
          AND YEAR(t.tr_date) = $year
        ORDER BY t.tr_date ASC, t.id ASC
    ";
    $txnRes = mysqli_query($con, $txnSql);
    $balance = $estimate;
    $sn = 1;
    if ($txnRes) {
        while ($row = mysqli_fetch_assoc($txnRes)) {
            $amount = (float)$row['credit'];
            $balance -= $amount;

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

            $refParts = [];
            $voucherVal = $row['pay_voucher'] ?? $row['voutcher_number'] ?? '';
            $chequeVal = $row['pay_cheque'] ?? '';
            if ($voucherVal !== '') {
                $refParts[] = '' . $voucherVal;
            }
            if ($chequeVal !== '') {
                $refParts[] = ' ' . $chequeVal;
            }
            $ref = implode(' | ', $refParts);

            $rows[] = [
                'sn' => $sn++,
                'date' => $row['tr_date'],
                'ref' => $ref,
                'desc' => $desc,
                'credit' => $amount,
                'balance' => $balance,
            ];
            $totalCredit += $amount;
        }
        $endingBalance = $balance;
    } else {
        $endingBalance = $estimate;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Expenditure Report</title>
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
        <h4>Expenditure</h4>
        <div class="muted">Year: <?php echo htmlspecialchars($year); ?></div>

    </div>

    <div class="summary">
        <div class="muted">Expenditure Account:
            <?php echo $selectedExpenseRow ? htmlspecialchars(expLabelLang($selectedExpenseRow, $primaryLanguage)) : '-'; ?>
        </div>
        <span>Total Estimate: <?php echo number_format($summary['estimate'], 2); ?></span>
        &nbsp; | &nbsp;
        <span>Total Spent: <?php echo number_format($summary['spent'], 2); ?></span>
        &nbsp; | &nbsp;
        <span>Balance: <?php echo number_format($summary['balance'], 2); ?></span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:10px;">Sn</th>
                <th style="width:100px;">Date</th>
                <th style="width:80px;">Reference</th>
                <th>Description</th>
                <th style="width:80px;">Spent Amount</th>
                <th style="width:80px;">Balance (Estimate)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr>
                <td colspan="6" style="text-align:center;">No transactions found.</td>
            </tr>
            <?php else: ?>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?php echo (int)$r['sn']; ?></td>
                <td><?php echo htmlspecialchars($r['date']); ?></td>
                <td><?php echo htmlspecialchars($r['ref']); ?></td>
                <td><?php echo htmlspecialchars($r['desc']); ?></td>
                <td style="text-align:right;"><?php echo formatZeroBlank($r['credit']); ?></td>
                <td style="text-align:right;"><?php echo number_format($r['balance'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" style="text-align:right;">Totals</th>
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