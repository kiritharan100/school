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

// Default bank from cookie
$defaultBank = 0;
if (isset($_COOKIE['payment_bank']) && ctype_digit($_COOKIE['payment_bank'])) {
    $defaultBank = (int)$_COOKIE['payment_bank'];
}

// Date range filters default to current year
$currentYear = (int)date('Y');
$fromDate = isset($_GET['from_date']) && $_GET['from_date'] !== '' ? $_GET['from_date'] : $currentYear . '-01-01';
$toDate = isset($_GET['to_date']) && $_GET['to_date'] !== '' ? $_GET['to_date'] : $currentYear . '-12-31';
$filterBank = isset($_GET['filter_bank']) ? (int)$_GET['filter_bank'] : (isset($_COOKIE['payment_filter_bank']) ? (int)$_COOKIE['payment_filter_bank'] : 0);

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

// Load suppliers
$suppliers = [];
if ($locationFilter > 0) {
    $res = mysqli_query($con, "
        SELECT sup_id, supplier_name, income_account, expense_account
        FROM manage_supplier
        WHERE location_id = $locationFilter AND status = 1
        ORDER BY supplier_name ASC
    ");
    while ($row = mysqli_fetch_assoc($res)) {
        $suppliers[] = $row;
    }
}

// Load expense accounts
$expenseAccounts = [];
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
    usort($expenseAccounts, function($a,$b){ return strnatcasecmp($a['ex_code'],$b['ex_code']); });
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
    usort($incomeAccounts, function($a,$b){ return strnatcasecmp($a['revinue_code'],$b['revinue_code']); });
}

// Language helper
$primaryLanguage = 'english';
if (isset($client_language)) {
    $lang = strtolower($client_language);
    if (in_array($lang, ['tamil', 'sinhala'], true)) {
        $primaryLanguage = $lang;
    }
}
function revLabel($row, $lang) {
    if ($lang === 'tamil' && !empty($row['revinue_tamil'])) return $row['revinue_code'].' - '.$row['revinue_tamil'];
    if ($lang === 'sinhala' && !empty($row['revinue_sinhala'])) return $row['revinue_code'].' - '.$row['revinue_sinhala'];
    return $row['revinue_code'].' - '.$row['detail_of_revinue'];
}
function expLabel($row, $lang) {
    if ($lang === 'tamil' && !empty($row['ex_tamil'])) return $row['ex_code'].' - '.$row['ex_tamil'];
    if ($lang === 'sinhala' && !empty($row['ex_sinhala'])) return $row['ex_code'].' - '.$row['ex_sinhala'];
    return $row['ex_code'].' - '.$row['ex_english'];
}

// CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_payment'])) {
        $payId  = isset($_POST['pay_id']) ? (int)$_POST['pay_id'] : 0;
        $bankId = isset($_POST['bank_id']) ? (int)$_POST['bank_id'] : 0;
        $supplierId = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
        $expId  = isset($_POST['expense_account']) ? (int)$_POST['expense_account'] : 0;
        $incId  = isset($_POST['income_account']) ? (int)$_POST['income_account'] : 0;
        $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
        $memo   = mysqli_real_escape_string($con, trim($_POST['memo'] ?? ''));
        $voucher = mysqli_real_escape_string($con, trim($_POST['voutcher_number'] ?? ''));
        $cheque = isset($_POST['cheque_number']) ? (int)$_POST['cheque_number'] : 0;
        $date   = mysqli_real_escape_string($con, trim($_POST['pay_date'] ?? ''));
        $status = 1;

        if ($locationFilter === 0) {
            $flash = ['type'=>'danger','text'=>'Select a location first.'];
        } elseif ($bankId === 0) {
            $flash = ['type'=>'danger','text'=>'Bank is required.'];
        } elseif ($amount <= 0) {
            $flash = ['type'=>'danger','text'=>'Amount must be greater than zero.'];
        } elseif ($date === '') {
            $flash = ['type'=>'danger','text'=>'Date is required.'];
        } else {
            setcookie('transaction_date', $date, time()+86400*180, '/');
            setcookie('payment_bank', $bankId, time()+86400*180, '/');
            setcookie('payment_filter_bank', $bankId, time()+86400*180, '/');

            // record_number generation
            $recordNo = 0;
            if ($payId > 0) {
                $existing = mysqli_query($con, "SELECT record_number FROM bank_payment WHERE pay_id=$payId AND location_id=$locationFilter");
                if ($existing && mysqli_num_rows($existing)>0) {
                    $row = mysqli_fetch_assoc($existing);
                    $recordNo = (int)$row['record_number'];
                }
            }
            if ($recordNo === 0) {
                $maxRes = mysqli_query($con, "SELECT COALESCE(MAX(record_number),0) AS mx FROM bank_payment WHERE location_id=$locationFilter");
                $maxRow = $maxRes ? mysqli_fetch_assoc($maxRes) : ['mx'=>0];
                $recordNo = (int)($maxRow['mx'] ?? 0) + 1;
            }

            if ($payId > 0) {
                $sql = "
                    UPDATE bank_payment SET
                        pay_date = '$date',
                        bank_id = $bankId,
                        record_number = $recordNo,
                        voutcher_number = '$voucher',
                        supplier_id = $supplierId,
                        expense_account = $expId,
                        income_account = $incId,
                        amount = $amount,
                        memo = '$memo',
                        cheque_number = $cheque,
                        status = $status
                    WHERE pay_id = $payId AND location_id = $locationFilter
                ";
                $ok = mysqli_query($con, $sql);
            } else {
                $sql = "
                    INSERT INTO bank_payment (pay_date, bank_id, record_number, voutcher_number, supplier_id, expense_account, income_account, amount, memo, cheque_number, status, location_id)
                    VALUES ('$date', $bankId, $recordNo, '$voucher', $supplierId, $expId, $incId, $amount, '$memo', $cheque, $status, $locationFilter)
                ";
                $ok = mysqli_query($con, $sql);
                if ($ok) {
                    $payId = mysqli_insert_id($con);
                }
            }

            if ($ok) {
                // sync transaction: debit expense, credit bank
                $txnId = $recordNo;
                $memoSql = "'$memo'";
                $dateSql = "'$date'";
                $voucherSql = "'".$voucher."'";
                mysqli_query($con, "DELETE FROM transaction WHERE transaction_id=$txnId AND transaction_type='Payment' AND location_id=$locationFilter");
                // Debit expense
                mysqli_query($con, "
                    INSERT INTO transaction (location_id, transaction_id, transaction_type, voutcher_number, tr_date, bank_account_id, income_account, expenses_account, memo, debit, credit, supplier_id, status)
                    VALUES ($locationFilter, $txnId, 'Payment', $voucherSql, $dateSql, 0, 0, $expId, $memoSql, $amount, 0, $supplierId, $status)
                ");
                // Credit bank
                mysqli_query($con, "
                    INSERT INTO transaction (location_id, transaction_id, transaction_type, voutcher_number, tr_date, bank_account_id, income_account, expenses_account, memo, debit, credit, supplier_id, status)
                    VALUES ($locationFilter, $txnId, 'Payment', $voucherSql, $dateSql, $bankId, 0, 0, $memoSql, 0, $amount, $supplierId, $status)
                ");
                $flash = ['type'=>'success','text'=>'Payment saved.'];
            } else {
                $flash = ['type'=>'danger','text'=>'Failed to save: '.mysqli_error($con)];
            }
        }
    }

    if (isset($_POST['delete_payment'])) {
        $delId = isset($_POST['delete_id']) ? (int)$_POST['delete_id'] : 0;
        if ($delId > 0 && $locationFilter > 0) {
            $recRes = mysqli_query($con, "SELECT record_number FROM bank_payment WHERE pay_id=$delId AND location_id=$locationFilter");
            $recordNo = 0;
            if ($recRes && mysqli_num_rows($recRes)>0) {
                $row = mysqli_fetch_assoc($recRes);
                $recordNo = (int)$row['record_number'];
            }
            mysqli_query($con, "DELETE FROM bank_payment WHERE pay_id=$delId AND location_id=$locationFilter");
            if ($recordNo > 0) {
                mysqli_query($con, "DELETE FROM transaction WHERE transaction_id=$recordNo AND transaction_type='Payment' AND location_id=$locationFilter");
            }
            $flash = ['type'=>'success','text'=>'Payment deleted.'];
        }
    }
}

