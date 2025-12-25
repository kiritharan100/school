<?php include 'header.php';

$locationFilter = isset($location_id) && $location_id !== '' ? (int)$location_id : 0;
$flash = ['type' => '', 'text' => ''];

// Language handling
$primaryLanguage = 'english';
if (isset($client_language)) {
    $lang = strtolower($client_language);
    if (in_array($lang, ['tamil', 'sinhala'], true)) {
        $primaryLanguage = $lang;
    }
}

// Helpers
function label_with_language(array $row, string $primaryLanguage) {
    if ($primaryLanguage === 'tamil' && !empty($row['revinue_tamil'] ?? $row['rev_tamil'] ?? $row['ex_tamil'] ?? '')) {
        return $row['code'] . ' - ' . ($row['revinue_tamil'] ?? $row['rev_tamil'] ?? $row['ex_tamil']);
    }
    if ($primaryLanguage === 'sinhala' && !empty($row['revinue_sinhala'] ?? $row['rev_sinhala'] ?? $row['ex_sinhala'] ?? '')) {
        return $row['code'] . ' - ' . ($row['revinue_sinhala'] ?? $row['rev_sinhala'] ?? $row['ex_sinhala']);
    }
    return $row['code'] . ' - ' . ($row['detail_of_revinue'] ?? $row['rev_desc'] ?? $row['ex_english'] ?? '');
}

// Load income accounts (revinue_code)
$incomeOptions = [];
if ($locationFilter > 0) {
    $revSql = "
        SELECT r_id, revinue_code AS code, detail_of_revinue, revinue_tamil, revinue_sinhala
        FROM revinue_code
        WHERE locaton_id = $locationFilter AND status = 1
        ORDER BY revinue_code ASC
    ";
    $revRes = mysqli_query($con, $revSql);
    while ($row = mysqli_fetch_assoc($revRes)) {
        $incomeOptions[] = $row;
    }
    // natural sort codes
    usort($incomeOptions, function($a,$b){ return strnatcasecmp($a['code'],$b['code']); });
}

// Load expense accounts (expenditure_code)
$expenseOptions = [];
if ($locationFilter > 0) {
    $expSql = "
        SELECT ex_id, ex_code AS code, ex_english, ex_tamil, ex_sinhala
        FROM expenditure_code
        WHERE location_id = $locationFilter AND status = 1
        ORDER BY ex_code ASC
    ";
    $expRes = mysqli_query($con, $expSql);
    while ($row = mysqli_fetch_assoc($expRes)) {
        $expenseOptions[] = $row;
    }
    usort($expenseOptions, function($a,$b){ return strnatcasecmp($a['code'],$b['code']); });
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save (add/update)
    if (isset($_POST['save_supplier'])) {
        $supId   = isset($_POST['sup_id']) ? (int)$_POST['sup_id'] : 0;
        $name    = mysqli_real_escape_string($con, trim($_POST['supplier_name'] ?? ''));
        $income  = isset($_POST['income_account']) ? (int)$_POST['income_account'] : 0;
        $expense = isset($_POST['expense_account']) ? (int)$_POST['expense_account'] : 0;
        $status  = isset($_POST['status']) ? (int)$_POST['status'] : 1;

        if ($locationFilter === 0) {
            $flash = ['type' => 'danger', 'text' => 'Select a location first.'];
        } elseif ($name === '') {
            $flash = ['type' => 'danger', 'text' => 'Supplier name is required.'];
        } else {
            if ($supId > 0) {
                $sql = "
                    UPDATE manage_supplier SET
                        supplier_name = '$name',
                        income_account = $income,
                        expense_account = $expense,
                        status = $status
                    WHERE sup_id = $supId AND location_id = $locationFilter
                ";
                if (mysqli_query($con, $sql)) {
                    $flash = ['type' => 'success', 'text' => 'Supplier updated successfully.'];
                } else {
                    $flash = ['type' => 'danger', 'text' => 'Failed to update supplier.'];
                }
            } else {
                $sql = "
                    INSERT INTO manage_supplier (location_id, income_account, expense_account, supplier_name, status)
                    VALUES ($locationFilter, $income, $expense, '$name', $status)
                ";
                if (mysqli_query($con, $sql)) {
                    $flash = ['type' => 'success', 'text' => 'Supplier created successfully.'];
                } else {
                    $flash = ['type' => 'danger', 'text' => 'Failed to create supplier.'];
                }
            }
        }
    }

    // Delete
    if (isset($_POST['delete_supplier'])) {
        $deleteId = isset($_POST['delete_id']) ? (int)$_POST['delete_id'] : 0;
        if ($deleteId > 0 && $locationFilter > 0) {
            mysqli_query($con, "DELETE FROM manage_supplier WHERE sup_id = $deleteId AND location_id = $locationFilter");
            $flash = ['type' => 'success', 'text' => 'Supplier deleted.'];
        }
    }
}

