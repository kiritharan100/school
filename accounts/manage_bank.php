<?php include 'header.php';

$locationFilter = isset($location_id) && $location_id !== '' ? (int)$location_id : 0;
$flash = ['type' => '', 'text' => ''];

function require_date_if_opening(float $opening, string $date): bool {
    return $opening == 0.0 || $date !== '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add or update
    if (isset($_POST['save_bank'])) {
        $bankId  = isset($_POST['bank_id']) ? (int)$_POST['bank_id'] : 0;
        $name    = mysqli_real_escape_string($con, trim($_POST['bank_name'] ?? ''));
        $opening = isset($_POST['opening_balance']) ? (float)$_POST['opening_balance'] : 0;
        $asAt    = mysqli_real_escape_string($con, trim($_POST['balance_as_it'] ?? ''));
        $status  = isset($_POST['status']) ? (int)$_POST['status'] : 1;

        if ($locationFilter === 0) {
            $flash = ['type' => 'danger', 'text' => 'Select a location first.'];
        } elseif ($name === '') {
            $flash = ['type' => 'danger', 'text' => 'Bank / cash account name is required.'];
        } elseif (!require_date_if_opening($opening, $asAt)) {
            $flash = ['type' => 'danger', 'text' => 'Please set Balance As At date when an opening balance is entered.'];
        } else {
            // Check if transactions exist
            $locked = false;
            if ($bankId > 0) {
                $txnRes = mysqli_query($con, "SELECT 1 FROM transaction WHERE bank_account_id = $bankId AND status = 1 LIMIT 1");
                $locked = $txnRes && mysqli_num_rows($txnRes) > 0;
            }

            if ($bankId > 0) {
                $sql = "
                    UPDATE bank_account SET
                        bank_name = '$name',
                        status = $status
                        " . (!$locked ? ", opening_balance = $opening, balance_as_it = " . ($asAt !== '' ? "'$asAt'" : "NULL") : "") . "
                    WHERE id = $bankId AND location_id = $locationFilter
                ";
                if (mysqli_query($con, $sql)) {
                    $flash = ['type' => 'success', 'text' => 'Account updated successfully.' . ($locked ? ' (Opening balance/date locked due to transactions.)' : '')];
                } else {
                    $flash = ['type' => 'danger', 'text' => 'Failed to update account.'];
                }
            } else {
                $sql = "
                    INSERT INTO bank_account (location_id, bank_name, opening_balance, balance_as_it, status)
                    VALUES ($locationFilter, '$name', $opening, " . ($asAt !== '' ? "'$asAt'" : "NULL") . ", $status)
                ";
                if (mysqli_query($con, $sql)) {
                    $flash = ['type' => 'success', 'text' => 'Account created successfully.'];
                } else {
                    $flash = ['type' => 'danger', 'text' => 'Failed to create account.'];
                }
            }
        }
    }

    // Delete
    if (isset($_POST['delete_bank'])) {
        $bankId = isset($_POST['delete_id']) ? (int)$_POST['delete_id'] : 0;
        if ($bankId > 0 && $locationFilter > 0) {
            $txnRes = mysqli_query($con, "SELECT 1 FROM transaction WHERE bank_account_id = $bankId LIMIT 1");
            if ($txnRes && mysqli_num_rows($txnRes) > 0) {
                $flash = ['type' => 'danger', 'text' => 'Cannot delete account with transactions.'];
            } else {
                mysqli_query($con, "DELETE FROM bank_account WHERE id = $bankId AND location_id = $locationFilter");
                $flash = ['type' => 'success', 'text' => 'Account deleted.'];
            }
        }
    }
}