// Load payments
$payments = [];
if ($locationFilter > 0) {
    $res = mysqli_query($con, "
        SELECT bp.*, ba.bank_name, ms.supplier_name, ec.ex_code, ec.ex_english, ec.ex_tamil, ec.ex_sinhala,
               rc.revinue_code, rc.detail_of_revinue, rc.revinue_tamil, rc.revinue_sinhala
        FROM bank_payment bp
        LEFT JOIN bank_account ba ON ba.id = bp.bank_id
        LEFT JOIN manage_supplier ms ON ms.sup_id = bp.supplier_id
        LEFT JOIN expenditure_code ec ON ec.ex_id = bp.expense_account
        LEFT JOIN revinue_code rc ON rc.r_id = bp.income_account
        WHERE bp.location_id = $locationFilter
          AND bp.pay_date BETWEEN '$fromDate' AND '$toDate'
          " . ($filterBank > 0 ? " AND bp.bank_id = $filterBank " : "") . "
        ORDER BY bp.pay_date DESC, bp.pay_id DESC
    ");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $payments[] = $row;
        }
    } else {
        $flash = ['type'=>'danger','text'=>'Failed to load payments: '.mysqli_error($con)];
    }
}

// persist bank filter cookie
setcookie('payment_filter_bank', (int)$filterBank, time()+86400*180, '/');
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 p-0">
                <div class="main-header">
                    <h4>Bank Payment</h4>
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
                            <button type='button' class="btn btn-primary" id="addPaymentBtn"
                                <?php echo $locationFilter === 0 ? 'disabled' : ''; ?>>
                                <i class="fa fa-plus"></i> New Payment
                            </button>
                        </form>

                    </div>
                    <div class="card-block">
                        <div class="table-responsive">
                            <table id="paymentTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Voucher</th>
                                        <th>Cheque</th>
                                        <th>Bank</th>
                                        <th>Supplier</th>
                                        <th>Income</th>
                                        <th>Expense</th>
                                        <th>Amount</th>
                                        <th>Memo</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $p): ?>
                                    <tr>
                                        <td><?php echo (int)$p['pay_id']; ?></td>
                                        <td><?php echo htmlspecialchars($p['pay_date']); ?></td>
                                        <td><?php echo htmlspecialchars($p['voutcher_number']); ?></td>
                                        <td><?php echo htmlspecialchars($p['cheque_number']); ?></td>
                                        <td><?php echo htmlspecialchars($p['bank_name']); ?></td>
                                        <td><?php echo htmlspecialchars($p['supplier_name']); ?></td>
                                        <td><?php echo htmlspecialchars($p['revinue_code']); ?></td>
                                        <td><?php echo htmlspecialchars($p['ex_code']); ?></td>
                                        <td><?php echo number_format((float)$p['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($p['memo']); ?></td>
                                        <td><?php echo ((int)$p['status'] === 1) ? 'Active' : 'Inactive'; ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-secondary dropdown-toggle" type="button"
                                                    data-toggle="dropdown">
                                                    Action
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item edit-payment" href="#"
                                                        data-id="<?php echo (int)$p['pay_id']; ?>"
                                                        data-date="<?php echo htmlspecialchars($p['pay_date']); ?>"
                                                        data-bank="<?php echo (int)$p['bank_id']; ?>"
                                                        data-voucher="<?php echo htmlspecialchars($p['voutcher_number']); ?>"
                                                        data-cheque="<?php echo htmlspecialchars($p['cheque_number']); ?>"
                                                        data-supplier="<?php echo (int)$p['supplier_id']; ?>"
                                                        data-expense="<?php echo (int)$p['expense_account']; ?>"
                                                        data-income="<?php echo (int)$p['income_account']; ?>"
                                                        data-amount="<?php echo htmlspecialchars($p['amount']); ?>"
                                                        data-memo="<?php echo htmlspecialchars($p['memo']); ?>">
                                                        <i class="fa fa-pencil"></i> Edit
                                                    </a>
                                                    <a class="dropdown-item delete-payment" href="#"
                                                        data-id="<?php echo (int)$p['pay_id']; ?>">
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
<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="paymentForm">
                <input type="hidden" name="save_payment" value="1">
                <input type="hidden" name="pay_id" id="pay_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">New Payment</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right" for="pay_date">Date</label>
                        <div class="col-sm-8">
                            <input type="date" class="form-control" name="pay_date" id="pay_date"
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
                        <label class="col-sm-4 col-form-label text-right" for="voutcher_number">Voucher No</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="voutcher_number" id="voutcher_number"
                                placeholder="Enter voucher (suggested)">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right" for="cheque_number">Cheque No</label>
                        <div class="col-sm-8">
                            <input type="number" class="form-control" name="cheque_number" id="cheque_number"
                                placeholder="Enter cheque (suggested)">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right" for="supplier_id">Supplier</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="supplier_id" id="supplier_id">
                                <option value="0">Select</option>
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?php echo (int)$s['sup_id']; ?>"
                                    data-inc="<?php echo (int)$s['income_account']; ?>"
                                    data-exp="<?php echo (int)$s['expense_account']; ?>">
                                    <?php echo htmlspecialchars($s['supplier_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right" for="income_account">Income Account</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="income_account" id="income_account">
                                <option value="0">None</option>
                                <?php foreach ($incomeAccounts as $inc): ?>
                                <option value="<?php echo (int)$inc['r_id']; ?>">
                                    <?php echo htmlspecialchars(revLabel($inc, $primaryLanguage)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right" for="expense_account">Expense Account</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="expense_account" id="expense_account">
                                <option value="0">None</option>
                                <?php foreach ($expenseAccounts as $exp): ?>
                                <option value="<?php echo (int)$exp['ex_id']; ?>">
                                    <?php echo htmlspecialchars(expLabel($exp, $primaryLanguage)); ?></option>
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
<form method="POST" id="deletePaymentForm" style="display:none;">
    <input type="hidden" name="delete_payment" value="1">
    <input type="hidden" name="delete_id" id="delete_id">
</form>

<?php include 'footer.php'; ?>

<script>
$(document).ready(function() {
    $('#paymentTable').DataTable({
        pageLength: 100
    });

    // Enhance supplier dropdown with Select2 inside modal
    $('#supplier_id').select2({
        dropdownParent: $('#paymentModal'),
        width: '100%',
        placeholder: 'Select supplier'
    });

    function resetForm() {
        $('#pay_id').val('');
        $('#pay_date').val('<?php echo htmlspecialchars($defaultDate); ?>');
        $('#bank_id').val('<?php echo (int)$defaultBank; ?>');
        $('#voutcher_number').val('');
        $('#cheque_number').val('');
        $('#supplier_id').val('0');
        $('#income_account').val('0');
        $('#expense_account').val('0');
        $('#amount').val('');
        $('#memo').val('');
        $('#paymentModalLabel').text('New Payment');
        loadAutoNumbers($('#bank_id').val());
    }

    function loadAutoNumbers(bankId) {
        if (!bankId) return;
        $.getJSON('ajax/payment_helpers.php', {
            action: 'voucher_next',
            bank_id: bankId,
            location_id: <?php echo (int)$locationFilter; ?>
        }, function(resp) {
            if (resp && resp.voucher !== undefined) {
                $('#voutcher_number').val(resp.voucher);
            }
        });
        $.getJSON('ajax/payment_helpers.php', {
            action: 'cheque_next',
            bank_id: bankId,
            location_id: <?php echo (int)$locationFilter; ?>
        }, function(resp) {
            if (resp && resp.cheque !== undefined) {
                $('#cheque_number').val(resp.cheque);
            }
        });
    }

    $('#bank_id').on('change', function() {
        loadAutoNumbers($(this).val());
    });

    $('#supplier_id').on('change', function() {
        var opt = $(this).find(':selected');
        var inc = opt.data('inc') || 0;
        var exp = opt.data('exp') || 0;
        if (inc) $('#income_account').val(inc);
        if (exp) $('#expense_account').val(exp);
    });

    $('#addPaymentBtn').on('click', function() {
        resetForm();
        $('#paymentModal').modal('show');
    });

    $('.edit-payment').on('click', function(e) {
        e.preventDefault();
        resetForm();
        $('#pay_id').val($(this).data('id'));
        $('#pay_date').val($(this).data('date'));
        $('#bank_id').val($(this).data('bank'));
        $('#voutcher_number').val($(this).data('voucher'));
        $('#cheque_number').val($(this).data('cheque'));
        $('#supplier_id').val($(this).data('supplier'));
        $('#income_account').val($(this).data('income'));
        $('#expense_account').val($(this).data('expense'));
        $('#amount').val($(this).data('amount'));
        $('#memo').val($(this).data('memo'));
        $('#paymentModalLabel').text('Edit Payment');
        $('#paymentModal').modal('show');
    });

    $('.delete-payment').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (confirm('Delete this payment?')) {
            $('#delete_id').val(id);
            $('#deletePaymentForm').submit();
        }
    });

    $('#paymentForm').on('submit', function() {
        var amt = parseFloat($('#amount').val() || 0);
        if (amt <= 0) {
            alert('Amount must be greater than zero.');
            return false;
        }
        if (!$('#pay_date').val()) {
            alert('Date is required.');
            return false;
        }
        if (!$('#bank_id').val()) {
            alert('Please select bank.');
            return false;
        }
        return true;
    });
});
</script>