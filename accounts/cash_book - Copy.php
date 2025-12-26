<?php
// Cash Book
ob_start();
include 'header.php';

$locationFilter = isset($location_id) && $location_id !== '' ? (int)$location_id : 0;

function isValidDateStr($d) {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
}

$defaultStart = date('Y-m-01', strtotime('first day of last month'));
$defaultEnd   = date('Y-m-t', strtotime('last day of last month'));

$cookieStart = isset($_COOKIE['cashbook_start']) && isValidDateStr($_COOKIE['cashbook_start']) ? $_COOKIE['cashbook_start'] : null;
$cookieEnd   = isset($_COOKIE['cashbook_end']) && isValidDateStr($_COOKIE['cashbook_end']) ? $_COOKIE['cashbook_end'] : null;

$startDate = isset($_GET['start_date']) && isValidDateStr($_GET['start_date']) ? $_GET['start_date'] : ($cookieStart ?: $defaultStart);
$endDate   = isset($_GET['end_date']) && isValidDateStr($_GET['end_date']) ? $_GET['end_date'] : ($cookieEnd ?: $defaultEnd);

// Banks for this location
$banks = [];
$bankIdsAll = [];
$bankNameById = [];
$bankOpeningBase = [];
if ($locationFilter > 0) {
    $bankRes = mysqli_query($con, "SELECT id, bank_name, opening_balance FROM bank_account WHERE location_id = $locationFilter AND status = 1 ORDER BY bank_name ASC");
    while ($row = mysqli_fetch_assoc($bankRes)) {
        $banks[] = $row;
        $bankIdsAll[] = (int)$row['id'];
        $bankNameById[(int)$row['id']] = $row['bank_name'];
        $bankOpeningBase[(int)$row['id']] = (float)$row['opening_balance'];
    }
}

// Selected banks (GET override cookie, then default all)
$selectedBankIds = [];
if (!empty($_GET['banks'])) {
    $parts = explode(',', $_GET['banks']);
    foreach ($parts as $p) {
        $id = (int)$p;
        if ($id > 0) $selectedBankIds[] = $id;
    }
} elseif (!empty($_COOKIE['cashbook_banks'])) {
    $parts = explode(',', $_COOKIE['cashbook_banks']);
    foreach ($parts as $p) {
        $id = (int)$p;
        if ($id > 0) $selectedBankIds[] = $id;
    }
}

// Fallback to all banks if none picked or invalid
if (empty($selectedBankIds)) {
    $selectedBankIds = $bankIdsAll;
}

// Build SQL filter
$bankFilterSql = count($selectedBankIds) ? implode(',', array_map('intval', $selectedBankIds)) : '0';

$selectedBankNames = [];
foreach ($selectedBankIds as $bid) {
    if (isset($bankNameById[$bid])) {
        $selectedBankNames[] = $bankNameById[$bid];
    }
}

$debits = [];
$credits = [];
$debitTotal = 0;
$creditTotal = 0;
$debitTotalsByBank = [];
$creditTotalsByBank = [];
$openingBalanceByBank = [];
$closingBalanceByBank = [];
$openingDebitByBank = [];
$openingCreditByBank = [];
$closingDebitByBank = [];
$closingCreditByBank = [];
foreach ($selectedBankIds as $bid) {
    $debitTotalsByBank[$bid] = 0;
    $creditTotalsByBank[$bid] = 0;
    $openingBalanceByBank[$bid] = $bankOpeningBase[$bid] ?? 0;
    $closingBalanceByBank[$bid] = 0;
    $openingDebitByBank[$bid] = 0;
    $openingCreditByBank[$bid] = 0;
    $closingDebitByBank[$bid] = 0;
    $closingCreditByBank[$bid] = 0;
}