// Load accounts
$accounts = [];
if ($locationFilter > 0) {
    $accRes = mysqli_query($con, "
        SELECT ba.*, 
            EXISTS(SELECT 1 FROM transaction t WHERE t.bank_account_id = ba.id AND t.status = 1 LIMIT 1) AS locked_txn
        FROM bank_account ba
        WHERE ba.location_id = $locationFilter
        ORDER BY ba.bank_name ASC
    ");
    while ($row = mysqli_fetch_assoc($accRes)) {
        $accounts[] = $row;
    }
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 p-0">
                <div class="main-header">
                    <h4>Manage Bank & Cash Accounts</h4>
                    <small>Create and maintain opening balances</small>
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
                    <div class="card-header" align="right">
                        <button class="btn btn-primary" id="addBankBtn" <?php echo $locationFilter === 0 ? 'disabled' : ''; ?>>
                            <i class="fa fa-plus"></i> Add Account
                        </button>
                    </div>
                    <div class="card-block">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Bank / Account</th>
                                        <th>Opening Balance</th>
                                        <th>Balance As At</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($accounts as $acc): ?>
                                        <tr>
                                            <td><?php echo (int)$acc['id']; ?></td>
                                            <td><?php echo htmlspecialchars($acc['bank_name']); ?></td>
                                            <td><?php echo number_format((float)$acc['opening_balance'], 2); ?></td>
                                            <td><?php echo $acc['balance_as_it']; ?></td>
                                            <td><?php echo ((int)$acc['status'] === 1) ? 'Active' : 'Inactive'; ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                                        Action
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item edit-bank" href="#"
                                                            data-id="<?php echo (int)$acc['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($acc['bank_name']); ?>"
                                                            data-open="<?php echo htmlspecialchars($acc['opening_balance']); ?>"
                                                            data-date="<?php echo htmlspecialchars($acc['balance_as_it']); ?>"
                                                            data-status="<?php echo (int)$acc['status']; ?>"
                                                            data-locked="<?php echo (int)$acc['locked_txn']; ?>">
                                                            <i class="fa fa-pencil"></i> Edit
                                                        </a>
                                                        <a class="dropdown-item delete-bank" href="#"
                                                            data-id="<?php echo (int)$acc['id']; ?>">
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
<div class="modal fade" id="bankModal" tabindex="-1" role="dialog" aria-labelledby="bankModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="bankForm">
                <input type="hidden" name="save_bank" value="1">
                <input type="hidden" name="bank_id" id="bank_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="bankModalLabel">Add Account</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="bank_name">Bank / Account Name</label>
                        <input type="text" class="form-control" name="bank_name" id="bank_name" required>
                    </div>
                    <div class="form-group">
                        <label for="opening_balance">Opening Balance</label>
                        <input type="number" step="0.01" class="form-control" name="opening_balance" id="opening_balance" value="0.00">
                        <small class="text-muted">If you enter an opening balance, a Balance As At date is required.</small>
                    </div>
                    <div class="form-group">
                        <label for="balance_as_it">Balance As At</label>
                        <input type="date" class="form-control" name="balance_as_it" id="balance_as_it">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" name="status" id="status">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
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
<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="delete_bank" value="1">
    <input type="hidden" name="delete_id" id="delete_id">
</form>

<?php include 'footer.php'; ?>

<script>
$(document).ready(function() {
    function resetForm() {
        $('#bank_id').val('');
        $('#bank_name').val('');
        $('#opening_balance').val('0.00').prop('disabled', false);
        $('#balance_as_it').val('').prop('disabled', false);
        $('#status').val('1');
        $('#bankModalLabel').text('Add Account');
    }

    $('#addBankBtn').on('click', function() {
        resetForm();
        $('#bankModal').modal('show');
    });

    $('.edit-bank').on('click', function(e) {
        e.preventDefault();
        resetForm();
        var locked = parseInt($(this).data('locked'), 10) === 1;
        $('#bank_id').val($(this).data('id'));
        $('#bank_name').val($(this).data('name'));
        $('#opening_balance').val($(this).data('open'));
        $('#balance_as_it').val($(this).data('date'));
        $('#status').val($(this).data('status'));
        if (locked) {
            $('#opening_balance').prop('disabled', true);
            $('#balance_as_it').prop('disabled', true);
        }
        $('#bankModalLabel').text('Edit Account');
        $('#bankModal').modal('show');
    });

    $('.delete-bank').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (confirm('Delete this account?')) {
            $('#delete_id').val(id);
            $('#deleteForm').submit();
        }
    });

    $('#bankForm').on('submit', function() {
        var open = parseFloat($('#opening_balance').val() || 0);
        var asAt = $('#balance_as_it').val();
        if (open !== 0 && asAt === '') {
            alert('Please set Balance As At date when an opening balance is entered.');
            return false;
        }
        return true;
    });
});
</script>