// Load suppliers
$suppliers = [];
if ($locationFilter > 0) {
    $supRes = mysqli_query($con, "
        SELECT * FROM manage_supplier
        WHERE location_id = $locationFilter
        ORDER BY supplier_name ASC
    ");
    while ($row = mysqli_fetch_assoc($supRes)) {
        $suppliers[] = $row;
    }
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 p-0">
                <div class="main-header">
                    <h4>Manage Suppliers</h4>
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
                        <button class="btn btn-primary" id="addSupplierBtn" <?php echo $locationFilter === 0 ? 'disabled' : ''; ?>>
                            <i class="fa fa-plus"></i> Add Supplier
                        </button>
                    </div>
                    <div class="card-block">
                        <div class="table-responsive">
                            <table id="supplierTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Supplier Name</th>
                                        <th>Income Account</th>
                                        <th>Expense Account</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($suppliers as $sup): ?>
                                        <?php
                                            // Resolve labels
                                            $incomeLabel = '';
                                            foreach ($incomeOptions as $opt) {
                                                if ((int)$opt['r_id'] === (int)$sup['income_account']) {
                                                    $incomeLabel = label_with_language($opt, $primaryLanguage);
                                                    break;
                                                }
                                            }
                                            $expenseLabel = '';
                                            foreach ($expenseOptions as $opt) {
                                                if ((int)$opt['ex_id'] === (int)$sup['expense_account']) {
                                                    $expenseLabel = label_with_language($opt, $primaryLanguage);
                                                    break;
                                                }
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo (int)$sup['sup_id']; ?></td>
                                            <td><?php echo htmlspecialchars($sup['supplier_name']); ?></td>
                                            <td><?php echo htmlspecialchars($incomeLabel); ?></td>
                                            <td><?php echo htmlspecialchars($expenseLabel); ?></td>
                                            <td><?php echo ((int)$sup['status'] === 1) ? 'Active' : 'Inactive'; ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                                        Action
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item edit-supplier" href="#"
                                                            data-id="<?php echo (int)$sup['sup_id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($sup['supplier_name']); ?>"
                                                            data-income="<?php echo (int)$sup['income_account']; ?>"
                                                            data-expense="<?php echo (int)$sup['expense_account']; ?>"
                                                            data-status="<?php echo (int)$sup['status']; ?>">
                                                            <i class="fa fa-pencil"></i> Edit
                                                        </a>
                                                        <a class="dropdown-item delete-supplier" href="#"
                                                            data-id="<?php echo (int)$sup['sup_id']; ?>">
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
<div class="modal fade" id="supplierModal" tabindex="-1" role="dialog" aria-labelledby="supplierModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="supplierForm">
                <input type="hidden" name="save_supplier" value="1">
                <input type="hidden" name="sup_id" id="sup_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="supplierModalLabel">Add Supplier</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="supplier_name">Supplier Name</label>
                        <input type="text" class="form-control" name="supplier_name" id="supplier_name" required>
                    </div>
                    <div class="form-group">
                        <label for="income_account">Income Account</label>
                        <select class="form-control" name="income_account" id="income_account">
                            <option value="0">None</option>
                            <?php foreach ($incomeOptions as $opt): ?>
                                <option value="<?php echo (int)$opt['r_id']; ?>">
                                    <?php echo htmlspecialchars(label_with_language($opt, $primaryLanguage)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="expense_account">Expense Account</label>
                        <select class="form-control" name="expense_account" id="expense_account">
                            <option value="0">None</option>
                            <?php foreach ($expenseOptions as $opt): ?>
                                <option value="<?php echo (int)$opt['ex_id']; ?>">
                                    <?php echo htmlspecialchars(label_with_language($opt, $primaryLanguage)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
<form method="POST" id="deleteSupplierForm" style="display:none;">
    <input type="hidden" name="delete_supplier" value="1">
    <input type="hidden" name="delete_id" id="delete_id">
</form>

<?php include 'footer.php'; ?>

<script>
$(document).ready(function() {
    $('#supplierTable').DataTable({
        pageLength: 100
    });

    function resetForm() {
        $('#sup_id').val('');
        $('#supplier_name').val('');
        $('#income_account').val('0');
        $('#expense_account').val('0');
        $('#status').val('1');
        $('#supplierModalLabel').text('Add Supplier');
    }

    $('#addSupplierBtn').on('click', function() {
        resetForm();
        $('#supplierModal').modal('show');
    });

    $('.edit-supplier').on('click', function(e) {
        e.preventDefault();
        resetForm();
        $('#sup_id').val($(this).data('id'));
        $('#supplier_name').val($(this).data('name'));
        $('#income_account').val($(this).data('income'));
        $('#expense_account').val($(this).data('expense'));
        $('#status').val($(this).data('status'));
        $('#supplierModalLabel').text('Edit Supplier');
        $('#supplierModal').modal('show');
    });

    $('.delete-supplier').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (confirm('Delete this supplier?')) {
            $('#delete_id').val(id);
            $('#deleteSupplierForm').submit();
        }
    });
});
</script>