if ($locationFilter > 0 && !empty($bankFilterSql)) {
    $startSql = mysqli_real_escape_string($con, $startDate);
    $endSql = mysqli_real_escape_string($con, $endDate);

    // Opening balances include base + prior transactions before start date
    $prevSql = "
        SELECT bank_account_id, COALESCE(SUM(debit - credit),0) AS net
        FROM transaction
        WHERE location_id = $locationFilter
          AND status = 1
          AND bank_account_id IN ($bankFilterSql)
          AND tr_date < '$startSql'
        GROUP BY bank_account_id
    ";
    $prevRes = mysqli_query($con, $prevSql);
    if ($prevRes) {
        while ($row = mysqli_fetch_assoc($prevRes)) {
            $bid = (int)$row['bank_account_id'];
            if (isset($openingBalanceByBank[$bid])) {
                $openingBalanceByBank[$bid] += (float)$row['net'];
            }
        }
    }

    $debitSql = "
        SELECT t.id, t.tr_date, t.voutcher_number, t.memo, t.transaction_type, t.transaction_id,
               t.bank_account_id, t.debit, ba.bank_name, rc.revinue_code, br.receipt_number
        FROM transaction t
        LEFT JOIN bank_account ba ON ba.id = t.bank_account_id
        LEFT JOIN revinue_code rc ON rc.r_id = t.income_account
        LEFT JOIN bank_receipt br ON br.record_no = t.transaction_id AND br.location_id = t.location_id
        WHERE t.location_id = $locationFilter
          AND t.status = 1
          AND t.bank_account_id IN ($bankFilterSql)
          AND t.tr_date BETWEEN '$startSql' AND '$endSql'
          AND t.debit > 0
        ORDER BY t.tr_date ASC, t.id ASC
    ";
    $debitRes = mysqli_query($con, $debitSql);
    if ($debitRes) {
        while ($row = mysqli_fetch_assoc($debitRes)) {
            $debits[] = $row;
            $debitTotal += (float)$row['debit'];
            $bid = (int)$row['bank_account_id'];
            if (isset($debitTotalsByBank[$bid])) {
                $debitTotalsByBank[$bid] += (float)$row['debit'];
            }
        }
    }

    $creditSql = "
        SELECT t.id, t.tr_date, t.voutcher_number, t.memo, t.transaction_type, t.transaction_id,
               t.bank_account_id, t.credit, ba.bank_name, rc.revinue_code AS income_code, ec.ex_code, bp.cheque_number, sup.supplier_name
        FROM transaction t
        LEFT JOIN bank_account ba ON ba.id = t.bank_account_id
        LEFT JOIN revinue_code rc ON rc.r_id = t.income_account
        LEFT JOIN expenditure_code ec ON ec.ex_id = t.expenses_account
        LEFT JOIN bank_payment bp ON bp.record_number = t.transaction_id AND bp.location_id = t.location_id
        LEFT JOIN manage_supplier sup ON sup.sup_id = t.supplier_id
        WHERE t.location_id = $locationFilter
          AND t.status = 1
          AND t.bank_account_id IN ($bankFilterSql)
          AND t.tr_date BETWEEN '$startSql' AND '$endSql'
          AND t.credit > 0
        ORDER BY t.tr_date ASC, t.id ASC
    ";
    $creditRes = mysqli_query($con, $creditSql);
    if ($creditRes) {
        while ($row = mysqli_fetch_assoc($creditRes)) {
            $credits[] = $row;
            $creditTotal += (float)$row['credit'];
            $bid = (int)$row['bank_account_id'];
            if (isset($creditTotalsByBank[$bid])) {
                $creditTotalsByBank[$bid] += (float)$row['credit'];
            }
        }
    }

    // Closing balances and opening/closing split for display
    foreach ($selectedBankIds as $bid) {
        $open = $openingBalanceByBank[$bid];
        if ($open >= 0) {
            $openingDebitByBank[$bid] = $open;
        } else {
            $openingCreditByBank[$bid] = abs($open);
        }

        $closing = $open + ($debitTotalsByBank[$bid] ?? 0) - ($creditTotalsByBank[$bid] ?? 0);
        $closingBalanceByBank[$bid] = $closing;
        if ($closing >= 0) {
            // debit balance placed on credit side to tally
            $closingCreditByBank[$bid] = $closing;
        } else {
            $closingDebitByBank[$bid] = abs($closing);
        }
    }
}

