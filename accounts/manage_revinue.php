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

/**
 * Generate the next revenue code based on type (main/sub) and parent.
 */
function generateRevenueCode(mysqli $con, int $locationId, int $mainId = 0): string {
    if ($mainId === 0) {
        // MAIN: S{max+1}
        $sql = "
            SELECT MAX(CAST(SUBSTRING(revinue_code, 2) AS UNSIGNED)) AS max_num
            FROM revinue_code
            WHERE locaton_id = $locationId AND main_cat_id = 0
        ";
        $res = mysqli_query($con, $sql);
        if (!$res) {
            return ''; // table or column issue
        }
        $row = mysqli_fetch_assoc($res);
        $next = (int)($row['max_num'] ?? 0) + 1;
        return 'S' . $next;
    }

    // SUB: parent_code.{next}
    $parentRes = mysqli_query($con, "
        SELECT revinue_code
        FROM revinue_code
        WHERE r_id = $mainId AND locaton_id = $locationId
    ");
    if (!$parentRes) {
        return '';
    }
    $parent = mysqli_fetch_assoc($parentRes);
    if (!$parent) {
        return '';
    }
    $parentCode = $parent['revinue_code'];

    $res = mysqli_query($con, "
        SELECT MAX(CAST(SUBSTRING_INDEX(revinue_code, '.', -1) AS UNSIGNED)) AS max_sub
        FROM revinue_code
        WHERE locaton_id = $locationId AND main_cat_id = $mainId
    ");
    if (!$res) {
        return '';
    }
    $row = mysqli_fetch_assoc($res);
    $next = (int)($row['max_sub'] ?? 0) + 1;
    return $parentCode . '.' . $next;
}

// Load revenue categories for dropdown
$categories = [];
$catResult = mysqli_query($con, "SELECT rev_cat, category_english, category_sinhala, category_tamil FROM revinue_category ORDER BY category_english");
if ($catResult) {
    while ($row = mysqli_fetch_assoc($catResult)) {
        $categories[] = $row;
    }
}

// Load active MAIN categories (for sub selection)
$mainCategories = [];
if ($locationFilter > 0) {
    $mainCatSql = "
        SELECT r_id, revinue_code, detail_of_revinue, revinue_tamil, revinue_sinhala
        FROM revinue_code
        WHERE locaton_id = $locationFilter AND has_sub_category = 1 AND main_cat_id = 0 AND status = 1
        ORDER BY revinue_code ASC
    ";
    $mainCatRes = mysqli_query($con, $mainCatSql);
    if ($mainCatRes) {
        while ($row = mysqli_fetch_assoc($mainCatRes)) {
            $mainCategories[] = $row;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create / Update
    if (isset($_POST['save_revinue'])) {
        $rId        = isset($_POST['r_id']) ? (int)$_POST['r_id'] : 0;
        $revType    = $_POST['rev_type'] ?? '';
        $mainCatId  = ($revType === 'sub') ? (int)($_POST['main_cat_id'] ?? 0) : 0;
        $nameEn     = trim($_POST['detail_of_revinue'] ?? '');
        $nameTa     = trim($_POST['revinue_tamil'] ?? '');
        $nameSi     = trim($_POST['revinue_sinhala'] ?? '');
        $status     = isset($_POST['status']) ? (int)$_POST['status'] : 1;
        $categoryId = isset($_POST['revinue_category_id']) ? (int)$_POST['revinue_category_id'] : 0;
        $code       = trim($_POST['revinue_code'] ?? '');

        if ($locationFilter === 0) {
            $flash = ['type' => 'danger', 'text' => 'Select a location before saving.'];
        } elseif ($categoryId === 0) {
            $flash = ['type' => 'danger', 'text' => 'Please select a revenue category.'];
        } elseif ($revType === 'sub' && $mainCatId === 0) {
            $flash = ['type' => 'danger', 'text' => 'Please select a main category for sub category.'];
        } else {
            // Generate code if not supplied
            if ($code === '') {
                $code = generateRevenueCode($con, $locationFilter, $mainCatId);
            }

            // Duplicate check
            $codeEsc = mysqli_real_escape_string($con, $code);
            $dupSql = "SELECT r_id FROM revinue_code WHERE revinue_code = '$codeEsc' AND locaton_id = $locationFilter";
            if ($rId > 0) {
                $dupSql .= " AND r_id <> $rId";
            }
            $dupRes = mysqli_query($con, $dupSql);
            if (mysqli_num_rows($dupRes) > 0) {
                $flash = ['type' => 'danger', 'text' => 'Revenue code already exists for this location.'];
            } else {
                // Block disabling main with children
                if ($status === 0 && $mainCatId === 0 && $rId > 0) {
                    $subCheck = mysqli_query($con, "
                        SELECT COUNT(*) AS c FROM revinue_code
                        WHERE main_cat_id = $rId AND locaton_id = $locationFilter
                    ");
                    $subRow = mysqli_fetch_assoc($subCheck);
                    if ((int)$subRow['c'] > 0) {
                        $flash = ['type' => 'danger', 'text' => 'Cannot deactivate a main category while sub categories exist.'];
                        goto skip_save;
                    }
                }

                if ($rId > 0) {
                    //has_sub_category = ".($revType === 'main' ? 1 : 0).",
                    $sql = "
                        UPDATE revinue_code SET
                            main_cat_id = $mainCatId,
                            revinue_code = '$codeEsc',
                            revinue_category_id = $categoryId,
                            detail_of_revinue = '".mysqli_real_escape_string($con, $nameEn)."',
                            revinue_tamil = '".mysqli_real_escape_string($con, $nameTa)."',
                            revinue_sinhala = '".mysqli_real_escape_string($con, $nameSi)."',
                            status = $status
                        WHERE r_id = $rId AND locaton_id = $locationFilter
                    ";
                    $ok = mysqli_query($con, $sql);
                    $actionText = "Updated";
                } else {
                    $sql = "
                        INSERT INTO revinue_code
                            (locaton_id, has_sub_category, main_cat_id, revinue_code, revinue_category_id, detail_of_revinue, revinue_tamil, revinue_sinhala, status)
                        VALUES (
                            $locationFilter,
                            ".($revType === 'main' ? 1 : 0).",
                            $mainCatId,
                            '$codeEsc',
                            $categoryId,
                            '".mysqli_real_escape_string($con, $nameEn)."',
                            '".mysqli_real_escape_string($con, $nameTa)."',
                            '".mysqli_real_escape_string($con, $nameSi)."',
                            $status
                        )
                    ";
                    $ok = mysqli_query($con, $sql);
                    $actionText = "Created";
                }

                if ($ok) {
                    UserLog("Revenue Source", $actionText, "Code: $code");
                    $flash = ['type' => 'success', 'text' => "Record {$actionText} successfully."];
                } else {
                    $flash = ['type' => 'danger', 'text' => 'Unable to save record.'];
                }
            }
        }
        skip_save:
    }

    // Delete
    if (isset($_POST['delete_revinue'])) {
        $deleteId = isset($_POST['delete_r_id']) ? (int)$_POST['delete_r_id'] : 0;
        if ($deleteId > 0 && $locationFilter > 0) {
            // Prevent deleting main with subs
            $subCheck = mysqli_query($con, "
                SELECT COUNT(*) AS c FROM revinue_code WHERE main_cat_id = $deleteId AND locaton_id = $locationFilter
            ");
            $subRow = mysqli_fetch_assoc($subCheck);
            if ((int)$subRow['c'] > 0) {
                $flash = ['type' => 'danger', 'text' => 'Cannot delete a main category while sub categories exist.'];
            } else {
                $stmt = $con->prepare("DELETE FROM revinue_code WHERE r_id=? AND locaton_id=?");
                $stmt->bind_param("ii", $deleteId, $locationFilter);
                if ($stmt->execute()) {
                    UserLog("Revenue Source", "Deleted", "Record ID: $deleteId");
                    $flash = ['type' => 'success', 'text' => 'Record deleted.'];
                } else {
                    $flash = ['type' => 'danger', 'text' => 'Unable to delete record.'];
                }
                $stmt->close();
            }
        } else {
            $flash = ['type' => 'danger', 'text' => 'Invalid record or location.'];
        }
    }
}

// Load data
$revinueRows = [];
if ($locationFilter > 0) {
    // Main rows
    $mainSql = "
        SELECT rs.*, cat.category_english, cat.category_sinhala, cat.category_tamil
        FROM revinue_code rs
        LEFT JOIN revinue_category cat ON rs.revinue_category_id = cat.rev_cat
        WHERE rs.locaton_id = $locationFilter AND rs.main_cat_id = 0
        ORDER BY rs.revinue_code ASC
    ";
    $mainRes = mysqli_query($con, $mainSql);

    if (!$mainRes) {
        $flash = ['type' => 'danger', 'text' => 'Failed to load revenue data: ' . mysqli_error($con)];
    }

    $mainCodeMap = [];
    if ($mainRes) while ($main = mysqli_fetch_assoc($mainRes)) {
        $mainId = (int)$main['r_id'];
        $mainCodeMap[$mainId] = $main['revinue_code'];
        // Count subs
        $subCountRes = mysqli_query($con, "
            SELECT COUNT(*) AS c FROM revinue_code
            WHERE locaton_id = $locationFilter AND main_cat_id = $mainId
        ");
        $subCountRow = $subCountRes ? mysqli_fetch_assoc($subCountRes) : ['c' => 0];
        $subCount = (int)($subCountRow['c'] ?? 0);

        $revinueRows[] = ['type' => 'main', 'row' => $main, 'sub_count' => $subCount];

        // Subs
        $subRes = mysqli_query($con, "
            SELECT rs.*, cat.category_english, cat.category_sinhala, cat.category_tamil, '$mainId' AS parent_id
            FROM revinue_code rs
            LEFT JOIN revinue_category cat ON rs.revinue_category_id = cat.rev_cat
            WHERE rs.locaton_id = $locationFilter AND rs.main_cat_id = $mainId
            ORDER BY rs.revinue_code ASC
        ");
        if ($subRes) while ($sub = mysqli_fetch_assoc($subRes)) {
            $revinueRows[] = [
                'type' => 'sub',
                'row' => $sub,
                'parent_id' => $mainId,
                'parent_code' => $mainCodeMap[$mainId] ?? ''
            ];
        }
    }

    // Sort final rows by code ascending for consistent display
    usort($revinueRows, function ($a, $b) {
        $ac = $a['row']['revinue_code'] ?? '';
        $bc = $b['row']['revinue_code'] ?? '';
        return strnatcasecmp($ac, $bc);
    });
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
                            <div class="col-sm-4">
                                <label for="mainFilter">Filter by Main Category</label>
                                <select id="mainFilter" class="form-control">
                                    <option value="">All</option>
                                    <?php foreach ($mainCategories as $m): ?>
                                    <option value="<?php echo htmlspecialchars($m['revinue_code']); ?>">
                                        <?php echo htmlspecialchars($m['revinue_code'] . ' - ' . ($m['detail_of_revinue'] ?? '')); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row" style="margin-top:10px;">
                            <div class="col-sm-12 table-responsive">
                                <table id="revinueTable" class="table table-bordered table-striped" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th width="20px">#</th>
                                            <th width="60px">Code</th>
                                            <th>Revenue Name</th>
                                            <th width="40px">Main</th>
                                            <th width="340px">Category</th>
                                            <th width="40px">Status</th>
                                            <th width="120px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($revinueRows as $item): ?>
                                        <?php
                                                $row = $item['row'];
                                                $isSub = $item['type'] === 'sub';

                                                // English is primary; fall back to another language if empty
                                                $englishName = $row['detail_of_revinue'] ?? '';
                                                if ($englishName === '') {
                                                    $englishName = ($row['revinue_tamil'] ?? '') ?: ($row['revinue_sinhala'] ?? '');
                                                }

                                                $secondaryName = '';
                                                if ($primaryLanguage === 'tamil' && ($row['revinue_tamil'] ?? '') !== '') {
                                                    $secondaryName = $row['revinue_tamil'];
                                                } elseif ($primaryLanguage === 'sinhala' && ($row['revinue_sinhala'] ?? '') !== '') {
                                                    $secondaryName = $row['revinue_sinhala'];
                                                }

                                                $code = $row['revinue_code'];
                                                $parentCode = $isSub ? ($item['parent_code'] ?? '') : '';
                                            ?>
                                        <tr>
                                            <td><?php echo (int)$row['r_id']; ?></td>
                                            <td align='center'>

                                                <?php echo htmlspecialchars($code); ?><?php echo $isSub ? '➥ ' : ''; ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($englishName); ?>
                                                <?php if ($secondaryName !== ''): ?>
                                                <br> <small class="text-muted"><?php echo ucfirst($primaryLanguage); ?>:
                                                    <?php echo htmlspecialchars($secondaryName); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $isSub ? htmlspecialchars($parentCode) : '-'; ?>
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
                                                            data-code="<?php echo htmlspecialchars($code); ?>"
                                                            data-en="<?php echo htmlspecialchars($row['detail_of_revenue'] ?? $row['detail_of_revinue']); ?>"
                                                            data-ta="<?php echo htmlspecialchars($row['revinue_tamil']); ?>"
                                                            data-si="<?php echo htmlspecialchars($row['revinue_sinhala']); ?>"
                                                            data-cat="<?php echo (int)($row['revinue_category_id'] ?? 0); ?>"
                                                            data-status="<?php echo (int)$row['status']; ?>"
                                                            data-type="<?php echo $isSub ? 'sub' : 'main'; ?>"
                                                            data-main-id="<?php echo $isSub ? (int)($row['main_cat_id'] ?? 0) : 0; ?>"
                                                            data-sub-count="<?php echo $item['sub_count'] ?? 0; ?>"
                                                            data-parent-code="<?php echo htmlspecialchars($parentCode); ?>">
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
                        <label for="rev_type">Revenue Type</label>
                        <select class="form-control" name="rev_type" id="rev_type" required>
                            <option value="">Select Type</option>
                            <option value="main">Main Category</option>
                            <option value="sub">Sub Category</option>
                        </select>
                    </div>

                    <div class="form-group" id="mainCatWrapper" style="display:none;">
                        <label for="main_cat_id">Parent (Main) Category</label>
                        <select class="form-control" name="main_cat_id" id="main_cat_id">
                            <option value="">Select Main</option>
                            <?php foreach ($mainCategories as $m): ?>
                            <option value="<?php echo (int)$m['r_id']; ?>">
                                <?php echo htmlspecialchars(($m['revinue_code'] ?? '') . ' - ' . ($m['detail_of_revinue'] ?? '')); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Generated Code</label>
                        <div><strong id="codePreview"></strong></div>
                        <input type="hidden" name="revinue_code" id="revinue_code">
                    </div>

                    <div class="form-group">
                        <label for="revenue_category_id">Revenue Category</label>
                        <select class="form-control" name="revinue_category_id" id="revenue_category_id" required>
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
    var currentSubCount = 0;

    var table = $('#revinueTable').DataTable({
        pageLength: 100,
        order: [
            [1, 'asc']
        ]
    });

    $('#mainFilter').on('change', function() {
        var val = $(this).val();
        table.column(3).search(val, false, false).draw();
    });

    function resetForm() {
        $('#revinueForm')[0].reset();
        $('#r_id').val('');
        $('#revenue_category_id').val('');
        $('#rev_type').val('');
        $('#main_cat_id').val('');
        $('#revinue_code').val('');
        $('#codePreview').text('');
        $('#status').val('1');
        $('#revinueModalLabel').text('Add Revinue');
        currentSubCount = 0;
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

    function toggleParentSelect() {
        var type = $('#rev_type').val();
        if (type === 'sub') {
            $('#mainCatWrapper').show();
        } else {
            $('#mainCatWrapper').hide();
            $('#main_cat_id').val('');
        }
    }

    function fetchNextCode(mainId) {
        var loc = <?php echo $locationFilter; ?>;
        $.get('ajax/revinue_code_gen.php', {
            main_id: mainId,
            location: loc
        }, function(data) {
            if (data && data.code) {
                $('#revinue_code').val(data.code);
                $('#codePreview').text(data.code);
            }
        }, 'json');
    }

    $('#rev_type').on('change', function() {
        toggleParentSelect();
        var type = $(this).val();
        if (type === 'main') {
            fetchNextCode(0);
        } else if (type === 'sub' && $('#main_cat_id').val()) {
            fetchNextCode($('#main_cat_id').val());
        } else {
            $('#revenue_code').val('');
            $('#codePreview').text('');
        }
    });

    $('#main_cat_id').on('change', function() {
        if ($('#rev_type').val() === 'sub' && $(this).val()) {
            fetchNextCode($(this).val());
        }
    });

    $('#addRevinueBtn').on('click', function() {
        resetForm();
        $('#rev_type').val('main');
        toggleParentSelect();
        fetchNextCode(0);
        $('#revinueModal').modal('show');
    });

    $('.edit-revinue').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        $('#r_id').val(btn.data('r-id'));
        $('#revinue_code').val(btn.data('code'));
        $('#codePreview').text(btn.data('code'));
        $('#detail_of_revinue').val(btn.data('en'));
        $('#revinue_tamil').val(btn.data('ta'));
        $('#revinue_sinhala').val(btn.data('si'));
        $('#revenue_category_id').val(btn.data('cat'));
        $('#status').val(btn.data('status'));
        $('#rev_type').val(btn.data('type'));
        $('#main_cat_id').val(btn.data('main-id'));
        currentSubCount = parseInt(btn.data('sub-count') || 0, 10);
        toggleParentSelect();
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

    $('#status').on('change', function() {
        if ($('#rev_type').val() === 'main' && currentSubCount > 0 && $(this).val() === '0') {
            alert('Cannot deactivate a main category while sub categories exist.');
            $(this).val('1');
        }
    });

    // Default language view
    applyLanguageView();
});
</script>

<?php include 'footer.php'; ?>