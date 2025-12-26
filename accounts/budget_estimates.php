<?php include 'header.php';

// Resolve working location from header context
$locationFilter = isset($location_id) && $location_id !== '' ? (int)$location_id : 0;
$primaryLanguage = 'english';
if (isset($client_language)) {
    $lang = strtolower($client_language);
    if (in_array($lang, ['tamil', 'sinhala'], true)) {
        $primaryLanguage = $lang;
    }
}

// Year selection
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$yearOptions = [$year];

$yearOptions = [];
// Build year options from 2025 to next year
$currentYear = (int)date('Y');
$maxYear = $currentYear + 1;
for ($y = 2025; $y <= $maxYear; $y++) {
    $yearOptions[] = $y;
}

// Load revenue (rows) ordered by code
$revenues = [];
if ($locationFilter > 0) {
    $revSql = "
        SELECT r_id, revinue_code, detail_of_revinue, revinue_tamil, revinue_sinhala, has_sub_category
        FROM revinue_code
        WHERE locaton_id = $locationFilter
        ORDER BY revinue_code ASC
    ";
    $revRes = mysqli_query($con, $revSql);
    while ($row = mysqli_fetch_assoc($revRes)) {
        $revenues[] = $row;
    }
}

// Natural sort revenue codes (e.g., S1, S2, S10) after fetch
if ($revenues) {
    usort($revenues, function($a, $b) {
        return strnatcasecmp($a['revinue_code'], $b['revinue_code']);
    });
}

// Load expenditure (columns)
$expenses = [];
if ($locationFilter > 0) {
    $expSql = "
        SELECT ex_id, ex_code, ex_english, ex_tamil, ex_sinhala
        FROM expenditure_code
        WHERE location_id = $locationFilter AND status = 1
        ORDER BY ex_code ASC
    ";
    $expRes = mysqli_query($con, $expSql);
    while ($row = mysqli_fetch_assoc($expRes)) {
        $expenses[] = $row;
    }
}

// Load allocations for selected year
$allocations = [];
$allocSql = "
    SELECT r_id, ex_id, amount
    FROM budget_allocation
    WHERE year = $year AND location_id = $locationFilter
";
$allocRes = mysqli_query($con, $allocSql);
if ($allocRes) {
    while ($row = mysqli_fetch_assoc($allocRes)) {
        $rid = (int)$row['r_id'];
        $eid = (int)$row['ex_id'];
        $allocations[$rid][$eid] = (float)$row['amount'];
    }
}

// Load opening balances
$opening = [];
$openSql = "
    SELECT r_id, op_balance
    FROM budget_opening_balance
    WHERE year = $year AND location_id = $locationFilter