$openingDebitSum  = array_sum($openingDebitByBank);
$openingCreditSum = array_sum($openingCreditByBank);
$closingDebitSum  = array_sum($closingDebitByBank);
$closingCreditSum = array_sum($closingCreditByBank);
$debitTotalsByBankWithBalances = [];
$creditTotalsByBankWithBalances = [];
foreach ($selectedBankIds as $bid) {
    $debitTotalsByBankWithBalances[$bid] = ($debitTotalsByBank[$bid] ?? 0) + ($openingDebitByBank[$bid] ?? 0) + ($closingDebitByBank[$bid] ?? 0);
    $creditTotalsByBankWithBalances[$bid] = ($creditTotalsByBank[$bid] ?? 0) + ($openingCreditByBank[$bid] ?? 0) + ($closingCreditByBank[$bid] ?? 0);
}
$debitTotalWithBalances  = $debitTotal + $openingDebitSum + $closingDebitSum;
$creditTotalWithBalances = $creditTotal + $openingCreditSum + $closingCreditSum;
$hasDebitRows = (count($debits) > 0) || ($openingDebitSum > 0) || ($closingDebitSum > 0);
$hasCreditRows = (count($credits) > 0) || ($openingCreditSum > 0) || ($closingCreditSum > 0);
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 p-0">
                <div class="main-header">
                    <h4>Cash Book</h4>
                    <p class="text-muted">Filters persist via cookies for this window.</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <form id="filterForm" method="GET" class="form-inline" style="gap:10px;">
                            <div class="form-group">
                                <label class="mr-2">From</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                            </div>
                            <div class="form-group ml-3">
                                <label class="mr-2">To</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                            </div>
                            <input type="hidden" id="banks" name="banks" value="<?php echo htmlspecialchars(implode(',', $selectedBankIds)); ?>">
                            <button type="button" class="btn btn-info ml-3" data-toggle="modal" data-target="#bankModal">Select Bank(s)</button>
                            <button type="submit" class="btn btn-primary ml-2">Load</button>
                            <span class="ml-3">Selected banks:
                                <?php if (!empty($selectedBankNames)): ?>
                                    <?php foreach ($selectedBankNames as $bn): ?>
                                        <span class="badge badge-success"><?php echo htmlspecialchars($bn); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="badge badge-default">All</span>
                                <?php endif; ?>
                            </span>
                        </form>
                    </div>
                    <div class="card-block">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Debit (Receipts / Transfers In)</h5>
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th style="width:95px;">Date</th>
                                            <th style="width:80px;">Income Code</th>
                                            <th>Description</th>
                                            <th style="width:90px;">Receipt #</th>
                                            <th style="width:90px;">Voucher #</th>
                                            <?php foreach ($selectedBankIds as $bid): ?>
                                                <th style="width:110px;" class="text-right"><?php echo htmlspecialchars($bankNameById[$bid] ?? ('Bank '.$bid)); ?></th>
                                            <?php endforeach; ?>
                                            <th style="width:120px;" class="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($openingDebitSum > 0): ?>
                                            <tr class="table-info">
                                                <td colspan="5"><strong>Opening Balance</strong></td>
                                                <?php foreach ($selectedBankIds as $bid): ?>
                                                    <td class="text-right"><?php echo number_format($openingDebitByBank[$bid] ?? 0, 2); ?></td>
                                                <?php endforeach; ?>
                                                <td class="text-right"><?php echo number_format($openingDebitSum, 2); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if ($hasDebitRows && count($debits) > 0): ?>
                                            <?php foreach ($debits as $d): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($d['tr_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($d['revinue_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($d['memo']); ?></td>
                                                    <td><?php echo htmlspecialchars($d['receipt_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($d['voutcher_number']); ?></td>
                                                    <?php foreach ($selectedBankIds as $bid): ?>
                                                        <td class="text-right">
                                                            <?php echo ((int)$d['bank_account_id'] === (int)$bid) ? number_format((float)$d['debit'], 2) : ''; ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                    <td class="text-right"><?php echo number_format((float)$d['debit'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <?php if ($closingDebitSum > 0): ?>
                                            <tr class="table-info">
                                                <td colspan="5"><strong>Closing Balance</strong></td>
                                                <?php foreach ($selectedBankIds as $bid): ?>
                                                    <td class="text-right"><?php echo number_format($closingDebitByBank[$bid] ?? 0, 2); ?></td>
                                                <?php endforeach; ?>
                                                <td class="text-right"><?php echo number_format($closingDebitSum, 2); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!$hasDebitRows): ?>
                                            <tr><td colspan="<?php echo 5 + count($selectedBankIds) + 1; ?>" class="text-center text-muted">No debit records</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-weight-bold">
                                            <td colspan="5" class="text-right">Total Debit</td>
                                            <?php foreach ($selectedBankIds as $bid): ?>
                                                <td class="text-right"><?php echo number_format($debitTotalsByBankWithBalances[$bid] ?? 0, 2); ?></td>
                                            <?php endforeach; ?>
                                            <td class="text-right"><?php echo number_format($debitTotalWithBalances, 2); ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Credit (Payments / Transfers Out)</h5>
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th style="width:95px;">Date</th>
                                            <th style="width:90px;">Voucher #</th>
                                            <th>Description</th>
                                            <th style="width:90px;">Cheque #</th>
                                            <th style="width:90px;">Income Code</th>
                                            <th style="width:90px;">Expense Code</th>
                                            <?php foreach ($selectedBankIds as $bid): ?>
                                                <th style="width:110px;" class="text-right"><?php echo htmlspecialchars($bankNameById[$bid] ?? ('Bank '.$bid)); ?></th>
                                            <?php endforeach; ?>
                                            <th style="width:120px;" class="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($openingCreditSum > 0): ?>
                                            <tr class="table-info">
                                                <td colspan="6"><strong>Opening Balance</strong></td>
                                                <?php foreach ($selectedBankIds as $bid): ?>
                                                    <td class="text-right"><?php echo number_format($openingCreditByBank[$bid] ?? 0, 2); ?></td>
                                                <?php endforeach; ?>
                                                <td class="text-right"><?php echo number_format($openingCreditSum, 2); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if ($hasCreditRows && count($credits) > 0): ?>
                                            <?php foreach ($credits as $c): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($c['tr_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($c['voutcher_number']); ?></td>
                                                    <td><?php echo htmlspecialchars(trim($c['supplier_name'] . ' ' . $c['memo'])); ?></td>
                                                    <td><?php echo htmlspecialchars($c['cheque_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($c['income_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($c['ex_code']); ?></td>
                                                    <?php foreach ($selectedBankIds as $bid): ?>
                                                        <td class="text-right">
                                                            <?php echo ((int)$c['bank_account_id'] === (int)$bid) ? number_format((float)$c['credit'], 2) : ''; ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                    <td class="text-right"><?php echo number_format((float)$c['credit'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <?php if ($closingCreditSum > 0): ?>
                                            <tr class="table-info">
                                                <td colspan="6"><strong>Closing Balance</strong></td>
                                                <?php foreach ($selectedBankIds as $bid): ?>
                                                    <td class="text-right"><?php echo number_format($closingCreditByBank[$bid] ?? 0, 2); ?></td>
                                                <?php endforeach; ?>
                                                <td class="text-right"><?php echo number_format($closingCreditSum, 2); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!$hasCreditRows): ?>
                                            <tr><td colspan="<?php echo 6 + count($selectedBankIds) + 1; ?>" class="text-center text-muted">No credit records</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-weight-bold">
                                            <td colspan="6" class="text-right">Total Credit</td>
                                            <?php foreach ($selectedBankIds as $bid): ?>
                                                <td class="text-right"><?php echo number_format($creditTotalsByBankWithBalances[$bid] ?? 0, 2); ?></td>
                                            <?php endforeach; ?>
                                            <td class="text-right"><?php echo number_format($creditTotalWithBalances, 2); ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bank selection modal -->
<div class="modal fade" id="bankModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Bank Accounts</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" id="bankCheckAll"></th>
                    <th>Bank Name</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($banks as $b): ?>
                <?php $checked = in_array((int)$b['id'], $selectedBankIds, true) ? 'checked' : ''; ?>
                <tr>
                    <td><input type="checkbox" class="bank-check" value="<?php echo (int)$b['id']; ?>" <?php echo $checked; ?>></td>
                    <td><?php echo htmlspecialchars($b['bank_name']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-danger" id="clearBanks">Clear</button>
        <button type="button" class="btn btn-primary" id="applyBanks">Apply</button>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
<?php ob_end_flush(); ?>

<script>
(function() {
    function setCookie(name, value) {
        document.cookie = name + "=" + encodeURIComponent(value) + ";path=/;max-age=" + (60*60*24*180);
    }

    $('#filterForm').on('submit', function() {
        setCookie('cashbook_start', $('#start_date').val());
        setCookie('cashbook_end', $('#end_date').val());
    });

    $('#bankCheckAll').on('change', function() {
        $('.bank-check').prop('checked', $(this).is(':checked'));
    });

    $('#clearBanks').on('click', function() {
        $('.bank-check').prop('checked', false);
    });

    $('#applyBanks').on('click', function() {
        const ids = [];
        $('.bank-check:checked').each(function() {
            ids.push($(this).val());
        });
        $('#banks').val(ids.join(','));
        setCookie('cashbook_banks', ids.join(','));
        $('#bankModal').modal('hide');
        $('#filterForm').submit();
    });
})();
</script>
