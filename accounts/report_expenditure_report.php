<?php
ob_start();
include 'header.php';

$locationFilter = isset($location_id) && $location_id !== '' ? (int)$location_id : 0;

// Language helper
$primaryLanguage = 'english';
if (isset($client_language)) {
    $lang = strtolower($client_language);
    if (in_array($lang, ['tamil', 'sinhala'], true)) {
        $primaryLanguage = $lang;
    }
}
function expLabelFull($row, $lang) {
    $english = $row['ex_english'] ?? '';
    $local = '';
    if ($lang === 'tamil' && !empty($row['ex_tamil'])) {
        $local = $row['ex_tamil'];
    } elseif ($lang === 'sinhala' && !empty($row['ex_sinhala'])) {
        $local = $row['ex_sinhala'];
    }
    $label = $row['ex_code'] . ' - ' . $english;
    if ($local && strcasecmp($local, $english) !== 0) {
        $label .= ' / ' . $local;
    }
    return $label;
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

$currentYear = (int)date('Y');
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$yearOptions = [];
if ($locationFilter > 0) {
    $yearRes = mysqli_query($con, "
        SELECT DISTINCT YEAR(tr_date) AS yr
        FROM transaction
        WHERE location_id = $locationFilter
          AND tr_date IS NOT NULL
        ORDER BY yr DESC
    ");
    if ($yearRes) {
        while ($row = mysqli_fetch_assoc($yearRes)) {
            $yr = (int)$row['yr'];
            if ($yr > 0) {
                $yearOptions[] = $yr;
            }
        }
    }
}
$yearOptions[] = $currentYear;
$yearOptions = array_values(array_unique($yearOptions));
rsort($yearOptions);

// Expense accounts
$expenseId = isset($_GET['expense_account']) ? (int)$_GET['expense_account'] : 0;
$expenseAccounts = [];
$selectedExpenseRow = null;
if ($locationFilter > 0) {
    $res = mysqli_query($con, "
        SELECT ex_id, ex_code, ex_english, ex_tamil, ex_sinhala
        FROM expenditure_code
        WHERE location_id = $locationFilter AND status = 1
        ORDER BY ex_code ASC
    ");
    while ($row = mysqli_fetch_assoc($res)) {
        $expenseAccounts[] = $row;
    }
    usort($expenseAccounts, function($a, $b) {
        return strnatcasecmp($a['ex_code'], $b['ex_code']);
    });
    foreach ($expenseAccounts as $row) {
        if ((int)$row['ex_id'] === (int)$expenseId) {
            $selectedExpenseRow = $row;
            break;
        }
    }
}

if ($expenseId === 0 && !empty($expenseAccounts)) {
    $expenseId = (int)$expenseAccounts[0]['ex_id'];
    $selectedExpenseRow = $expenseAccounts[0];
}
if ($selectedExpenseRow === null) {
    foreach ($expenseAccounts as $row) {
        if ((int)$row['ex_id'] === $expenseId) {
            $selectedExpenseRow = $row;
            break;
        }
    }
}

$summary = [
    'estimate' => 0.0,
    'spent' => 0.0,
    'balance' => 0.0,
];
$rows = [];
$totalCredit = 0.0;
$endingBalance = 0.0;

if ($locationFilter > 0 && $expenseId > 0) {
    // Budget estimate
    $estRes = mysqli_query($con, "
        SELECT COALESCE(SUM(amount),0) AS total_est
        FROM budget_allocation
        WHERE year = $year AND location_id = $locationFilter AND ex_id = $expenseId
    ");
    $estimate = $estRes ? (float)(mysqli_fetch_assoc($estRes)['total_est'] ?? 0) : 0.0;

    // Spent
    $spentRes = mysqli_query($con, "
        SELECT COALESCE(SUM(credit),0) AS total_spent
        FROM transaction
        WHERE location_id = $locationFilter
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

    // Detail rows (payments)
    $txnSql = "
        SELECT t.id, t.tr_date, t.voutcher_number, t.memo, t.credit, t.supplier_id,
               sup.supplier_name,
               bp.voutcher_number AS pay_voucher, bp.cheque_number AS pay_cheque
        FROM transaction t
        LEFT JOIN manage_supplier sup ON sup.sup_id = t.supplier_id
        LEFT JOIN bank_payment bp ON bp.record_number = t.transaction_id AND bp.location_id = t.location_id
        WHERE t.location_id = $locationFilter
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
                $refParts[] = 'Voucher: ' . $voucherVal;
            }
            if ($chequeVal !== '') {
                $refParts[] = 'Cheque: ' . $chequeVal;
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

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 p-0">
                <div class="main-header">
                    <h4>Expenditure Report</h4>
                    <small>Expense spend vs budget</small>
                </div>
            </div>
        </div>

        <?php if ($locationFilter === 0): ?>
            <div class="alert alert-warning">
                Please select a working location to view the report.
            </div>
        <?php else: ?>
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <form class="form-inline" method="GET" style="gap:10px;">
                            <label class="mr-2">Year</label>
                            <select name="year" id="year" class="form-control">
                                <?php foreach ($yearOptions as $yr): ?>
                                    <option value="<?php echo $yr; ?>" <?php if ($yr === $year) echo 'selected'; ?>>
                                        <?php echo $yr; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label class="mr-2 ml-3">Expenditure</label>
                            <select name="expense_account" id="expense_account" class="form-control" style="min-width:260px;">
                                <?php foreach ($expenseAccounts as $exp): ?>
                                    <option value="<?php echo (int)$exp['ex_id']; ?>" <?php if ((int)$exp['ex_id'] === $expenseId) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars(expLabelFull($exp, $primaryLanguage)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="btn btn-primary ml-3">Generate Report</button>
                            <button type="button" class="btn btn-secondary ml-2" onclick="printExpenditureReport()">Print</button>
                        </form>
                    </div>
                    <div class="card-block">
                        <div class="printable-area">
                        <div class="row m-b-15">
                            <div class="col-12 text-center">
                                <h4><?php echo htmlspecialchars($client_name); ?></h4>
                            </div>
                        </div>
                        <div class="row m-b-15">
                            <div class="col-md-6">
                                <strong>Expenditure account:</strong>
                                <?php echo $selectedExpenseRow ? htmlspecialchars(expLabelLang($selectedExpenseRow, $primaryLanguage)) : '-'; ?>
                            </div>
                        </div>
                        <div class="row m-b-20">
                            <div class="col-12">
                                <div class="well">
                                    <strong>Total Estimate:</strong> <?php echo number_format($summary['estimate'], 2); ?>
                                    &nbsp; | &nbsp;
                                    <strong>Total Spent:</strong> <?php echo number_format($summary['spent'], 2); ?>
                                    &nbsp; | &nbsp;
                                    <strong>Balance:</strong> <?php echo number_format($summary['balance'], 2); ?>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th style="width:60px;">Sn</th>
                                        <th style="width:140px;">Date</th>
                                        <th style="width:200px;">Reference</th>
                                        <th>Description</th>
                                        <th style="width:140px;" class="text-right">Spent Amount</th>
                                        <th style="width:160px;" class="text-right">Balance (Estimate)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($rows)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No transactions found for the selected filters.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($rows as $r): ?>
                                            <tr>
                                                <td><?php echo (int)$r['sn']; ?></td>
                                                <td><?php echo htmlspecialchars($r['date']); ?></td>
                                                <td><?php echo htmlspecialchars($r['ref']); ?></td>
                                                <td><?php echo htmlspecialchars($r['desc']); ?></td>
                                                <td class="text-right"><?php echo formatZeroBlank($r['credit']); ?></td>
                                                <td class="text-right"><?php echo number_format($r['balance'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="font-weight-bold">
                                        <td colspan="4" class="text-right">Totals</td>
                                        <td class="text-right"><?php echo number_format($totalCredit, 2); ?></td>
                                        <td class="text-right"><?php echo number_format($endingBalance, 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        </div> <!-- printable-area -->
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(function() {
    $('#expense_account').select2({
        width: 'resolve'
    });
});

function printExpenditureReport() {
    var yearEl = document.getElementById('year');
    var expEl = document.getElementById('expense_account');
    var year = yearEl ? yearEl.value : '';
    var exp = expEl ? expEl.value : '';
    var url = 'report_expenditure_report_print.php?year=' + encodeURIComponent(year) + '&expense_account=' + encodeURIComponent(exp);
    window.open(url, '_blank');
}
</script>

<style>
#expense_account + .select2-container {
    max-width: 450px;
}
@media print {
    .table-bordered th,
    .table-bordered td {
        border: 1px solid #000 !important;
        color: #000;
    }
    .card,
    .well,
    .table {
        border: 1px solid #000 !important;
    }
    body * {
        visibility: hidden;
    }
    .printable-area, .printable-area * {
        visibility: visible;
    }
    .printable-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>

<?php include 'footer.php'; ?>
