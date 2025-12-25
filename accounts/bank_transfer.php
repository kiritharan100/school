<?php
// Enable output buffering to allow header operations (setcookie) after includes
ob_start();
include 'header.php';

$locationFilter = isset($location_id) && $location_id !== '' ? (int)$location_id : 0;
$flash = ['type' => '', 'text' => ''];

// Date range filter defaults to current year
$currentYear = (int)date('Y');
$fromDate = isset($_GET['from_date']) && $_GET['from_date'] !== '' ? $_GET['from_date'] : $currentYear . '-01-01';
$toDate = isset($_GET['to_date']) && $_GET['to_date'] !== '' ? $_GET['to_date'] : $currentYear . '-12-31';

// Preferred transaction date from cookie (fallback to today)
$defaultTrDate = date('Y-m-d');
if (isset($_COOKIE['transaction_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_COOKIE['transaction_date'])) {
    $defaultTrDate = $_COOKIE['transaction_date'];
}

// Load bank accounts for dropdown
$bankAccounts = [];
if ($locationFilter > 0) {
    $baRes = mysqli_query($con, "
        SELECT id, bank_name, opening_balance, balance_as_it, status
        FROM bank_account
        WHERE location_id = $locationFilter AND status = 1
        ORDER BY bank_name ASC
    ");
    while ($row = mysqli_fetch_assoc($baRes)) {
        $bankAccounts[] = $row;
    }
}

function sanitize($con, $val) {
    return mysqli_real_escape_string($con, trim($val));
}

// CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create/Update
    if (isset($_POST['save_transfer'])) {
        $transferId = isset($_POST['transfer_id']) ? (int)$_POST['transfer_id'] : 0;
        $trDate = sanitize($con, $_POST['tr_date'] ?? '');
        $txnNumber = 0; // system generated
        $fromAcc = isset($_POST['from_account']) ? (int)$_POST['from_account'] : 0;
        $toAcc = isset($_POST['to_account']) ? (int)$_POST['to_account'] : 0;
        $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
        $status = 1; // hidden; default active
        $memo = sanitize($con, $_POST['memo'] ?? '');

        if ($locationFilter === 0) {
            $flash = ['type' => 'danger', 'text' => 'Select a location first.'];
        } elseif ($fromAcc === 0 || $toAcc === 0) {
            $flash = ['type' => 'danger', 'text' => 'From and To accounts are required.'];
        } elseif ($fromAcc === $toAcc) {
            $flash = ['type' => 'danger', 'text' => 'From and To accounts cannot be the same.'];
        } elseif ($amount <= 0) {
            $flash = ['type' => 'danger', 'text' => 'Amount must be greater than zero.'];
        } elseif ($trDate === '') {
            $flash = ['type' => 'danger', 'text' => 'Date is required.'];
        } else {
            // persist selected date in cookie for future use
            setcookie('transaction_date', $trDate, time() + (86400 * 180), '/');
            // Get existing txn number if editing
            if ($transferId > 0) {
                $existingRes = mysqli_query($con, "SELECT transaction_number FROM bank_transfer WHERE id=$transferId AND location_id=$locationFilter");
                if ($existingRes && mysqli_num_rows($existingRes) > 0) {
                    $row = mysqli_fetch_assoc($existingRes);
                    $txnNumber = (int)$row['transaction_number'];
                }
            }

            if ($transferId > 0) {
                $sql = "
                    UPDATE bank_transfer SET
                        transaction_number = $txnNumber,
                        from_account = $fromAcc,
                        to_account = $toAcc,
                        amount = $amount,
                        status = $status
                    WHERE id = $transferId AND location_id = $locationFilter
                ";
                $ok = mysqli_query($con, $sql);
            } else {
                $sql = "
                    INSERT INTO bank_transfer (transaction_number, location_id, from_account, to_account, amount, status)
                    VALUES (0, $locationFilter, $fromAcc, $toAcc, $amount, $status)
                ";
                $ok = mysqli_query($con, $sql);
                if ($ok) {
                    $transferId = mysqli_insert_id($con);
                    $txnNumber = $transferId;
                    mysqli_query($con, "UPDATE bank_transfer SET transaction_number=$txnNumber WHERE id=$transferId");
                }
            }

            if ($ok) {
                // remove existing transactions for this transfer
                mysqli_query($con, "DELETE FROM transaction WHERE transaction_id=$txnNumber AND transaction_type='Transfer' AND location_id=$locationFilter");

                // insert credit (from account)
                $memoSql = $memo !== '' ? "'" . sanitize($con, $memo) . "'" : "''";
                $tDateSql = "'" . $trDate . "'";
                mysqli_query($con, "
                    INSERT INTO transaction (location_id, transaction_id, transaction_type, voutcher_number, tr_date, bank_account_id, income_account, expenses_account, memo, debit, credit, supplier_id, status)
                    VALUES ($locationFilter, $txnNumber, 'Transfer', 'BT-$txnNumber', $tDateSql, $fromAcc, 0, 0, $memoSql, 0, $amount, 0, $status)
                ");

                // insert debit (to account)
                mysqli_query($con, "
                    INSERT INTO transaction (location_id, transaction_id, transaction_type, voutcher_number, tr_date, bank_account_id, income_account, expenses_account, memo, debit, credit, supplier_id, status)
                    VALUES ($locationFilter, $txnNumber, 'Transfer', 'BT-$txnNumber', $tDateSql, $toAcc, 0, 0, $memoSql, $amount, 0, 0, $status)
                ");

                $flash = ['type' => 'success', 'text' => 'Transfer saved.'];
            } else {
                $flash = ['type' => 'danger', 'text' => 'Failed to save transfer: ' . mysqli_error($con)];
            }
        }
    }

    // Delete
    if (isset($_POST['delete_transfer'])) {
        $delId = isset($_POST['delete_id']) ? (int)$_POST['delete_id'] : 0;
        if ($delId > 0 && $locationFilter > 0) {
            $tnRes = mysqli_query($con, "SELECT transaction_number FROM bank_transfer WHERE id=$delId AND location_id=$locationFilter");
            $txnNumber = '';
            if ($tnRes && mysqli_num_rows($tnRes) > 0) {
                $row = mysqli_fetch_assoc($tnRes);
                $txnNumber = $row['transaction_number'];
            }
            mysqli_query($con, "DELETE FROM bank_transfer WHERE id=$delId AND location_id=$locationFilter");
            if ($txnNumber !== '') {
                mysqli_query($con, "DELETE FROM transaction WHERE transaction_id=$txnNumber AND transaction_type='Transfer' AND location_id=$locationFilter");
            }
            $flash = ['type' => 'success', 'text' => 'Transfer deleted.'];
        }
    }
}

// Load transfers
$transfers = [];
if ($locationFilter > 0) {
    $tRes = mysqli_query($con, "
        SELECT bt.*, bf.bank_name AS from_name, bt2.bank_name AS to_name,
            (SELECT tr_date FROM transaction t WHERE t.transaction_id = bt.transaction_number AND t.transaction_type='Transfer' AND t.location_id = bt.location_id ORDER BY t.tr_date ASC LIMIT 1) AS tr_date
        FROM bank_transfer bt
        LEFT JOIN bank_account bf ON bf.id = bt.from_account
        LEFT JOIN bank_account bt2 ON bt2.id = bt.to_account
        WHERE bt.location_id = $locationFilter
          AND EXISTS (
              SELECT 1 FROM transaction t
              WHERE t.transaction_id = bt.transaction_number
                AND t.transaction_type = 'Transfer'
                AND t.location_id = bt.location_id
                AND t.tr_date BETWEEN '$fromDate' AND '$toDate'
          )
        ORDER BY bt.id DESC
    ");
    while ($row = mysqli_fetch_assoc($tRes)) {
        $transfers[] = $row;
    }
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 p-0">
                <div class="main-header">
                    <h4>Bank Transfer</h4>
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
                            <button type="submit" class="btn btn-secondary ml-2">Filter</button>
                            <button class="btn btn-primary" id="addTransferBtn" type='button'
                                <?php echo $locationFilter === 0 ? 'disabled' : ''; ?>>
                                <i class="fa fa-plus"></i> New Transfer
                            </button>
                        </form>
                    </div>
                    <div class="card-block">
                        <div class="table-responsive">
                            <table id="transferTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Txn No</th>
                                        <th>Date</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transfers as $tr): ?>
                                    <tr>
                                        <td><?php echo (int)$tr['id']; ?></td>
                                        <td><?php echo htmlspecialchars($tr['transaction_number']); ?></td>
                                        <td><?php echo htmlspecialchars($tr['tr_date']); ?></td>
                                        <td><?php echo htmlspecialchars($tr['from_name']); ?></td>
                                        <td><?php echo htmlspecialchars($tr['to_name']); ?></td>
                                        <td><?php echo number_format((float)$tr['amount'], 2); ?></td>
                                        <td><?php echo ((int)$tr['status'] === 1) ? 'Active' : 'Inactive'; ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-secondary dropdown-toggle" type="button"
                                                    data-toggle="dropdown">
                                                    Action
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item edit-transfer" href="#"
                                                        data-id="<?php echo (int)$tr['id']; ?>"
                                                        data-txn="<?php echo htmlspecialchars($tr['transaction_number']); ?>"
                                                        data-date="<?php echo htmlspecialchars($tr['tr_date']); ?>"
                                                        data-from="<?php echo (int)$tr['from_account']; ?>"
                                                        data-to="<?php echo (int)$tr['to_account']; ?>"
                                                        data-amount="<?php echo htmlspecialchars($tr['amount']); ?>"
                                                        data-status="<?php echo (int)$tr['status']; ?>">
                                                        <i class="fa fa-pencil"></i> Edit
                                                    </a>
                                                    <a class="dropdown-item delete-transfer" href="#"
                                                        data-id="<?php echo (int)$tr['id']; ?>">
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
<div class="modal fade" id="transferModal" tabindex="-1" role="dialog" aria-labelledby="transferModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="transferForm">
                <input type="hidden" name="save_transfer" value="1">
                <input type="hidden" name="transfer_id" id="transfer_id">
                <input type="hidden" name="transaction_number" id="transaction_number">
                <div class="modal-header">
                    <h5 class="modal-title" id="transferModalLabel">New Transfer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Transaction Number</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="transaction_number_display" disabled>
                            <small class="text-muted">Auto-generated</small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right" for="tr_date">Date</label>
                        <div class="col-sm-8">
                            <input type="date" class="form-control" name="tr_date" id="tr_date"
                                value="<?php echo htmlspecialchars($defaultTrDate); ?>" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right" for="from_account">From Account</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="from_account" id="from_account" required>
                                <option value="">Select</option>
                                <?php foreach ($bankAccounts as $ba): ?>
                                <option value="<?php echo (int)$ba['id']; ?>">
                                    <?php echo htmlspecialchars($ba['bank_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right" for="to_account">To Account</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="to_account" id="to_account" required>
                                <option value="">Select</option>
                                <?php foreach ($bankAccounts as $ba): ?>
                                <option value="<?php echo (int)$ba['id']; ?>">
                                    <?php echo htmlspecialchars($ba['bank_name']); ?></option>
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
<form method="POST" id="deleteTransferForm" style="display:none;">
    <input type="hidden" name="delete_transfer" value="1">
    <input type="hidden" name="delete_id" id="delete_id">
</form>

<?php include 'footer.php'; ?>

<script>
$(document).ready(function() {
    $('#transferTable').DataTable({
        pageLength: 100
    });

    function resetForm() {
        $('#transfer_id').val('');
        $('#transaction_number').val('');
        $('#transaction_number_display').val('');
        $('#tr_date').val('<?php echo htmlspecialchars($defaultTrDate); ?>');
        $('#from_account').val('');
        $('#to_account').val('');
        $('#amount').val('');
        $('#memo').val('');
        $('#status').val('1');
        $('#transferModalLabel').text('New Transfer');
    }

    $('#addTransferBtn').on('click', function() {
        resetForm();
        $('#transferModal').modal('show');
    });

    $('.edit-transfer').on('click', function(e) {
        e.preventDefault();
        resetForm();
        $('#transfer_id').val($(this).data('id'));
        $('#transaction_number').val($(this).data('txn'));
        $('#transaction_number_display').val($(this).data('txn'));
        $('#tr_date').val($(this).data('date'));
        $('#from_account').val($(this).data('from'));
        $('#to_account').val($(this).data('to'));
        $('#amount').val($(this).data('amount'));
        $('#status').val($(this).data('status'));
        $('#transferModalLabel').text('Edit Transfer');
        $('#transferModal').modal('show');
    });

    $('.delete-transfer').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (confirm('Delete this transfer?')) {
            $('#delete_id').val(id);
            $('#deleteTransferForm').submit();
        }
    });

    $('#transferForm').on('submit', function() {
        var from = $('#from_account').val();
        var to = $('#to_account').val();
        var amt = parseFloat($('#amount').val() || 0);
        var dateVal = $('#tr_date').val();
        var cy = <?php echo (int)date('Y'); ?>;
        if (dateVal) {
            var year = parseInt(dateVal.substring(0, 4), 10);
            if (year !== cy) {
                if (!confirm(
                        'You are recording a transfer for a different year. This will affect that year\'s records. Continue?'
                    )) {
                    return false;
                }
            }
        }
        if (!from || !to) {
            alert('Please select both From and To accounts.');
            return false;
        }
        if (from === to) {
            alert('From and To accounts cannot be the same.');
            return false;
        }
        if (amt <= 0) {
            alert('Amount must be greater than zero.');
            return false;
        }
        if (!$('#tr_date').val()) {
            alert('Date is required.');
            return false;
        }
        return true;
    });
});
</script>