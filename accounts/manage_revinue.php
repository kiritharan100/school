<?php include 'header.php';

// Which language to surface to the user
$languagePreference = strtolower($client_language ?? 'english');
$primaryLanguage = in_array($languagePreference, ['tamil', 'sinhala']) ? $languagePreference : 'english';

// Resolve working location
$locationFilter = isset($location_id) && $location_id !== '' ? (int)$location_id : 0;

$flash = ['type' => '', 'text' => ''];

/**
 * Build bilingual label using English as the base and adding the selected language when present.
 */
function buildLabel(?string $english, ?string $alt, string $altLanguage): string {
    $english = $english ?? '';
    $alt = $alt ?? '';
    if ($altLanguage !== 'english' && $alt !== '') {
        return trim($english . ' / ' . ucfirst($altLanguage) . ': ' . $alt);
    }
    return $english;
}

// Load revenue categories for dropdown
$categories = [];
$catResult = mysqli_query($con, "SELECT rev_cat, category_english, category_sinhala, category_tamil FROM revinue_category ORDER BY category_english");
if ($catResult) {
    while ($row = mysqli_fetch_assoc($catResult)) {
        $categories[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create / Update
    if (isset($_POST['save_revinue'])) {
        $rId     = isset($_POST['r_id']) ? (int)$_POST['r_id'] : 0;
        $code    = trim($_POST['revinue_code'] ?? '');
        $nameEn  = trim($_POST['detail_of_revinue'] ?? '');
        $nameTa  = trim($_POST['revinue_tamil'] ?? '');
        $nameSi  = trim($_POST['revinue_sinhala'] ?? '');
        $status  = isset($_POST['status']) ? (int)$_POST['status'] : 1;
        $categoryId = isset($_POST['revinue_category_id']) ? (int)$_POST['revinue_category_id'] : 0;

        if ($locationFilter === 0) {
            $flash = ['type' => 'danger', 'text' => 'Select a location before saving.'];
        } elseif ($categoryId === 0) {
            $flash = ['type' => 'danger', 'text' => 'Please select a revenue category.'];
        } else {
            if ($rId > 0) {
                $stmt = $con->prepare("UPDATE revinue_code SET revinue_code=?, revinue_category_id=?, detail_of_revinue=?, revinue_tamil=?, revinue_sinhala=?, status=? WHERE r_id=? AND locaton_id=?");
                $stmt->bind_param("sisssiii", $code, $categoryId, $nameEn, $nameTa, $nameSi, $status, $rId, $locationFilter);
                $actionText = "Updated";
            } else {
                $stmt = $con->prepare("INSERT INTO revinue_code (locaton_id, revinue_code, revinue_category_id, detail_of_revinue, revinue_tamil, revinue_sinhala, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isisssi", $locationFilter, $code, $categoryId, $nameEn, $nameTa, $nameSi, $status);
                $actionText = "Created";
            }

            if ($stmt && $stmt->execute()) {
                UserLog("Revinue Source", $actionText, "Code: $code");
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
    if (isset($_POST['delete_revinue'])) {
        $deleteId = isset($_POST['delete_r_id']) ? (int)$_POST['delete_r_id'] : 0;
        if ($deleteId > 0 && $locationFilter > 0) {
            $stmt = $con->prepare("DELETE FROM revinue_code WHERE r_id=? AND locaton_id=?");
            $stmt->bind_param("ii", $deleteId, $locationFilter);
            if ($stmt->execute()) {
                UserLog("Revinue Source", "Deleted", "Record ID: $deleteId");
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

// Load data
$revinueList = [];
if ($locationFilter > 0) {
    $stmt = $con->prepare("
        SELECT rc.r_id, rc.locaton_id, rc.revinue_code, rc.revinue_category_id,
               rc.detail_of_revinue, rc.revinue_tamil, rc.revinue_sinhala, rc.status,
               cat.category_english, cat.category_sinhala, cat.category_tamil
        FROM revinue_code rc
        LEFT JOIN revinue_category cat ON rc.revinue_category_id = cat.rev_cat
        WHERE rc.locaton_id=?
        ORDER BY rc.r_id DESC
    ");
    $stmt->bind_param("i", $locationFilter);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $revinueList[] = $row;
    }
    $stmt->close();
}
?>

<div class="content-wrapper">
    <div class="container-fluid">

        <div class="row">
            <div class="col-sm-12 p-0">
                <div class="main-header">
                    <h4>Manage Revinue Source</h4>
                    <small>Showing names in:
                        <strong>English<?php echo $primaryLanguage !== 'english' ? ' + ' . ucfirst($primaryLanguage) : ''; ?></strong></small>
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
                    Please select a working location to manage revinue sources.
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header" align="right">
                        <button class="btn btn-primary" id="addRevinueBtn"
                            <?php echo $locationFilter === 0 ? 'disabled' : ''; ?>>
                            <i class="fa fa-plus" aria-hidden="true"></i> Add Revinue
                        </button>
                    </div>

                    <div class="card-block">
                        <div class="row">
                            <div class="col-sm-12 table-responsive">
                                <table id="revinueTable" class="table table-bordered table-striped" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th width="60px">#</th>
                                            <th width="60px">Code</th>
                                            <th>Revinue Name</th>
                                            <th width="320px">Category</th>
                                            <th width="80px">Status</th>
                                            <th width="120px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($revinueList as $row): ?>
                                        <?php
                                                // English is primary; fall back to another language if empty
                                                $englishName = $row['detail_of_revinue'];
                                                if ($englishName === '') {
                                                    $englishName = $row['revinue_tamil'] ?: $row['revinue_sinhala'];
                                                }

                                                $secondaryName = '';
                                                if ($primaryLanguage === 'tamil' && $row['revinue_tamil'] !== '') {
                                                    $secondaryName = $row['revinue_tamil'];
                                                } elseif ($primaryLanguage === 'sinhala' && $row['revinue_sinhala'] !== '') {
                                                    $secondaryName = $row['revinue_sinhala'];
                                                }
                                            ?>
                                        <tr>
                                            <td><?php echo (int)$row['r_id']; ?></td>
                                            <td align='center'><?php echo htmlspecialchars($row['revinue_code']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($englishName); ?>
                                                <?php if ($secondaryName !== ''): ?>
                                                <br> <small class="text-muted"><?php echo ucfirst($primaryLanguage); ?>:
                                                    <?php echo htmlspecialchars($secondaryName); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                            $englishName = $row['category_english'] ?? '';

                                                            if ($primaryLanguage === 'tamil') {
                                                                $secondaryName = $row['category_tamil'] ?? '';
                                                            } elseif ($primaryLanguage === 'sinhala') {
                                                                $secondaryName = $row['category_sinhala'] ?? '';
                                                            } else {
                                                                $secondaryName = '';
                                                            }
                                                        ?>

                                                <?php echo htmlspecialchars($englishName); ?>

                                                <?php if ($secondaryName !== ''): ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo ucfirst($primaryLanguage); ?>:
                                                    <?php echo htmlspecialchars($secondaryName); ?>
                                                </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo ((int)$row['status'] === 1) ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>'; ?>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-secondary dropdown-toggle" type="button"
                                                        id="actionMenuButton<?php echo (int)$row['r_id']; ?>"
                                                        data-toggle="dropdown" aria-haspopup="true"
                                                        aria-expanded="true">
                                                        Action
                                                    </button>
                                                    <div class="dropdown-menu"
                                                        aria-labelledby="actionMenuButton<?php echo (int)$row['r_id']; ?>">
                                                        <a class="dropdown-item edit-revinue" href="#"
                                                            data-r-id="<?php echo (int)$row['r_id']; ?>"
                                                            data-code="<?php echo htmlspecialchars($row['revinue_code']); ?>"
                                                            data-en="<?php echo htmlspecialchars($row['detail_of_revinue']); ?>"
                                                            data-ta="<?php echo htmlspecialchars($row['revinue_tamil']); ?>"
                                                            data-si="<?php echo htmlspecialchars($row['revinue_sinhala']); ?>"
                                                            data-cat="<?php echo (int)($row['revinue_category_id'] ?? 0); ?>"
                                                            data-status="<?php echo (int)$row['status']; ?>">
                                                            <i class="fa fa-pencil" aria-hidden="true"></i> Edit
                                                        </a>
                                                        <a class="dropdown-item delete-revinue" href="#"
                                                            data-r-id="<?php echo (int)$row['r_id']; ?>">
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
<div class="modal fade" id="revinueModal" tabindex="-1" role="dialog" aria-labelledby="revinueModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" class="processing_form" id="revinueForm">
                <input type="hidden" name="save_revinue" value="1">
                <input type="hidden" name="r_id" id="r_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="revinueModalLabel">Add Revinue</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="revinue_code">Revinue Code</label>
                        <input type="text" class="form-control" name="revinue_code" id="revinue_code" required>
                    </div>

                    <div class="form-group">
                        <label for="revinue_category_id">Revinue Category</label>
                        <select class="form-control" name="revinue_category_id" id="revinue_category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <?php
                                    $catAlt = ($primaryLanguage === 'tamil') ? $cat['category_tamil'] : (($primaryLanguage === 'sinhala') ? $cat['category_sinhala'] : '');
                                    $catLabel = buildLabel($cat['category_english'], $catAlt, $primaryLanguage);
                                ?>
                            <option value="<?php echo (int)$cat['rev_cat']; ?>">
                                <?php echo htmlspecialchars($catLabel); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group lang-field lang-english">
                        <label for="detail_of_revinue">Name (English)</label>
                        <input type="text" class="form-control" name="detail_of_revinue" id="detail_of_revinue"
                            required>
                    </div>

                    <div class="form-group lang-field lang-tamil"
                        style="<?php echo $primaryLanguage === 'tamil' ? '' : 'display:none;'; ?>">
                        <label for="revinue_tamil">Name (Tamil)</label>
                        <input type="text" class="form-control" name="revinue_tamil" id="revinue_tamil">
                    </div>

                    <div class="form-group lang-field lang-sinhala"
                        style="<?php echo $primaryLanguage === 'sinhala' ? '' : 'display:none;'; ?>">
                        <label for="revinue_sinhala">Name (Sinhala)</label>
                        <input type="text" class="form-control" name="revinue_sinhala" id="revinue_sinhala">
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
<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="delete_revinue" value="1">
    <input type="hidden" name="delete_r_id" id="delete_r_id">
</form>

<style>
.lang-field {
    transition: all 0.2s ease-in-out;
}
</style>

<script>
$(document).ready(function() {
    var primaryLang = '<?php echo $primaryLanguage; ?>';

    $('#revinueTable').DataTable({
        pageLength: 100
    });

    function resetForm() {
        $('#revinueForm')[0].reset();
        $('#r_id').val('');
        $('#revinue_category_id').val('');
        $('#status').val('1');
        $('#revinueModalLabel').text('Add Revinue');
        applyLanguageView();
    }

    function applyLanguageView() {
        // Always show English and keep it required
        $('.lang-field').hide();
        $('.lang-english').show();
        $('#detail_of_revinue').prop('required', true);

        // Show selected language (optional) when not English
        $('#revinue_tamil, #revinue_sinhala').prop('required', false);
        if (primaryLang === 'tamil') {
            $('.lang-tamil').show();
        } else if (primaryLang === 'sinhala') {
            $('.lang-sinhala').show();
        }
    }

    $('#addRevinueBtn').on('click', function() {
        resetForm();
        $('#revinueModal').modal('show');
    });

    $('.edit-revinue').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        $('#r_id').val(btn.data('r-id'));
        $('#revinue_code').val(btn.data('code'));
        $('#detail_of_revinue').val(btn.data('en'));
        $('#revinue_tamil').val(btn.data('ta'));
        $('#revinue_sinhala').val(btn.data('si'));
        $('#revinue_category_id').val(btn.data('cat'));
        $('#status').val(btn.data('status'));
        $('#revinueModalLabel').text('Edit Revinue');
        applyLanguageView();
        $('#revinueModal').modal('show');
    });

    $('.delete-revinue').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('r-id');
        if (confirm('Delete this record?')) {
            $('#delete_r_id').val(id);
            $('#deleteForm').submit();
        }
    });

    // Default language view
    applyLanguageView();
});
</script>

<?php include 'footer.php'; ?>