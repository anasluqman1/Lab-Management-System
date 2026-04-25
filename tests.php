<?php
$pageTitle = 'Tests';
$pageSubtitle = 'Assign and manage laboratory tests';
require_once __DIR__ . '/auth.php';
requireRole(['admin', 'technician']);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'assign') {
        $patientDbId = intval($_POST['patient_id']);
        $testIds = $_POST['test_ids'] ?? [];

        if (empty($testIds)) {
            $error = "Please select at least one test.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO patient_tests (patient_id, test_id, ordered_by) VALUES (?, ?, ?)");
                foreach ($testIds as $tid) {
                    $stmt->execute([$patientDbId, intval($tid), getUserId()]);
                }

                $pName = $pdo->prepare("SELECT full_name FROM patients WHERE id = ?");
                $pName->execute([$patientDbId]);
                $pName = $pName->fetchColumn();

                $count = count($testIds);
                $success = "$count test(s) assigned to $pName successfully!";
                broadcastNotification($pdo, ['admin', 'technician'], 'test', 'Tests Assigned', "$count test(s) assigned to $pName", 'tests.php');
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }

    if ($_POST['action'] === 'update_status') {
        $ptId = intval($_POST['pt_id']);
        $newStatus = sanitize($_POST['status']);
        try {
            $completedAt = $newStatus === 'completed' ? date('Y-m-d H:i:s') : null;
            $stmt = $pdo->prepare("UPDATE patient_tests SET status = ?, completed_at = ? WHERE id = ?");
            $stmt->execute([$newStatus, $completedAt, $ptId]);
            $success = "Test status updated!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    // ── BULK STATUS UPDATE ──────────────────────────────────────────────────
    if ($_POST['action'] === 'bulk_status') {
        $ptIds     = array_map('intval', $_POST['pt_ids'] ?? []);
        $newStatus = sanitize($_POST['bulk_status'] ?? '');
        $allowed   = ['in_progress', 'completed', 'pending'];

        if (empty($ptIds)) {
            $error = "No tests selected.";
        } elseif (!in_array($newStatus, $allowed)) {
            $error = "Invalid status.";
        } else {
            try {
                $completedAt = $newStatus === 'completed' ? date('Y-m-d H:i:s') : null;
                $placeholders = implode(',', array_fill(0, count($ptIds), '?'));
                $params = array_merge([$newStatus, $completedAt], $ptIds);
                $pdo->prepare("UPDATE patient_tests SET status = ?, completed_at = ? WHERE id IN ($placeholders)")
                    ->execute($params);
                $success = count($ptIds) . " test(s) updated to " . ucfirst(str_replace('_', ' ', $newStatus)) . "!";
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }

    // ── BULK DELETE ────────────────────────────────────────────────────────
    if ($_POST['action'] === 'bulk_delete') {
        $ptIds = array_map('intval', $_POST['pt_ids'] ?? []);
        if (empty($ptIds)) {
            $error = "No tests selected.";
        } else {
            try {
                $placeholders = implode(',', array_fill(0, count($ptIds), '?'));
                $pdo->prepare("DELETE FROM patient_tests WHERE id IN ($placeholders)")->execute($ptIds);
                $success = count($ptIds) . " test(s) removed.";
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }

    if ($_POST['action'] === 'delete_test') {
        try {
            $pdo->prepare("DELETE FROM patient_tests WHERE id = ?")->execute([intval($_POST['pt_id'])]);
            $success = "Test removed.";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

$selPatient = intval($_GET['patient_id'] ?? 0);
$allPatients = $pdo->query("SELECT id, patient_id, full_name FROM patients ORDER BY full_name")->fetchAll();

// test catalog grouped by category
$catalog = $pdo->query("SELECT * FROM test_catalog WHERE is_active = 1 ORDER BY category, test_name")->fetchAll();
$categories = [];
foreach ($catalog as $t) {
    $categories[$t['category']][] = $t;
}

$status = sanitize($_GET['status'] ?? '');
$sql = "
    SELECT pt.*, p.full_name as patient_name, p.patient_id as pid, tc.test_name, tc.category, tc.test_code
    FROM patient_tests pt
    JOIN patients p ON pt.patient_id = p.id
    JOIN test_catalog tc ON pt.test_id = tc.id
";
$params = [];
if ($status) {
    $sql .= " WHERE pt.status = ?";
    $params[] = $status;
}
$sql .= " ORDER BY pt.ordered_at DESC";
$patientTests = $pdo->prepare($sql);
$patientTests->execute($params);
$patientTests = $patientTests->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?>
    <div class="toast toast-success" id="toast"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="toast toast-error" id="toast"><i class="fas fa-times-circle"></i> <?php echo $error; ?></div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-left">
        <form method="GET" style="display:flex;gap:8px;align-items:center;">
            <label style="color:var(--text-muted);font-size:13px;">Filter:</label>
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
            </select>
        </form>
    </div>
    <div class="toolbar-right">
        <button class="btn btn-primary" onclick="openModal('assignTestModal')">
            <i class="fas fa-plus"></i> <span data-translate="Assign Tests">Assign Tests</span>
        </button>
    </div>
</div>

<!-- Bulk Action Bar (hidden until rows are selected) -->
<div id="bulkBar" style="display:none;margin-bottom:12px;padding:12px 16px;background:rgba(6,182,212,0.08);border:1px solid rgba(6,182,212,0.25);border-radius:var(--radius-md);display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <span id="bulkCount" style="color:var(--accent-primary);font-weight:600;font-size:14px;"></span>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <form method="POST" id="bulkForm" style="display:contents;">
            <input type="hidden" name="action" value="bulk_status">
            <div id="bulkIdsContainer"></div>
            <button type="button" class="btn btn-sm btn-warning" onclick="submitBulk('in_progress')" title="Set selected to In Progress">
                <i class="fas fa-play"></i> Start Selected
            </button>
            <button type="button" class="btn btn-sm btn-success" onclick="submitBulk('completed')" title="Set selected to Completed">
                <i class="fas fa-check"></i> Complete Selected
            </button>
        </form>
        <form method="POST" id="bulkDeleteForm" style="display:contents;">
            <input type="hidden" name="action" value="bulk_delete">
            <div id="bulkDeleteIdsContainer"></div>
            <button type="button" class="btn btn-sm btn-danger" onclick="submitBulkDelete()" title="Remove selected tests">
                <i class="fas fa-trash"></i> Remove Selected
            </button>
        </form>
    </div>
    <button class="btn btn-sm btn-secondary" style="margin-left:auto;" onclick="clearSelection()">
        <i class="fas fa-times"></i> Clear
    </button>
</div>

<div class="card">
    <div class="table-container">
        <table id="testsTable">
            <thead>
                <tr>
                    <th style="width:40px;">
                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"
                            style="accent-color:var(--accent-primary);width:16px;height:16px;cursor:pointer;"
                            title="Select All">
                    </th>
                    <th data-translate="Patient">Patient</th>
                    <th>Test Code</th>
                    <th data-translate="Test Name">Test Name</th>
                    <th data-translate="Category">Category</th>
                    <th data-translate="Status">Status</th>
                    <th>Ordered</th>
                    <th data-translate="Actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($patientTests)): ?>
                    <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:40px;">No tests found</td></tr>
                <?php else: ?>
                    <?php foreach ($patientTests as $pt): ?>
                    <tr class="test-row" data-id="<?php echo $pt['id']; ?>" data-status="<?php echo $pt['status']; ?>">
                        <td>
                            <input type="checkbox" class="row-check" value="<?php echo $pt['id']; ?>"
                                onchange="onRowCheck()"
                                style="accent-color:var(--accent-primary);width:16px;height:16px;cursor:pointer;">
                        </td>
                        <td>
                            <strong style="color:var(--text-primary);"><?php echo htmlspecialchars($pt['patient_name']); ?></strong>
                            <br><small style="color:var(--text-muted);"><?php echo $pt['pid']; ?></small>
                        </td>
                        <td style="color:var(--accent-primary);font-weight:600;"><?php echo $pt['test_code']; ?></td>
                        <td><?php echo htmlspecialchars($pt['test_name']); ?></td>
                        <td><span class="badge badge-in-progress"><?php echo $pt['category']; ?></span></td>
                        <td><span class="badge badge-<?php echo $pt['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $pt['status'])); ?></span></td>
                        <td style="font-size:13px;color:var(--text-muted);"><?php echo formatDateTime($pt['ordered_at']); ?></td>
                        <td>
                            <div class="btn-group">
                                <?php if ($pt['status'] !== 'completed'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="pt_id" value="<?php echo $pt['id']; ?>">
                                        <?php if ($pt['status'] === 'pending'): ?>
                                            <input type="hidden" name="status" value="in_progress">
                                            <button type="submit" class="btn btn-sm btn-warning" title="Start"><i class="fas fa-play"></i></button>
                                        <?php else: ?>
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="btn btn-sm btn-success" title="Complete"><i class="fas fa-check"></i></button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                                <?php if ($pt['status'] === 'completed'): ?>
                                    <a href="results.php?patient_test_id=<?php echo $pt['id']; ?>" class="btn btn-sm btn-primary" title="Enter Results">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this test?')">
                                    <input type="hidden" name="action" value="delete_test">
                                    <input type="hidden" name="pt_id" value="<?php echo $pt['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Remove"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Assign Tests Modal -->
<div class="modal-overlay" id="assignTestModal">
    <div class="modal" style="max-width:700px;">
        <div class="modal-header">
            <h3><i class="fas fa-flask" style="color:var(--accent-primary);margin-right:8px;"></i>Assign Tests to Patient</h3>
            <button class="modal-close" onclick="closeModal('assignTestModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="assign">
            <div class="modal-body">
                <div class="form-group">
                    <label>Select Patient <span class="required">*</span></label>
                    <select name="patient_id" class="form-control" required>
                        <option value="">Choose patient...</option>
                        <?php foreach ($allPatients as $ap): ?>
                            <option value="<?php echo $ap['id']; ?>" <?php echo $selPatient == $ap['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ap['full_name']); ?> (<?php echo $ap['patient_id']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <label style="margin:0;">Select Tests <span class="required">*</span></label>
                        <div style="display:flex;gap:8px;">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="selectAllTests(true)" id="btnSelectAll">
                                <i class="fas fa-check-square"></i> Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="selectAllTests(false)">
                                <i class="fas fa-square"></i> Deselect All
                            </button>
                        </div>
                    </div>

                    <!-- Live selection counter -->
                    <div id="testSelCounter" style="font-size:13px;color:var(--accent-primary);font-weight:600;margin-bottom:8px;min-height:20px;"></div>

                    <div style="max-height:380px;overflow-y:auto;border:1px solid var(--border-color);border-radius:var(--radius-md);padding:16px;">
                        <?php foreach ($categories as $cat => $tests): ?>
                            <?php $catId = preg_replace('/[^a-z0-9]/i', '_', $cat); ?>
                            <div style="margin-bottom:16px;">
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid var(--border-color);">
                                    <input type="checkbox" class="cat-check" id="cat_<?php echo $catId; ?>"
                                        data-cat="<?php echo htmlspecialchars($cat); ?>"
                                        onchange="toggleCategory('<?php echo htmlspecialchars($cat); ?>', this.checked)"
                                        style="accent-color:var(--accent-primary);width:15px;height:15px;cursor:pointer;">
                                    <h4 style="font-size:14px;color:var(--accent-primary);margin:0;cursor:pointer;"
                                        onclick="document.getElementById('cat_<?php echo $catId; ?>').click()">
                                        <i class="fas fa-vial" style="margin-right:6px;"></i><?php echo $cat; ?>
                                        <span style="font-size:11px;color:var(--text-muted);font-weight:400;margin-left:6px;">(<?php echo count($tests); ?> tests)</span>
                                    </h4>
                                </div>
                                <?php foreach ($tests as $t): ?>
                                    <label style="display:flex;align-items:center;gap:10px;padding:6px 4px;cursor:pointer;color:var(--text-secondary);font-size:13px;border-radius:6px;transition:background 0.15s;"
                                        onmouseover="this.style.background='rgba(6,182,212,0.05)'" onmouseout="this.style.background=''">
                                        <input type="checkbox" name="test_ids[]" value="<?php echo $t['id']; ?>"
                                            data-cat="<?php echo htmlspecialchars($cat); ?>"
                                            class="test-check"
                                            onchange="updateTestCounter(); syncCatCheck('<?php echo htmlspecialchars($cat); ?>')"
                                            style="accent-color:var(--accent-primary);">
                                        <span><?php echo htmlspecialchars($t['test_name']); ?></span>
                                        <span style="margin-left:auto;color:var(--text-muted);font-size:12px;"><?php echo number_format($t['price'], 0); ?> IQD</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('assignTestModal')" data-translate="Cancel">Cancel</button>
                <button type="submit" class="btn btn-primary" id="assignSubmitBtn"><i class="fas fa-check"></i> <span data-translate="Assign Selected Tests">Assign Selected Tests</span></button>
            </div>
        </form>
    </div>
</div>

<style>
#bulkBar { display: none; }
#bulkBar.visible { display: flex !important; }
.test-row.selected { background: rgba(6,182,212,0.06) !important; }
</style>

<script>
//  Assign modal: select all / deselect all 
function selectAllTests(checked) {
    document.querySelectorAll('.test-check').forEach(cb => cb.checked = checked);
    document.querySelectorAll('.cat-check').forEach(cb => cb.checked = checked);
    updateTestCounter();
}

function toggleCategory(cat, checked) {
    document.querySelectorAll(`.test-check[data-cat="${CSS.escape(cat)}"]`).forEach(cb => cb.checked = checked);
    updateTestCounter();
}

function syncCatCheck(cat) {
    const all  = document.querySelectorAll(`.test-check[data-cat="${CSS.escape(cat)}"]`);
    const chkd = document.querySelectorAll(`.test-check[data-cat="${CSS.escape(cat)}"]:checked`);
    const catCb = document.querySelector(`.cat-check[data-cat="${CSS.escape(cat)}"]`);
    if (catCb) catCb.checked = all.length === chkd.length;
}

function updateTestCounter() {
    const count = document.querySelectorAll('.test-check:checked').length;
    const el = document.getElementById('testSelCounter');
    if (count === 0) {
        el.textContent = '';
    } else {
        el.innerHTML = `<i class="fas fa-check-circle" style="margin-right:4px;"></i>${count} test${count > 1 ? 's' : ''} selected`;
    }
}

//  Tests table: row checkbox / bulk bar 
function toggleSelectAll(master) {
    document.querySelectorAll('.row-check').forEach(cb => {
        cb.checked = master.checked;
        cb.closest('tr').classList.toggle('selected', master.checked);
    });
    onRowCheck();
}

function onRowCheck() {
    const checked = document.querySelectorAll('.row-check:checked');
    const bar = document.getElementById('bulkBar');
    const countEl = document.getElementById('bulkCount');

    // sync master checkbox
    const all = document.querySelectorAll('.row-check');
    document.getElementById('selectAll').checked = all.length > 0 && checked.length === all.length;
    document.getElementById('selectAll').indeterminate = checked.length > 0 && checked.length < all.length;

    // highlight rows
    document.querySelectorAll('.row-check').forEach(cb => {
        cb.closest('tr').classList.toggle('selected', cb.checked);
    });

    if (checked.length > 0) {
        bar.classList.add('visible');
        countEl.innerHTML = `<i class="fas fa-check-square" style="margin-right:5px;"></i>${checked.length} test${checked.length > 1 ? 's' : ''} selected`;
    } else {
        bar.classList.remove('visible');
    }
}

function getCheckedIds() {
    return [...document.querySelectorAll('.row-check:checked')].map(cb => cb.value);
}

function submitBulk(newStatus) {
    const ids = getCheckedIds();
    if (!ids.length) return;
    if (!confirm(`Set ${ids.length} test(s) to "${newStatus.replace('_',' ')}"?`)) return;

    const form = document.getElementById('bulkForm');
    // clear old hidden inputs
    document.getElementById('bulkIdsContainer').innerHTML = '';
    ids.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'pt_ids[]'; inp.value = id;
        document.getElementById('bulkIdsContainer').appendChild(inp);
    });
    form.querySelector('input[name="bulk_status"]')?.remove();
    const statusInp = document.createElement('input');
    statusInp.type = 'hidden'; statusInp.name = 'bulk_status'; statusInp.value = newStatus;
    form.appendChild(statusInp);
    form.submit();
}

function submitBulkDelete() {
    const ids = getCheckedIds();
    if (!ids.length) return;
    if (!confirm(`Remove ${ids.length} test(s)? This cannot be undone.`)) return;

    document.getElementById('bulkDeleteIdsContainer').innerHTML = '';
    ids.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'pt_ids[]'; inp.value = id;
        document.getElementById('bulkDeleteIdsContainer').appendChild(inp);
    });
    document.getElementById('bulkDeleteForm').submit();
}

function clearSelection() {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    document.getElementById('selectAll').indeterminate = false;
    document.querySelectorAll('.test-row').forEach(r => r.classList.remove('selected'));
    document.getElementById('bulkBar').classList.remove('visible');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
