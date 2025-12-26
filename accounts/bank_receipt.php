<?php
ob_start();
include 'header.php';

$locationFilter = isset($location_id) && $location_id !== '' ? (int)$location_id : 0;
$flash = ['type' => '', 'text' => ''];

// Default date from cookie or today
$defaultDate = date('Y-m-d');
if (isset($_COOKIE['transaction_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_COOKIE['transaction_date'])) {
    $defaultDate = $_COOKIE['transaction_date'];
}

// Date range filters default to current year
$currentYear = (int)date('Y');
$fromDate = isset($_GET['from_date']) && $_GET['from_date'] !== '' ? $_GET['from_date'] : $currentYear . '-01-01';
$toDate = isset($_GET['to_date']) && $_GET['to_date'] !== '' ? $_GET['to_date'] : $currentYear . '-12-31';

// Bank filter with cookie fallback
$filterBank = isset($_GET['filter_bank']) ? (int)$_GET['filter_bank'] : (isset($_COOKIE['receipt_filter_bank']) ? (int)$_COOKIE['receipt_filter_bank'] : 0);

// Preferred bank selection from cookie (local to this window)
$defaultBank = 0;
if (isset($_COOKIE['receipt_bank']) && ctype_digit($_COOKIE['receipt_bank'])) {
    $defaultBank = (int)$_COOKIE['receipt_bank'];
}

// Load bank accounts
$bankAccounts = [];
if ($locationFilter > 0) {
    $res = mysqli_query($con, "
        SELECT id, bank_name
        FROM bank_account
        WHERE location_id = $locationFilter AND status = 1
        ORDER BY bank_name ASC
    ");
    while ($row = mysqli_fetch_assoc($res)) {
        $bankAccounts[] = $row;
    }
}

// Load income accounts (revinue_code)
$incomeAccounts = [];
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
    usort($incomeAccounts, function($a,$b){ return strnatcasecmp($a['revinue_code'], $b['revinue_code']); });
}

// Language helper
$primaryLanguage = 'english';
if (isset($client_language)) {
    $lang = strtolower($client_language);
    if (in_array($lang, ['tamil', 'sinhala'], true)) {
        $primaryLanguage = $lang;
    }
}
function incomeLabel($row, $lang) {
    if ($lang === 'tamil' && !empty($row['revinue_tamil'])) return $row['revinue_code'].' - '.$row['revinue_tamil'];
    if ($lang === 'sinhala' && !empty($row['revinue_sinhala'])) return $row['revinue_code'].' - '.$row['revinue_sinhala'];
    return $row['revinue_code'].' - '.$row['detail_of_revinue'];
}

// CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save
    if (isset($_POST['save_receipt'])) {
        $recId   = isset($_POST['rec_id']) ? (int)$_POST['rec_id'] : 0;
        $bankId  = isset($_POST['bank_id']) ? (int)$_POST['bank_id'] : 0;
        $incId   = isset($_POST['income_account']) ? (int)$_POST['income_account'] : 0;
        $amount  = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
        $memo    = mysqli_real_escape_string($con, trim($_POST['memo'] ?? ''));
        $receiptNo = mysqli_real_escape_string($con, trim($_POST['receipt_number'] ?? ''));
        $date    = mysqli_real_escape_string($con, trim($_POST['rec_date'] ?? ''));
        $status  = 1;

        if ($locationFilter === 0) {
            $flash = ['type'=>'danger','text'=>'Select a location first.'];
        } elseif ($bankId === 0 || $incId === 0) {
            $flash = ['type'=>'danger','text'=>'Bank and income accounts are required.'];
        } elseif ($amount <= 0) {
            $flash = ['type'=>'danger','text'=>'Amount must be greater than zero.'];
        } elseif ($date === '') {
            $flash = ['type'=>'danger','text'=>'Date is required.'];
        } elseif ($receiptNo === '') {
            $flash = ['type'=>'danger','text'=>'Receipt No is required.'];
        } else {
            setcookie('transaction_date', $date, time() + 86400*180, '/');
            if ($bankId > 0) {
                setcookie('receipt_bank', $bankId, time() + 86400*180, '/');
                setcookie('receipt_filter_bank', $bankId, time() + 86400*180, '/');
            }
            // determine record_no
            $recordNo = 0;
            if ($recId > 0) {
                $existing = mysqli_query($con, "SELECT record_no FROM bank_receipt WHERE rec_id=$recId AND location_id=$locationFilter");
                if ($existing && mysqli_num_rows($existing) > 0) {
                    $row = mysqli_fetch_assoc($existing);
                    $recordNo = (int)$row['record_no'];
                }
            }
            if ($recordNo === 0) {
                $maxRes = mysqli_query($con, "SELECT COALESCE(MAX(record_no),0) AS mx FROM bank_receipt WHERE location_id=$locationFilter");
                $maxRow = $maxRes ? mysqli_fetch_assoc($maxRes) : ['mx'=>0];
                $recordNo = (int)($maxRow['mx'] ?? 0) + 1;
            }

            if ($recId > 0) {
                $sql = "
                    UPDATE bank_receipt SET
                        bank_id = $bankId,
                        rec_date = '$date',
                        income_account = $incId,
                        memo = '$memo',
                        amount = $amount,
                        receipt_number = '$receiptNo',
                        status = $status
                    WHERE rec_id = $recId AND location_id = $locationFilter
                ";
                $ok = mysqli_query($con, $sql);
            } else {
                $sql = "
                    INSERT INTO bank_receipt (location_id, record_no, bank_id, rec_date, receipt_number, income_account, memo, amount, status)
                    VALUES ($locationFilter, $recordNo, $bankId, '$date', '$receiptNo', $incId, '$memo', $amount, $status)
                ";
                $ok = mysqli_query($con, $sql);
                if ($ok) {
                    $recId = mysqli_insert_id($con);
                }
            }

            if ($ok) {
                $txnId = $recordNo;
                $voucher = 'BR-'.$recordNo;
                // remove existing txn rows for this receipt
                mysqli_query($con, "DELETE FROM transaction WHERE transaction_id=$txnId AND transaction_type='Receipt' AND location_id=$locationFilter");
                $memoSql = "'$memo'";
                $dateSql = "'$date'";
                // Debit bank
                mysqli_query($con, "
                    INSERT INTO transaction (location_id, transaction_id, transaction_type, voutcher_number, tr_date, bank_account_id, income_account, expenses_account, memo, debit, credit, supplier_id, status)
                    VALUES ($locationFilter, $txnId, 'Receipt', '$voucher', $dateSql, $bankId, $incId, 0, $memoSql, $amount, 0, 0, $status)
                ");
                // Credit income
                mysqli_query($con, "
                    INSERT INTO transaction (location_id, transaction_id, transaction_type, voutcher_number, tr_date, bank_account_id, income_account, expenses_account, memo, debit, credit, supplier_id, status)
                    VALUES ($locationFilter, $txnId, 'Receipt', '$voucher', $dateSql, 0, $incId, 0, $memoSql, 0, $amount, 0, $status)
                ");
                $flash = ['type'=>'success','text'=>'Receipt saved.'];
            } else {
                $flash = ['type'=>'danger','text'=>'Failed to save: '.mysqli_error($con)];
            }
        }
    }

    // Delete
    if (isset($_POST['delete_receipt'])) {
        $delId = isset($_POST['delete_id']) ? (int)$_POST['delete_id'] : 0;
        if ($delId > 0 && $locationFilter > 0) {
            $recRes = mysqli_query($con, "SELECT record_no FROM bank_receipt WHERE rec_id=$delId AND location_id=$locationFilter");
            $recordNo = 0;
            if ($recRes && mysqli_num_rows($recRes)>0) {
                $row = mysqli_fetch_assoc($recRes);
                $recordNo = (int)$row['record_no'];
            }
            mysqli_query($con, "DELETE FROM bank_receipt WHERE rec_id=$delId AND location_id=$locationFilter");
            if ($recordNo > 0) {
                mysqli_query($con, "DELETE FROM transaction WHERE transaction_id=$recordNo AND transaction_type='Receipt' AND location_id=$locationFilter");
            }
            $flash = ['type'=>'success','text'=>'Receipt deleted.'];
        }
    }
}

// Load receipts
$receipts = [];
if ($locationFilter > 0) {
    $res = mysqli_query($con, "
        SELECT br.*, ba.bank_name, rc.revinue_code, rc.detail_of_revinue, rc.revinue_tamil, rc.revinue_sinhala
        FROM bank_receipt br
        LEFT JOIN bank_account ba ON ba.id = br.bank_id
        LEFT JOIN revinue_code rc ON rc.r_id = br.income_account
        WHERE br.location_id = $locationFilter
          AND br.rec_date BETWEEN '$fromDate' AND '$toDate'
          " . ($filterBank > 0 ? " AND br.bank_id = $filterBank " : "") . "
        ORDER BY br.rec_date DESC, br.rec_id DESC
    ");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $receipts[] = $row;
        }
    } else {
        $flash = ['type'=>'danger','text'=>'Failed to load receipts: '.mysqli_error($con)];
    }
}

// persist bank filter cookie
setcookie('receipt_filter_bank', (int)$filterBank, time()+86400*180, '/');
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 p-0">
                <div class="main-header">
                    <h4>Bank Receipt</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?php if ($flash['text'] !== ''): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['text']); ?>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <form method="GET" class="form-inline float-left" style="gap:10px;">
                            <label class="mr-2">From</label>
                            <input type="date" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>"
                                class="form-control">
                            <label class="mr-2 ml-2">To</label>
                            <input type="date" name="to_date" value="<?php echo htmlspecialchars($toDate); ?>"
                                class="form-control">
                            <label class="mr-2 ml-2">Bank</label>
                            <select name="filter_bank" class="form-control">
                                <option value="0">All</option>
                                <?php foreach ($bankAccounts as $ba): ?>
                                <option value="<?php echo (int)$ba['id']; ?>"
                                    <?php if ($filterBank === (int)$ba['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($ba['bank_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-secondary ml-2">Filter</button>
                            <button class="btn btn-primary" id="addReceiptBtn" type='button'
                                <?php echo $locationFilter === 0 ? 'disabled' : ''; ?>>
                                <i class="fa fa-plus"></i> New Receipt
                            </button>
                        </form>

                    </div>
                    <div class="card-block">
                        <div class="table-responsive">
                            <table id="receiptTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Receipt No</th>
                                        <th width='80'>Date</th>
                                        <th>Bank</th>
                                        <th>Income Account</th>
                                        <th>Memo</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($receipts as $r): ?>
                                    <tr>
                                        <td><?php echo (int)$r['rec_id']; ?></td>
                                        <td><?php echo htmlspecialchars($r['receipt_number']); ?></td>
                                        <td><?php echo htmlspecialchars($r['rec_date']); ?></td>
                                        <td><?php echo htmlspecialchars($r['bank_name']); ?></td>
                                        <td><?php echo htmlspecialchars(incomeLabel($r, $primaryLanguage)); ?></td>
                                        <td><?php echo htmlspecialchars($r['memo']); ?></td>
                                        <td align='right'><?php echo number_format((float)$r['amount'], 2); ?></td>
                                        <td><?php echo ((int)$r['status'] === 1) ? 'Active' : 'Inactive'; ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-secondary dropdown-toggle" type="button"
                                                    data-toggle="dropdown">
                                                    Action
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item edit-receipt" href="#"
                                                        data-id="<?php echo (int)$r['rec_id']; ?>"
                                                        data-bank="<?php echo (int)$r['bank_id']; ?>"
                                                        data-date="<?php echo htmlspecialchars($r['rec_date']); ?>"
                                                        data-receipt="<?php echo htmlspecialchars($r['receipt_number']); ?>"
                                                        data-income="<?php echo (int)$r['income_account']; ?>"
                                                        data-memo="<?php echo htmlspecialchars($r['memo']); ?>"
                                                        data-amount="<?php echo htmlspecialchars($r['amount']); ?>"
                                                        data-status="<?php echo (int)$r['status']; ?>">
                                                        <i class="fa fa-pencil"></i> Edit
                                                    </a>
                                                    <a class="dropdown-item delete-receipt" href="#"
                                                        data-id="<?php echo (int)$r['rec_id']; ?>">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-labelledby="receiptModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="receiptForm">
                <input type="hidden" name="save_receipt" value="1">
                <input type="hidden" name="rec_id" id="rec_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">New Receipt</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Receipt No</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="receipt_number" id="receipt_number"
                                placeholder="Enter receipt number" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right" for="rec_date">Date</label>
                        <div class="col-sm-8">
                            <input type="date" class="form-control" name="rec_date" id="rec_date"
                                value="<?php echo htmlspecialchars($defaultDate); ?>" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right" for="bank_id">Bank</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="bank_id" id="bank_id" required>
                                <option value="">Select</option>
                                <?php foreach ($bankAccounts as $ba): ?>
                                <option value="<?php echo (int)$ba['id']; ?>">
                                    <?php echo htmlspecialchars($ba['bank_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right" for="income_account">Income Account</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="income_account" id="income_account" required>
                                <option value="">Select</option>
                                <?php foreach ($incomeAccounts as $inc): ?>
                                <option value="<?php echo (int)$inc['r_id']; ?>">
                                    <?php echo htmlspecialchars(incomeLabel($inc, $primaryLanguage)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right" for="amount">Amount</label>
                        <div class="col-sm-8">
                            <input type="number" step="0.01" class="form-control" name="amount" id="amount" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right" for="memo">Memo</label>
                        <div class="col-sm-8">
                            <textarea class="form-control" name="memo" id="memo" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete form -->
<form method="POST" id="deleteReceiptForm" style="display:none;">
    <input type="hidden" name="delete_receipt" value="1">
    <input type="hidden" name="delete_id" id="delete_id">
</form>

<?php include 'footer.php'; ?>

<script>
$(document).ready(function() {
    $('#receiptTable').DataTable({
        pageLength: 100,
        order: [[2, 'desc'], [0, 'desc']]
    });

    function resetForm() {
        $('#rec_id').val('');
        $('#receipt_number').val('');
        $('#rec_date').val('<?php echo htmlspecialchars($defaultDate); ?>');
        $('#bank_id').val('<?php echo (int)$defaultBank; ?>');
        $('#income_account').val('');
        $('#amount').val('');
        $('#memo').val('');
        $('#receiptModalLabel').text('New Receipt');
    }

    $('#addReceiptBtn').on('click', function() {
        resetForm();
        $('#receiptModal').modal('show');
    });

    $('.edit-receipt').on('click', function(e) {
        e.preventDefault();
        resetForm();
        $('#rec_id').val($(this).data('id'));
        $('#receipt_number').val($(this).data('receipt'));
        $('#rec_date').val($(this).data('date'));
        $('#bank_id').val($(this).data('bank'));
        $('#income_account').val($(this).data('income'));
        $('#amount').val($(this).data('amount'));
        $('#memo').val($(this).data('memo'));
        $('#receiptModalLabel').text('Edit Receipt');
        $('#receiptModal').modal('show');
    });

    $('.delete-receipt').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (confirm('Delete this receipt?')) {
            $('#delete_id').val(id);
            $('#deleteReceiptForm').submit();
        }
    });

    $('#receiptForm').on('submit', function() {
        var amt = parseFloat($('#amount').val() || 0);
        if (amt <= 0) {
            alert('Amount must be greater than zero.');
            return false;
        }
        if (!$('#rec_date').val()) {
            alert('Date is required.');
            return false;
        }
        if (!$('#bank_id').val() || !$('#income_account').val()) {
            alert('Please select bank and income account.');
            return false;
        }
        return true;
    });
});
</script>
