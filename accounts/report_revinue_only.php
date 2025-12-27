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

function incomeLabelFull($row, $lang) {
    $english = $row['detail_of_revinue'] ?? '';
    $local = '';
    if ($lang === 'tamil' && !empty($row['revinue_tamil'])) {
        $local = $row['revinue_tamil'];
    } elseif ($lang === 'sinhala' && !empty($row['revinue_sinhala'])) {
        $local = $row['revinue_sinhala'];
    }
    $label = $row['revinue_code'] . ' - ' . $english;
    if ($local && strcasecmp($local, $english) !== 0) {
        $label .= ' / ' . $local;
    }
    return $label;
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

// Income accounts
$incomeId = isset($_GET['income_account']) ? (int)$_GET['income_account'] : 0;
$incomeAccounts = [];
$selectedIncomeRow = null;
if ($locationFilter > 0) {
    $res = mysqli_query($con, "
        SELECT r_id, revinue_code, detail_of_revinue, revinue_tamil, revinue_sinhala
        FROM revinue_code
        WHERE locaton_id = $locationFilter AND status = 1
        ORDER BY revinue_code ASC
    ");
    while ($row = mysqli_fetch_assoc($res)) {
        $incomeAccounts[] = $row;
    }
    usort($incomeAccounts, function($a, $b) {
        return strnatcasecmp($a['revinue_code'], $b['revinue_code']);
    });
    foreach ($incomeAccounts as $row) {
        if ((int)$row['r_id'] === (int)$incomeId) {
            $selectedIncomeRow = $row;
            break;
        }
    }
}

if ($incomeId === 0 && !empty($incomeAccounts)) {
    $incomeId = (int)$incomeAccounts[0]['r_id'];
    $selectedIncomeRow = $incomeAccounts[0];
}
if ($selectedIncomeRow === null) {
    foreach ($incomeAccounts as $row) {
        if ((int)$row['r_id'] === $incomeId) {
            $selectedIncomeRow = $row;
            break;
        }
    }
}

$summary = [
    'estimate' => 0.0,
    'collection' => 0.0,
];
$rows = [];
$totalDebit = 0.0;
$endingBalance = 0.0;

if ($locationFilter > 0 && $incomeId > 0) {
    // Totals
    $allocRes = mysqli_query($con, "
        SELECT COALESCE(SUM(amount),0) AS total_alloc
        FROM budget_allocation
        WHERE year = $year AND location_id = $locationFilter AND r_id = $incomeId
    ");
    $alloc = $allocRes ? (float)(mysqli_fetch_assoc($allocRes)['total_alloc'] ?? 0) : 0.0;

    $openRes = mysqli_query($con, "
        SELECT COALESCE(SUM(op_balance),0) AS total_open
        FROM budget_opening_balance
        WHERE year = $year AND location_id = $locationFilter AND r_id = $incomeId
    ");
    $opening = $openRes ? (float)(mysqli_fetch_assoc($openRes)['total_open'] ?? 0) : 0.0;

    $colRes = mysqli_query($con, "
        SELECT COALESCE(SUM(credit),0) AS total_col
        FROM transaction
        WHERE location_id = $locationFilter
          AND status = 1
          AND income_account = $incomeId
          AND transaction_type = 'Receipt'
          AND YEAR(tr_date) = $year
    ");
    $collection = $colRes ? (float)(mysqli_fetch_assoc($colRes)['total_col'] ?? 0) : 0.0;

    $summary['estimate'] = $alloc + $opening;
    $summary['collection'] = $collection + $opening;

    // Detail rows (receipts only)
    $txnSql = "
        SELECT t.id, t.tr_date, t.transaction_type, t.voutcher_number, t.memo, t.credit, t.supplier_id,
               sup.supplier_name,
               br.receipt_number AS receipt_no
        FROM transaction t
        LEFT JOIN manage_supplier sup ON sup.sup_id = t.supplier_id
        LEFT JOIN bank_receipt br ON br.record_no = t.transaction_id AND br.location_id = t.location_id
        WHERE t.location_id = $locationFilter
          AND t.status = 1
          AND t.income_account = $incomeId
          AND t.transaction_type = 'Receipt'
          AND t.credit > 0
          AND YEAR(t.tr_date) = $year
        ORDER BY t.tr_date ASC, t.credit DESC, t.id ASC
    ";
    $txnRes = mysqli_query($con, $txnSql);
    $balance = $opening;
    $sn = 1;
    // Opening row always
    $rows[] = [
        'sn' => $sn++,
        'date' => $year.'-01-01',
        'ref' => 'Balance',
        'desc' => 'Opening balance',
        'debit' => $opening,
        'balance' => $balance,
    ];
    $totalDebit += $opening;
    if ($txnRes) {
        while ($row = mysqli_fetch_assoc($txnRes)) {
            $amount = (float)$row['credit'];
            $debit = $amount;
            $balance += $debit;

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

            $ref = $row['receipt_no'] ?? '';

            $rows[] = [
                'sn' => $sn++,
                'date' => $row['tr_date'],
                'ref' => $ref,
                'desc' => $desc,
                'debit' => $debit,
                'balance' => $balance,
            ];
            $totalDebit += $debit;
        }
        $endingBalance = $balance;
    }
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 p-0">
                <div class="main-header">
                    <h4>Revenue Report</h4>
                    <small>Income receipts only</small>
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

                            <label class="mr-2 ml-3">Income Account</label>
                            <select name="income_account" id="income_account" class="form-control"
                                style="min-width:260px;">
                                <?php foreach ($incomeAccounts as $inc): ?>
                                <option value="<?php echo (int)$inc['r_id']; ?>"
                                    <?php if ((int)$inc['r_id'] === $incomeId) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars(incomeLabelFull($inc, $primaryLanguage)); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="btn btn-primary ml-3">Generate Report</button>
                            <button type="button" class="btn btn-secondary ml-2" id="printRevenueOnlyBtn"
                                onclick="printRevenueOnly()">Print</button>
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
                                    <strong>Income account:</strong>
                                    <?php echo $selectedIncomeRow ? htmlspecialchars(incomeLabelLang($selectedIncomeRow, $primaryLanguage)) : '-'; ?>
                                </div>
                            </div>
                            <div class="row m-b-20">
                                <div class="col-12">
                                    <div class="well">
                                        <strong>Total Estimate:</strong>
                                        <?php echo number_format($summary['estimate'], 2); ?>
                                        &nbsp; | &nbsp;
                                        <strong>Total Collection:</strong>
                                        <?php echo number_format($summary['collection'], 2); ?>
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
                                            <th style="width:140px;" class="text-right">Debit</th>
                                            <th style="width:160px;" class="text-right">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($rows)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No transactions found for the selected
                                                filters.</td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($rows as $r): ?>
                                        <tr>
                                            <td><?php echo (int)$r['sn']; ?></td>
                                            <td><?php echo htmlspecialchars($r['date']); ?></td>
                                            <td><?php echo htmlspecialchars($r['ref']); ?></td>
                                            <td><?php echo htmlspecialchars($r['desc']); ?></td>
                                            <td class="text-right"><?php echo formatZeroBlank($r['debit']); ?></td>
                                            <td class="text-right"><?php echo number_format($r['balance'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-weight-bold">
                                            <td colspan="4" class="text-right">Totals</td>
                                            <td class="text-right"><?php echo number_format($totalDebit, 2); ?></td>
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
    $('#income_account').select2({
        width: 'resolve'
    });
});

function printRevenueOnly() {
    var yearEl = document.getElementById('year');
    var incomeEl = document.getElementById('income_account');
    var year = yearEl ? yearEl.value : '';
    var income = incomeEl ? incomeEl.value : '';
    var url = 'report_revinue_only_print.php?year=' + encodeURIComponent(year) + '&income_account=' +
        encodeURIComponent(income);
    window.open(url, '_blank');
}
</script>

<style>
#income_account+.select2-container {
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

    .printable-area,
    .printable-area * {
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