<?php include 'header.php';

$languagePreference = strtolower($client_language ?? 'english');
$primaryLanguage = in_array($languagePreference, ['tamil', 'sinhala']) ? $languagePreference : 'english';
$locationFilter = isset($location_id) && $location_id !== '' ? (int)$location_id : 0;
$flash = ['type' => '', 'text' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create / Update
    if (isset($_POST['save_expenditure'])) {
        $exId   = isset($_POST['ex_id']) ? (int)$_POST['ex_id'] : 0;
        $code   = trim($_POST['ex_code'] ?? '');
        $nameEn = trim($_POST['ex_english'] ?? '');
        $nameTa = trim($_POST['ex_tamil'] ?? '');
        $nameSi = trim($_POST['ex_sinhala'] ?? '');
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

        if ($locationFilter === 0) {
            $flash = ['type' => 'danger', 'text' => 'Select a location before saving.'];
        } elseif ($code === '') {
            $flash = ['type' => 'danger', 'text' => 'Expenditure code is required.'];
        } elseif ($nameEn === '') {
            $flash = ['type' => 'danger', 'text' => 'English name is required.'];
        } else {
            if ($exId > 0) {
                $stmt = $con->prepare("UPDATE expenditure_code SET ex_code=?, ex_english=?, ex_tamil=?, ex_sinhala=?, status=? WHERE ex_id=? AND location_id=?");
                $stmt->bind_param("ssssiii", $code, $nameEn, $nameTa, $nameSi, $status, $exId, $locationFilter);
                $actionText = "Updated";
            } else {
                $stmt = $con->prepare("INSERT INTO expenditure_code (location_id, ex_code, ex_english, ex_tamil, ex_sinhala, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssi", $locationFilter, $code, $nameEn, $nameTa, $nameSi, $status);
                $actionText = "Created";
            }

            if ($stmt && $stmt->execute()) {
                UserLog("Expenditure", $actionText, "Code: $code | Name: $nameEn");
                $flash = ['type' => 'success', 'text' => "Record {$actionText} successfully."];
            } else {
                $flash = ['type' => 'danger', 'text' => 'Unable to save record.'];
            }

            if ($stmt) {
                $stmt->close();
            }
        }
    }

    // Delete
    if (isset($_POST['delete_expenditure'])) {
        $deleteId = isset($_POST['delete_ex_id']) ? (int)$_POST['delete_ex_id'] : 0;
        if ($deleteId > 0 && $locationFilter > 0) {
            $stmt = $con->prepare("DELETE FROM expenditure_code WHERE ex_id=? AND location_id=?");
            $stmt->bind_param("ii", $deleteId, $locationFilter);
            if ($stmt->execute()) {
                UserLog("Expenditure", "Deleted", "Record ID: $deleteId");
                $flash = ['type' => 'success', 'text' => 'Record deleted.'];
            } else {
                $flash = ['type' => 'danger', 'text' => 'Unable to delete record.'];
            }
            $stmt->close();
        } else {
            $flash = ['type' => 'danger', 'text' => 'Invalid record or location.'];
        }
    }
}

$expenditures = [];
if ($locationFilter > 0) {
    $stmt = $con->prepare("SELECT ex_id, ex_code, location_id, ex_english, ex_tamil, ex_sinhala, status FROM expenditure_code WHERE location_id=? ORDER BY ex_id DESC");
    $stmt->bind_param("i", $locationFilter);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $expenditures[] = $row;
    }
    $stmt->close();
}
?>

