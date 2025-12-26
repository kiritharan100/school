<?php
include 'header.php';

$locationFilter = isset($location_id) && $location_id !== '' ? (int)$location_id : 0;

// Language
$primaryLanguage = 'english';
if (isset($client_language)) {
    $lang = strtolower($client_language);
    if (in_array($lang, ['tamil', 'sinhala'], true)) {
        $primaryLanguage = $lang;
    }
}

// Year selection
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Revenue (income) rows with opening balance first
$revenues = [];
$openingBalance = 0;
$incomeTotal = 0;
$expenseTotal = 0;
$openingByRev = [];

if ($locationFilter > 0) {
    // opening balances
    $openRes = mysqli_query($con, "
        SELECT r_id, SUM(op_balance) AS total_open
        FROM budget_opening_balance
        WHERE year = $year AND location_id = $locationFilter
        GROUP BY r_id
    ");
    if ($openRes) {
        while ($row = mysqli_fetch_assoc($openRes)) {
            $openingByRev[(int)$row['r_id']] = (float)$row['total_open'];
            $openingBalance += (float)$row['total_open'];
        }
    }

    // Revenue allocations
    $revRes = mysqli_query($con, "
        SELECT rc.r_id, rc.revinue_code, rc.detail_of_revinue, rc.revinue_tamil, rc.revinue_sinhala,
               COALESCE(SUM(ba.amount),0) AS amt
        FROM revinue_code rc
        LEFT JOIN budget_allocation ba ON ba.r_id = rc.r_id AND ba.year = $year AND ba.location_id = $locationFilter
        WHERE rc.locaton_id = $locationFilter
        GROUP BY rc.r_id, rc.revinue_code, rc.detail_of_revinue, rc.revinue_tamil, rc.revinue_sinhala
        ORDER BY rc.revinue_code ASC
    ");
    while ($row = mysqli_fetch_assoc($revRes)) {
        $rowOpening = $openingByRev[(int)$row['r_id']] ?? 0;
        $row['opening'] = $rowOpening;
        $row['collection'] = (float)$row['amt'] - (float)$rowOpening;
        if ((float)$row['collection'] != 0.0) {
            $revenues[] = $row;
            $incomeTotal += (float)$row['collection'];
        }
    }

    // Expenses: sum budget allocation per expense code
    $expenses = [];
    $expRes = mysqli_query($con, "
        SELECT ec.ex_id, ec.ex_code, ec.ex_english, ec.ex_tamil, ec.ex_sinhala,
               COALESCE(SUM(ba.amount),0) AS amt
        FROM expenditure_code ec
        LEFT JOIN budget_allocation ba ON ba.ex_id = ec.ex_id AND ba.year = $year AND ba.location_id = $locationFilter
        WHERE ec.location_id = $locationFilter AND ec.status = 1
        GROUP BY ec.ex_id, ec.ex_code, ec.ex_english, ec.ex_tamil, ec.ex_sinhala
        ORDER BY ec.ex_code ASC
    ");
    while ($row = mysqli_fetch_assoc($expRes)) {
        if ((float)$row['amt'] != 0.0) {
            $expenses[] = $row;
            $expenseTotal += (float)$row['amt'];
        }
    }
}

function revLabelPrint($row, $lang) {
    if ($lang === 'tamil' && !empty($row['revinue_tamil'])) return $row['revinue_tamil'];
    if ($lang === 'sinhala' && !empty($row['revinue_sinhala'])) return $row['revinue_sinhala'];
    return $row['detail_of_revinue'];
}
function expLabelPrint($row, $lang) {
    if ($lang === 'tamil' && !empty($row['ex_tamil'])) return $row['ex_tamil'];
    if ($lang === 'sinhala' && !empty($row['ex_sinhala'])) return $row['ex_sinhala'];
    return $row['ex_english'];
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 p-0">
                <div class="main-header">
                    <h4>Print Budget Estimates</h4>
                    <button class="btn btn-primary float-right" onclick="window.print()">Print</button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <form method="GET" class="form-inline" style="gap:10px;">
                            <label class="mr-2">Year</label>
                            <input type="number" class="form-control" name="year"
                                value="<?php echo htmlspecialchars($year); ?>">
                            <button type="submit" class="btn btn-secondary ml-2">Load</button>
                        </form>
                    </div>
                    <div class="card-block print-area">
                        <div class="row">
                            <div class="col-12 text-center mb-4">
                                <h4><?php echo $client_name; ?></h4>
                                <h5>Budget Estimates for the Year <?php echo htmlspecialchars($year); ?></h5>
                            </div>
                        </div>
                        <div class="row">


                            <div class="col-md-6">
                                <h5>Income</h5>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width:20px;">#</th>
                                            <th style="width:50px;">Code</th>
                                            <th>Detail</th>
                                            <th style="width:150px;" class="text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>1</td>
                                            <td>OB</td>
                                            <td>Opening Balance</td>
                                            <td class="text-right"><?php echo number_format($openingBalance, 2); ?></td>
                                        </tr>
                                        <?php $sn = 2; foreach ($revenues as $rev): ?>
                                        <tr>
                                            <td><?php echo $sn++; ?></td>
                                            <td><?php echo htmlspecialchars($rev['revinue_code']); ?></td>
                                            <td><?php echo htmlspecialchars(revLabelPrint($rev, $primaryLanguage)); ?>
                                            </td>
                                            <td class="text-right">
                                                <?php echo number_format((float)$rev['collection'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="font-weight-bold">
                                            <td colspan="3" class="text-right">Total Income</td>
                                            <td class="text-right">
                                                <?php echo number_format($incomeTotal + $openingBalance, 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Expenses</h5>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width:20px;">#</th>
                                            <th style="width:50px;">Code</th>
                                            <th>Detail</th>
                                            <th style="width:150px;" class="text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $sn = 1; foreach ($expenses as $exp): ?>
                                        <tr>
                                            <td><?php echo $sn++; ?></td>
                                            <td><?php echo htmlspecialchars($exp['ex_code']); ?></td>
                                            <td><?php echo htmlspecialchars(expLabelPrint($exp, $primaryLanguage)); ?>
                                            </td>
                                            <td class="text-right"><?php echo number_format((float)$exp['amt'], 2); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="font-weight-bold">
                                            <td colspan="3" class="text-right">Total Expenses</td>
                                            <td class="text-right"><?php echo number_format($expenseTotal, 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<style>
@media print {
    @page {
        size: landscape;
        margin: 12mm;
    }

    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        margin: 0;
        background: #fff !important;
    }

    html {
        background: #fff !important;
    }

    /* Hide chrome that we don't want in print */
    header.main-header-top,
    .sidebar-mini .sidebar,
    .main-header,
    .card-header,
    .navbar,
    .loader-bg {
        display: none !important;
    }

    body * {
        visibility: hidden;
    }

    .print-area,
    .print-area * {
        visibility: visible;
    }

    .print-area {
        position: static;
        width: 100% !important;
        margin: 0 !important;
        padding: 6mm !important;
        min-height: 100vh !important;
        background: #fff !important;
    }

    .content-wrapper,
    .container-fluid,
    .row,
    .col-md-6 {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
    }

    .col-md-6 {
        float: left !important;
        width: 50% !important;
    }

    .card,
    .card-block {
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    table.table {
        width: 100% !important;
        margin-bottom: 12px !important;
        background: #fff !important;
        border: 1px solid #000 !important;
    }

    table.table th,
    table.table td {
        border: 1px solid #000 !important;
    }
}
</style>