";
$openRes = mysqli_query($con, $openSql);
if ($openRes) {
    while ($row = mysqli_fetch_assoc($openRes)) {
        $opening[(int)$row['r_id']] = (float)$row['op_balance'];
    }
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 p-0">
                <div class="main-header">
                    <h4>Budget Estimates</h4>
                    <small>Yearly budget entry per revenue vs expenditure</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <?php if ($locationFilter === 0): ?>
                        <div class="alert alert-warning m-b-0">
                            Please select a working location to manage budget estimates.
                        </div>
                        <?php endif; ?>
                        <form method="GET" class="form-inline" style="gap:10px;">
                            <label for="year" class="mr-2">Select Year</label>
                            <select name="year" id="year" class="form-control">
                                <?php foreach ($yearOptions as $y): ?>
                                <option value="<?php echo $y; ?>" <?php if ($y === $year) echo 'selected'; ?>>
                                    <?php echo $y; ?>
                                </option>
                                <?php endforeach; ?>
                                <?php if (!in_array((int)date('Y'), $yearOptions, true)): ?>
                                <option value="<?php echo (int)date('Y'); ?>"><?php echo (int)date('Y'); ?></option>
                                <?php endif; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Load</button>
                            <button type="button" class="btn btn-secondary" id="editBudgetBtn">Edit</button>
                            <span class="badge badge-secondary ml-3" id="budgetStatus">Draft</span>
                        </form>
                    </div>

                    <div class="card-block table-responsive">
                        <table id="budgetTable" class="table table-bordered table-striped budget-table">
                            <thead>
                                <tr>
                                    <th style="min-width:180px;">Revenue</th>
                                    <?php foreach ($expenses as $exp): ?>
                                    <th class="rotate">
                                        <div><span><?php echo htmlspecialchars($exp['ex_code']); ?></span></div>
                                    </th>
                                    <?php endforeach; ?>
                                    <th class="rotate">
                                        <div><span>Opening Balance</span></div>
                                    </th>
                                    <th class="rotate">
                                        <div><span>Collection CY</span></div>
                                    </th>
                                    <th class="rotate">
                                        <div><span>Total</span></div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($revenues as $rev): ?>
                                <?php
                                        $rid = (int)$rev['r_id'];
                                        $rowOpening = $opening[$rid] ?? 0;
                                        $isHeading = ((int)($rev['has_sub_category'] ?? 0) === 1);
                                        // Language-specific description
                                        if ($primaryLanguage === 'tamil') {
                                            $desc = $rev['revinue_tamil'] ?? '';
                                        } elseif ($primaryLanguage === 'sinhala') {
                                            $desc = $rev['revinue_sinhala'] ?? '';
                                        } else {
                                            $desc = $rev['detail_of_revinue'] ?? '';
                                        }
                                    ?>
                                <tr data-rid="<?php echo $rid; ?>"
                                    <?php echo $isHeading ? "data-heading='1' class='bg-light font-weight-bold'" : ""; ?>>
                                    <td>
                                        <strong><?php echo htmlspecialchars($rev['revinue_code']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($desc); ?></small>
                                    </td>
                                    <?php foreach ($expenses as $exp): ?>
                                    <?php
                                                $eid = (int)$exp['ex_id'];
                                                $val = $allocations[$rid][$eid] ?? 0;
                                            ?>
                                    <td>
                                        <?php if ($isHeading): ?>
                                        -
                                        <?php else: ?>
                                        <input type="number" step="0.01"
                                            class="form-control form-control-sm alloc-input"
                                            data-rid="<?php echo $rid; ?>" data-exid="<?php echo $eid; ?>"
                                            value="<?php echo htmlspecialchars($val); ?>" />
                                        <?php endif; ?>
                                    </td>
                                    <?php endforeach; ?>
                                    <td>
                                        <?php if ($isHeading): ?>
                                        -
                                        <?php else: ?>
                                        <input type="number" step="0.01"
                                            class="form-control form-control-sm opening-input"
                                            data-rid="<?php echo $rid; ?>"
                                            value="<?php echo htmlspecialchars($rowOpening); ?>" />
                                        <?php endif; ?>
                                    </td>
                                    <td class="collection-cell" data-rid="<?php echo $rid; ?>">
                                        <?php echo $isHeading ? '-' : '0.00'; ?></td>
                                    <td class="row-total-cell" data-rid="<?php echo $rid; ?>">
                                        <?php echo $isHeading ? '-' : '0.00'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="font-weight:600; background-color:#A8BBF0;">
                                    <th>Total expenses</th>
                                    <?php foreach ($expenses as $exp): ?>
                                    <th class="col-total" data-exid="<?php echo (int)$exp['ex_id']; ?>">0.00</th>
                                    <?php endforeach; ?>
                                    <th id="total-opening">0.00</th>
                                    <th id="total-collection">0.00</th>
                                    <th id="grand-total">0.00</th>
                                </tr>
                                <!-- <tr class="bg-light">
                                    <th>Opening Balance</th>
                                    <?php foreach ($expenses as $exp): ?>
                                    <th>-</th>
                                    <?php endforeach; ?>
                                    <th id="sum-opening">0.00</th>
                                    <th></th>
                                    <th></th>
                                </tr>
                                <tr class="bg-light">
                                    <th>Income for the year</th>
                                    <?php foreach ($expenses as $exp): ?>
                                    <th>-</th>
                                    <?php endforeach; ?>
                                    <th id="income-opening">0.00</th>
                                    <th id="income-collection">0.00</th>
                                    <th id="income-total">0.00</th>
                                </tr> -->
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.budget-table .rotate {
    height: auto;
    vertical-align: middle;
    text-align: center;
}

.budget-table .rotate>div {
    transform: none;
    width: auto;
}

.budget-table input {
    min-width: 80px;
    text-align: right;
}

.collection-cell,
.row-total-cell,
.col-total {
    text-align: right;
    font-weight: 600;
}
</style>

<script>
(function() {
    const year = <?php echo (int)$year; ?>;
    const locationId = <?php echo (int)$locationFilter; ?>;
    const expenses =
        <?php echo json_encode(array_map(function($e){ return ['ex_id'=>$e['ex_id']]; }, $expenses)); ?>;
    const inputs = Array.from(document.querySelectorAll('.alloc-input, .opening-input'));

    function format(val) {
        return parseFloat(val || 0).toFixed(2);
    }

    function recalcTotals() {
        let totalOpening = 0;
        let totalCollection = 0;
        let grandTotal = 0;
        let colTotals = {};
        expenses.forEach(e => {
            colTotals[e.ex_id] = 0;
        });

        // Rows
        document.querySelectorAll('#budgetTable tbody tr').forEach(row => {
            if (row.dataset.heading === '1') {
                return; // skip heading rows
            }
            const rid = row.getAttribute('data-rid');
            let rowExpense = 0;

            row.querySelectorAll('.alloc-input').forEach(inp => {
                const val = parseFloat(inp.value || 0);
                rowExpense += val;
                const exid = inp.getAttribute('data-exid');
                colTotals[exid] += val;
            });

            const openingInput = row.querySelector('.opening-input');
            const openingVal = parseFloat(openingInput.value || 0);
            totalOpening += openingVal;

            const collection = rowExpense - openingVal;
            totalCollection += collection;
            grandTotal += rowExpense;

            row.querySelector('.collection-cell').textContent = format(collection);
            row.querySelector('.row-total-cell').textContent = format(rowExpense);
        });

        // Column totals
        document.querySelectorAll('.col-total').forEach(cell => {
            const exid = cell.getAttribute('data-exid');
            cell.textContent = format(colTotals[exid] || 0);
        });

        document.getElementById('total-opening').textContent = format(totalOpening);
        document.getElementById('total-collection').textContent = format(totalCollection);
        document.getElementById('grand-total').textContent = format(grandTotal);

        document.getElementById('sum-opening').textContent = format(totalOpening);
        document.getElementById('income-opening').textContent = format(totalOpening);
        document.getElementById('income-collection').textContent = format(totalCollection);
        document.getElementById('income-total').textContent = format(totalCollection + totalOpening);

        const statusEl = document.getElementById('budgetStatus');
        if (Math.abs((totalOpening + totalCollection) - grandTotal) < 0.01) {
            statusEl.textContent = 'Final';
            statusEl.className = 'badge badge-success';
        } else {
            statusEl.textContent = 'Draft';
            statusEl.className = 'badge badge-warning';
        }
    }

    function saveAllocation(rid, exid, amount) {
        fetch('ajax/save_budget.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    type: 'allocation',
                    year: year,
                    location_id: locationId,
                    r_id: rid,
                    ex_id: exid,
                    amount: amount
                })
            })
            .then(res => res.text())
            .then(text => {
                let resp;
                try {
                    resp = JSON.parse(text);
                } catch (e) {
                    alert('Server response:\n' + text);
                    return;
                }
                if (!resp.success) {
                    alert(resp.msg || 'Failed to save allocation');
                }
            })
            .catch((e) => alert('Network error while saving allocation'));
    }

    function saveOpening(rid, amount) {
        fetch('ajax/save_budget.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: 'opening',
                    year: year,
                    location_id: locationId,
                    r_id: rid,
                    amount: amount
                })
            })
            .then(res => res.text())
            .then(text => {
                let resp;
                try {
                    resp = JSON.parse(text);
                } catch (e) {
                    alert('Server response:\n' + text);
                    return;
                }
                if (!resp.success) {
                    alert(resp.msg || 'Failed to save opening balance');
                }
            })
            .catch(() => alert('Network error while saving opening balance'));
    }

    function setInputsReadonly(isReadonly) {
        inputs.forEach(inp => {
            inp.readOnly = isReadonly;
            inp.classList.toggle('is-readonly', isReadonly);
        });
    }

    const editBtn = document.getElementById('editBudgetBtn');
    if (editBtn) {
        editBtn.addEventListener('click', () => {
            const confirmEdit = () => {
                setInputsReadonly(false);
                editBtn.disabled = true;
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Enable editing?',
                    text: 'You are about to edit budget estimates for ' + year + '. Continue?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, enable edit'
                }).then(result => {
                    if (result.isConfirmed) {
                        confirmEdit();
                    }
                });
            } else if (confirm('Enable editing for this budget year?')) {
                confirmEdit();
            }
        });
    }

    document.querySelectorAll('.alloc-input').forEach(inp => {
        inp.addEventListener('change', function() {
            const rid = this.getAttribute('data-rid');
            const exid = this.getAttribute('data-exid');
            const val = parseFloat(this.value || 0);
            saveAllocation(rid, exid, val);
            recalcTotals();
        });
    });

    document.querySelectorAll('.opening-input').forEach(inp => {
        inp.addEventListener('change', function() {
            const rid = this.getAttribute('data-rid');
            const val = parseFloat(this.value || 0);
            saveOpening(rid, val);
            recalcTotals();
        });
    });

    setInputsReadonly(true);
    recalcTotals();
})();
</script>

<?php include 'footer.php'; ?>