<div class="content-wrapper">
    <div class="container-fluid">

        <div class="row">
            <div class="col-sm-12 p-0">
                <div class="main-header">
                    <h4>Manage Expenditure</h4>
                    <small>Showing names in: <strong>English<?php echo $primaryLanguage !== 'english' ? ' + ' . ucfirst($primaryLanguage) : ''; ?></strong></small>
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

                <?php if ($locationFilter === 0): ?>
                    <div class="alert alert-warning">
                        Please select a working location to manage expenditure codes.
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header" align="right">
                        <button class="btn btn-primary" id="addExpenditureBtn" <?php echo $locationFilter === 0 ? 'disabled' : ''; ?>>
                            <i class="fa fa-plus" aria-hidden="true"></i> Add Expenditure
                        </button>
                    </div>

                    <div class="card-block">
                        <div class="row">
                            <div class="col-sm-12 table-responsive">
                                <table id="expenditureTable" class="table table-bordered table-striped" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th width="60px">#</th>
                                            <th width="140px">Expenditure Code</th>
                                            <th>Expenditure Name</th>
                                            <th width="80px">Status</th>
                                            <th width="120px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($expenditures as $row): ?>
                                            <?php
                                                $englishName = $row['ex_english'];
                                                if ($englishName === '') {
                                                    $englishName = $row['ex_tamil'] ?: $row['ex_sinhala'];
                                                }
                                                $secondaryName = '';
                                                if ($primaryLanguage === 'tamil' && $row['ex_tamil'] !== '') {
                                                    $secondaryName = $row['ex_tamil'];
                                                } elseif ($primaryLanguage === 'sinhala' && $row['ex_sinhala'] !== '') {
                                                    $secondaryName = $row['ex_sinhala'];
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo (int)$row['ex_id']; ?></td>
                                                <td><?php echo htmlspecialchars($row['ex_code']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($englishName); ?>
                                                    <?php if ($secondaryName !== ''): ?>
                                                        <br><small class="text-muted"><?php echo ucfirst($primaryLanguage); ?>: <?php echo htmlspecialchars($secondaryName); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo ((int)$row['status'] === 1) ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>'; ?>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            Action
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <a class="dropdown-item edit-expenditure" href="#"
                                                               data-ex-id="<?php echo (int)$row['ex_id']; ?>"
                                                               data-code="<?php echo htmlspecialchars($row['ex_code']); ?>"
                                                               data-en="<?php echo htmlspecialchars($row['ex_english']); ?>"
                                                               data-ta="<?php echo htmlspecialchars($row['ex_tamil']); ?>"
                                                               data-si="<?php echo htmlspecialchars($row['ex_sinhala']); ?>"
                                                               data-status="<?php echo (int)$row['status']; ?>">
                                                                <i class="fa fa-pencil" aria-hidden="true"></i> Edit
                                                            </a>
                                                            <a class="dropdown-item delete-expenditure" href="#" data-ex-id="<?php echo (int)$row['ex_id']; ?>">
                                                                <i class="fa fa-trash" aria-hidden="true"></i> Delete
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
</div>

<!-- Create / Edit Modal -->
<div class="modal fade" id="expenditureModal" tabindex="-1" role="dialog" aria-labelledby="expenditureModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" class="processing_form" id="expenditureForm">
                <input type="hidden" name="save_expenditure" value="1">
                <input type="hidden" name="ex_id" id="ex_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="expenditureModalLabel">Add Expenditure</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="ex_code">Expenditure Code</label>
                        <input type="text" class="form-control" name="ex_code" id="ex_code" required>
                    </div>

                    <div class="form-group lang-field lang-english">
                        <label for="ex_english">Name (English)</label>
                        <input type="text" class="form-control" name="ex_english" id="ex_english" required>
                    </div>

                    <div class="form-group lang-field lang-tamil" style="<?php echo $primaryLanguage === 'tamil' ? '' : 'display:none;'; ?>">
                        <label for="ex_tamil">Name (Tamil)</label>
                        <input type="text" class="form-control" name="ex_tamil" id="ex_tamil">
                    </div>

                    <div class="form-group lang-field lang-sinhala" style="<?php echo $primaryLanguage === 'sinhala' ? '' : 'display:none;'; ?>">
                        <label for="ex_sinhala">Name (Sinhala)</label>
                        <input type="text" class="form-control" name="ex_sinhala" id="ex_sinhala">
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary processing">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete helper form -->
<form method="POST" id="deleteExpenditureForm" style="display:none;">
    <input type="hidden" name="delete_expenditure" value="1">
    <input type="hidden" name="delete_ex_id" id="delete_ex_id">
</form>

<style>
    .lang-field { transition: all 0.2s ease-in-out; }
</style>

<script>
    $(document).ready(function () {
        var primaryLang = '<?php echo $primaryLanguage; ?>';

        $('#expenditureTable').DataTable({
            pageLength: 100
        });

        function applyLanguageView() {
            // Always show English and keep it required
            $('.lang-field').hide();
            $('.lang-english').show();
            $('#ex_english').prop('required', true);

            // Show selected language (optional) when not English
            $('#ex_tamil, #ex_sinhala').prop('required', false);
            if (primaryLang === 'tamil') {
                $('.lang-tamil').show();
            } else if (primaryLang === 'sinhala') {
                $('.lang-sinhala').show();
            }
        }

        function resetForm() {
            $('#expenditureForm')[0].reset();
            $('#ex_id').val('');
            $('#ex_code').val('');
            $('#status').val('1');
            $('#expenditureModalLabel').text('Add Expenditure');
            applyLanguageView();
        }

        $('#addExpenditureBtn').on('click', function () {
            resetForm();
            $('#expenditureModal').modal('show');
        });

        $('.edit-expenditure').on('click', function (e) {
            e.preventDefault();
            var btn = $(this);
            $('#ex_id').val(btn.data('ex-id'));
            $('#ex_code').val(btn.data('code'));
            $('#ex_english').val(btn.data('en'));
            $('#ex_tamil').val(btn.data('ta'));
            $('#ex_sinhala').val(btn.data('si'));
            $('#status').val(btn.data('status'));
            $('#expenditureModalLabel').text('Edit Expenditure');
            applyLanguageView();
            $('#expenditureModal').modal('show');
        });

        $('.delete-expenditure').on('click', function (e) {
            e.preventDefault();
            var id = $(this).data('ex-id');
            if (confirm('Delete this record?')) {
                $('#delete_ex_id').val(id);
                $('#deleteExpenditureForm').submit();
            }
        });

        applyLanguageView();
    });
</script>

<?php include 'footer.php'; ?>
