<?php
$pageTitle = 'Test Results';
$pageSubtitle = 'Enter and manage test results';
require_once __DIR__ . '/auth.php';
requireRole(['admin', 'technician']);

$success = '';
$error = '';

// ── Single result save (via modal AJAX-friendly POST) ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'save_result') {
        $ptId          = intval($_POST['patient_test_id']);
        $resultValue   = sanitize($_POST['result_value']);
        $resultNumeric = is_numeric($_POST['result_numeric']) && $_POST['result_numeric'] !== '' ? floatval($_POST['result_numeric']) : null;
        // Auto-derive numeric from result_value if numeric field was left empty
        if ($resultNumeric === null && is_numeric($resultValue)) {
            $resultNumeric = floatval($resultValue);
        }
        $notes         = sanitize($_POST['notes']);

        $testInfo = $pdo->prepare("
            SELECT tc.* FROM patient_tests pt
            JOIN test_catalog tc ON pt.test_id = tc.id
            WHERE pt.id = ?
        ");
        $testInfo->execute([$ptId]);
        $testInfo = $testInfo->fetch();

        $isAbnormal = 0;
        if ($resultNumeric !== null && $testInfo['normal_range_min'] !== null && $testInfo['normal_range_max'] !== null) {
            if ($resultNumeric < $testInfo['normal_range_min'] || $resultNumeric > $testInfo['normal_range_max']) {
                $isAbnormal = 1;
            }
        }

        try {
            $existing = $pdo->prepare("SELECT id FROM test_results WHERE patient_test_id = ?");
            $existing->execute([$ptId]);

            if ($existing->fetch()) {
                $pdo->prepare("UPDATE test_results SET result_value=?, result_numeric=?, is_abnormal=?, notes=?, entered_by=? WHERE patient_test_id=?")
                    ->execute([$resultValue, $resultNumeric, $isAbnormal, $notes, getUserId(), $ptId]);
                $success = "Result updated successfully!";
            } else {
                $pdo->prepare("INSERT INTO test_results (patient_test_id, result_value, result_numeric, is_abnormal, notes, entered_by) VALUES (?,?,?,?,?,?)")
                    ->execute([$ptId, $resultValue, $resultNumeric, $isAbnormal, $notes, getUserId()]);
                $success = "Result saved successfully!";
            }

            $pdo->prepare("UPDATE patient_tests SET status='completed', completed_at=NOW() WHERE id=?")->execute([$ptId]);

            $pInfo = $pdo->prepare("SELECT p.full_name, tc.test_name FROM patient_tests pt JOIN patients p ON pt.patient_id=p.id JOIN test_catalog tc ON pt.test_id=tc.id WHERE pt.id=?");
            $pInfo->execute([$ptId]);
            $pInfo = $pInfo->fetch();
            broadcastNotification($pdo, ['admin', 'technician', 'doctor'], 'result', 'Result Entered', "Result for {$pInfo['test_name']} entered for {$pInfo['full_name']}", 'results.php');
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    // ── BATCH result save ──────────────────────────────────────────────────
    if ($_POST['action'] === 'batch_save') {
        $batchIds     = $_POST['batch_pt_id']     ?? [];
        $batchValues  = $_POST['batch_result']     ?? [];
        $batchNums    = $_POST['batch_numeric']    ?? [];
        $batchNotes   = $_POST['batch_notes']      ?? [];

        $saved  = 0;
        $errors = 0;

        foreach ($batchIds as $idx => $ptId) {
            $ptId        = intval($ptId);
            $resultValue = sanitize($batchValues[$idx] ?? '');
            if ($resultValue === '') continue; // skip empty rows

            $resultNumeric = is_numeric($batchNums[$idx] ?? '') && ($batchNums[$idx] ?? '') !== '' ? floatval($batchNums[$idx]) : null;
            $notes         = sanitize($batchNotes[$idx] ?? '');

            // Auto-derive numeric from result_value if numeric field was left empty
            if ($resultNumeric === null && is_numeric($resultValue)) {
                $resultNumeric = floatval($resultValue);
            }

            $testInfo = $pdo->prepare("SELECT tc.* FROM patient_tests pt JOIN test_catalog tc ON pt.test_id=tc.id WHERE pt.id=?");
            $testInfo->execute([$ptId]);
            $testInfo = $testInfo->fetch();

            $isAbnormal = 0;
            if ($resultNumeric !== null && $testInfo['normal_range_min'] !== null && $testInfo['normal_range_max'] !== null) {
                if ($resultNumeric < $testInfo['normal_range_min'] || $resultNumeric > $testInfo['normal_range_max']) {
                    $isAbnormal = 1;
                }
            }

            try {
                $existing = $pdo->prepare("SELECT id FROM test_results WHERE patient_test_id=?");
                $existing->execute([$ptId]);
                if ($existing->fetch()) {
                    $pdo->prepare("UPDATE test_results SET result_value=?, result_numeric=?, is_abnormal=?, notes=?, entered_by=? WHERE patient_test_id=?")
                        ->execute([$resultValue, $resultNumeric, $isAbnormal, $notes, getUserId(), $ptId]);
                } else {
                    $pdo->prepare("INSERT INTO test_results (patient_test_id, result_value, result_numeric, is_abnormal, notes, entered_by) VALUES (?,?,?,?,?,?)")
                        ->execute([$ptId, $resultValue, $resultNumeric, $isAbnormal, $notes, getUserId()]);
                }
                $pdo->prepare("UPDATE patient_tests SET status='completed', completed_at=NOW() WHERE id=?")->execute([$ptId]);
                $saved++;
            } catch (Exception $e) {
                $errors++;
            }
        }

        if ($saved > 0) {
            $success = "$saved result(s) saved successfully!" . ($errors ? " ($errors failed)" : "");
        } elseif ($errors > 0) {
            $error = "Failed to save results. Please try again.";
        } else {
            $error = "No results were filled in.";
        }
    }
}

// ── Data for the "enter result" modal (single test) ────────────────────────
$editId     = intval($_GET['patient_test_id'] ?? 0);
$editTest   = null;
$editResult = null;
if ($editId) {
    $stmt = $pdo->prepare("
        SELECT pt.*, p.full_name as patient_name, p.patient_id as pid, p.age, p.gender,
               tc.test_name, tc.test_code, tc.unit, tc.normal_range_min, tc.normal_range_max, tc.normal_range_text, tc.category
        FROM patient_tests pt
        JOIN patients p ON pt.patient_id = p.id
        JOIN test_catalog tc ON pt.test_id = tc.id
        WHERE pt.id = ?
    ");
    $stmt->execute([$editId]);
    $editTest = $stmt->fetch();

    if ($editTest) {
        $res = $pdo->prepare("SELECT * FROM test_results WHERE patient_test_id=?");
        $res->execute([$editId]);
        $editResult = $res->fetch();
    }
}

// ── Tests awaiting results (completed status, no result entered yet) ────────
$pendingResults = $pdo->query("
    SELECT pt.id, pt.status, p.id as patient_db_id, p.full_name as patient_name, p.patient_id as pid, p.age, p.gender,
           tc.test_name, tc.test_code, tc.unit, tc.normal_range_min, tc.normal_range_max, tc.normal_range_text, tc.category
    FROM patient_tests pt
    JOIN patients p ON pt.patient_id = p.id
    JOIN test_catalog tc ON pt.test_id = tc.id
    LEFT JOIN test_results tr ON pt.id = tr.patient_test_id
    WHERE pt.status = 'completed' AND tr.id IS NULL
    ORDER BY p.full_name ASC, pt.completed_at ASC
")->fetchAll();

// Group pending results by patient — PREVENTS MIX-UPS
$pendingByPatient = [];
foreach ($pendingResults as $pr) {
    $key = $pr['patient_db_id'];
    if (!isset($pendingByPatient[$key])) {
        $pendingByPatient[$key] = [
            'patient_name' => $pr['patient_name'],
            'pid'          => $pr['pid'],
            'age'          => $pr['age'],
            'gender'       => $pr['gender'],
            'tests'        => [],
        ];
    }
    $pendingByPatient[$key]['tests'][] = $pr;
}

// ── All completed results ──────────────────────────────────────────────────
$allResults = $pdo->query("
    SELECT pt.*, p.full_name as patient_name, p.patient_id as pid,
           tc.test_name, tc.test_code, tc.unit, tc.normal_range_text, tc.category,
           tr.result_value, tr.result_numeric, tr.is_abnormal, tr.entered_at as result_date, tr.notes
    FROM patient_tests pt
    JOIN patients p ON pt.patient_id = p.id
    JOIN test_catalog tc ON pt.test_id = tc.id
    LEFT JOIN test_results tr ON pt.id = tr.patient_test_id
    WHERE pt.status = 'completed'
    ORDER BY pt.completed_at DESC
")->fetchAll();

// Patient color palette (cycles if more than 8 patients)
$patientColors = [
    ['accent' => '#06b6d4', 'bg' => 'rgba(6,182,212,0.07)',  'border' => 'rgba(6,182,212,0.3)'],
    ['accent' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.07)', 'border' => 'rgba(139,92,246,0.3)'],
    ['accent' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.07)', 'border' => 'rgba(245,158,11,0.3)'],
    ['accent' => '#10b981', 'bg' => 'rgba(16,185,129,0.07)', 'border' => 'rgba(16,185,129,0.3)'],
    ['accent' => '#ef4444', 'bg' => 'rgba(239,68,68,0.07)',  'border' => 'rgba(239,68,68,0.3)'],
    ['accent' => '#ec4899', 'bg' => 'rgba(236,72,153,0.07)', 'border' => 'rgba(236,72,153,0.3)'],
    ['accent' => '#f97316', 'bg' => 'rgba(249,115,22,0.07)', 'border' => 'rgba(249,115,22,0.3)'],
    ['accent' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.07)', 'border' => 'rgba(59,130,246,0.3)'],
];

include __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?>
    <div class="toast toast-success" id="toast"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="toast toast-error" id="toast"><i class="fas fa-times-circle"></i> <?php echo $error; ?></div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════════
     BATCH ENTRY PANEL — GROUPED BY PATIENT to prevent mix-ups
────────────────────────────────────────────────────────────────────────── -->
<?php if (!empty($pendingByPatient)): ?>

<!-- Quick-Jump Bar: only shown when >1 patient -->
<?php if (count($pendingByPatient) > 1): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:var(--card-bg);border:1px solid var(--border-color);border-radius:var(--radius-md);display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
    <span style="font-size:13px;color:var(--text-muted);white-space:nowrap;"><i class="fas fa-users" style="margin-right:5px;"></i>Jump to patient:</span>
    <?php $ci = 0; foreach ($pendingByPatient as $patId => $patData): $col = $patientColors[$ci % count($patientColors)]; $ci++; ?>
        <a href="#patient_<?php echo $patId; ?>"
            style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;text-decoration:none;
                   background:<?php echo $col['bg']; ?>;border:1px solid <?php echo $col['border']; ?>;color:<?php echo $col['accent']; ?>;transition:opacity 0.2s;"
            onmouseover="this.style.opacity='0.75'" onmouseout="this.style.opacity='1'">
            <i class="fas fa-user" style="font-size:10px;"></i>
            <?php echo htmlspecialchars($patData['patient_name']); ?>
            <span style="background:<?php echo $col['accent']; ?>;color:#fff;border-radius:10px;padding:1px 7px;font-size:10px;">
                <?php echo count($patData['tests']); ?>
            </span>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" id="batchForm">
    <input type="hidden" name="action" value="batch_save">

    <?php $colorIndex = 0; foreach ($pendingByPatient as $patId => $patData):
        $col = $patientColors[$colorIndex % count($patientColors)]; $colorIndex++; ?>

    <div class="patient-batch-card" id="patient_<?php echo $patId; ?>"
        style="margin-bottom:20px;border-radius:var(--radius-lg);border:2px solid <?php echo $col['border']; ?>;overflow:hidden;">

        <!-- Patient Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;
                    background:<?php echo $col['bg']; ?>;border-bottom:1px solid <?php echo $col['border']; ?>;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;border-radius:50%;background:<?php echo $col['accent']; ?>;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-user" style="color:#fff;font-size:16px;"></i>
                </div>
                <div>
                    <div style="font-size:16px;font-weight:700;color:<?php echo $col['accent']; ?>;">
                        <?php echo htmlspecialchars($patData['patient_name']); ?>
                    </div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                        ID: <?php echo $patData['pid']; ?>&nbsp;|&nbsp;
                        <?php echo $patData['age']; ?> yrs&nbsp;|&nbsp;
                        <?php echo $patData['gender']; ?>&nbsp;|&nbsp;
                        <strong style="color:<?php echo $col['accent']; ?>;">
                            <?php echo count($patData['tests']); ?> test<?php echo count($patData['tests']) > 1 ? 's' : ''; ?> awaiting results
                        </strong>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <button type="button" class="btn btn-sm"
                    style="background:<?php echo $col['accent']; ?>;color:#fff;border:none;"
                    onclick="savePatient(<?php echo $patId; ?>)">
                    <i class="fas fa-save"></i> Save <?php echo htmlspecialchars($patData['patient_name']); ?>'s Results
                </button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="togglePatientSection(<?php echo $patId; ?>)">
                    <i class="fas fa-chevron-up" id="ptIcon_<?php echo $patId; ?>"></i>
                </button>
            </div>
        </div>

        <!-- Per-patient test table -->
        <div id="ptSection_<?php echo $patId; ?>">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:rgba(0,0,0,0.04);">
                            <th style="padding:10px 14px;text-align:left;font-size:12px;color:var(--text-muted);font-weight:600;width:190px;">TEST</th>
                            <th style="padding:10px 14px;text-align:left;font-size:12px;color:var(--text-muted);font-weight:600;width:140px;">REF. RANGE</th>
                            <th style="padding:10px 14px;text-align:left;font-size:12px;color:var(--text-muted);font-weight:600;min-width:170px;">RESULT VALUE <span style="color:var(--danger);">*</span></th>
                            <th style="padding:10px 14px;text-align:left;font-size:12px;color:var(--text-muted);font-weight:600;min-width:120px;">NUMERIC</th>
                            <th style="padding:10px 14px;text-align:left;font-size:12px;color:var(--text-muted);font-weight:600;min-width:160px;">NOTES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patData['tests'] as $idx => $pr): ?>
                        <tr class="batch-row" id="brow_<?php echo $pr['id']; ?>"
                            style="border-bottom:1px solid var(--border-color);<?php echo $idx % 2 === 1 ? 'background:rgba(0,0,0,0.015);' : ''; ?>">
                            <td style="padding:12px 14px;">
                                <input type="hidden" name="batch_pt_id[]" value="<?php echo $pr['id']; ?>">
                                <div style="font-weight:700;color:<?php echo $col['accent']; ?>;font-size:13px;"><?php echo $pr['test_code']; ?></div>
                                <div style="font-size:12px;color:var(--text-secondary);"><?php echo htmlspecialchars($pr['test_name']); ?></div>
                                <?php if ($pr['category']): ?>
                                <div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?php echo $pr['category']; ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:12px 14px;font-size:12px;color:var(--text-muted);">
                                <?php echo $pr['normal_range_text'] ?: '—'; ?>
                                <?php if ($pr['unit']): ?><div style="font-style:italic;font-size:11px;margin-top:2px;"><?php echo $pr['unit']; ?></div><?php endif; ?>
                            </td>
                            <td style="padding:10px 14px;">
                                <input type="text" name="batch_result[]"
                                    class="form-control batch-result-input"
                                    placeholder="e.g. 5.2, Positive"
                                    data-patient-id="<?php echo $patId; ?>"
                                    oninput="highlightRow(this)"
                                    style="font-size:13px;border-color:<?php echo $col['border']; ?>;">
                            </td>
                            <td style="padding:10px 14px;">
                                <input type="number" step="0.01" name="batch_numeric[]"
                                    class="form-control"
                                    placeholder="<?php echo $pr['unit'] ?: 'value'; ?>"
                                    style="font-size:13px;">
                            </td>
                            <td style="padding:10px 14px;">
                                <input type="text" name="batch_notes[]"
                                    class="form-control"
                                    placeholder="optional"
                                    style="font-size:13px;">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Per-patient footer -->
            <div style="padding:12px 18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;
                        background:<?php echo $col['bg']; ?>;border-top:1px solid <?php echo $col['border']; ?>;">
                <p style="font-size:12px;color:var(--text-muted);margin:0;">
                    <i class="fas fa-info-circle" style="margin-right:4px;"></i>
                    Fill <strong>Result Value</strong> for each test. Empty rows are skipped.
                </p>
                <div style="display:flex;gap:8px;">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="clearPatient(<?php echo $patId; ?>)">
                        <i class="fas fa-eraser"></i> Clear
                    </button>
                    <button type="button" class="btn btn-sm"
                        style="background:<?php echo $col['accent']; ?>;color:#fff;border:none;"
                        onclick="savePatient(<?php echo $patId; ?>)">
                        <i class="fas fa-save"></i> Save Results
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php endforeach; ?>

    <!-- Global Save All (only when multiple patients) -->
    <?php if (count($pendingByPatient) > 1): ?>
    <div style="margin-bottom:24px;padding:14px 18px;background:var(--card-bg);border:1px solid var(--border-color);border-radius:var(--radius-md);
                display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <span style="font-size:14px;color:var(--text-secondary);">
            <i class="fas fa-layer-group" style="margin-right:6px;color:var(--accent-primary);"></i>
            Save all filled results across <strong><?php echo count($pendingByPatient); ?> patients</strong>
        </span>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save All Results
        </button>
    </div>
    <?php endif; ?>

</form>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════════
     COMPLETED RESULTS TABLE
────────────────────────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-file-medical-alt" style="color:var(--success);margin-right:8px;"></i>Completed Test Results</h3>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th data-translate="Patient">Patient</th>
                    <th data-translate="Test">Test</th>
                    <th data-translate="Result">Result</th>
                    <th>Unit</th>
                    <th>Reference Range</th>
                    <th data-translate="Status">Status</th>
                    <th data-translate="Date">Date</th>
                    <th data-translate="Actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allResults)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;color:var(--text-muted);padding:40px;">No results yet</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($allResults as $r): ?>
                        <tr>
                            <td>
                                <strong style="color:var(--text-primary);"><?php echo htmlspecialchars($r['patient_name']); ?></strong>
                                <br><small style="color:var(--text-muted);"><?php echo $r['pid']; ?></small>
                            </td>
                            <td>
                                <span style="color:var(--accent-primary);font-weight:600;"><?php echo $r['test_code']; ?></span>
                                <br><small><?php echo htmlspecialchars($r['test_name']); ?></small>
                            </td>
                            <td style="font-weight:600;color:<?php echo $r['is_abnormal'] ? 'var(--danger)' : 'var(--success)'; ?>;">
                                <?php echo $r['result_value'] ?? '<span style="color:var(--text-muted);">—</span>'; ?>
                            </td>
                            <td style="color:var(--text-muted);"><?php echo $r['unit']; ?></td>
                            <td style="font-size:12px;color:var(--text-muted);"><?php echo $r['normal_range_text']; ?></td>
                            <td>
                                <?php if ($r['result_value']): ?>
                                    <span class="badge <?php echo $r['is_abnormal'] ? 'badge-abnormal' : 'badge-normal'; ?>">
                                        <?php echo $r['is_abnormal'] ? 'Abnormal' : 'Normal'; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-pending">No Result</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:13px;color:var(--text-muted);">
                                <?php echo $r['result_date'] ? formatDateTime($r['result_date']) : '—'; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-secondary" title="Edit Result"
                                    onclick="openEditModal(<?php echo $r['id']; ?>, <?php echo htmlspecialchars(json_encode($r), ENT_QUOTES); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     EDIT RESULT MODAL (replaces full-page redirect)
────────────────────────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="editResultModal">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit" style="color:var(--accent-primary);margin-right:8px;"></i>Edit Result</h3>
            <button class="modal-close" onclick="closeModal('editResultModal')">&times;</button>
        </div>
        <form method="POST" id="editResultForm">
            <input type="hidden" name="action" value="save_result">
            <input type="hidden" name="patient_test_id" id="erModalPtId">
            <div class="modal-body">

                <!-- Patient / Test info cards -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                    <div style="padding:14px;background:rgba(6,182,212,0.05);border-radius:var(--radius-md);border:1px solid rgba(6,182,212,0.1);">
                        <h4 style="font-size:12px;color:var(--text-muted);margin-bottom:6px;">PATIENT</h4>
                        <p id="erPatientName" style="font-size:15px;font-weight:600;color:var(--text-primary);margin:0;"></p>
                        <p id="erPatientMeta" style="font-size:12px;color:var(--text-muted);margin:2px 0 0;"></p>
                    </div>
                    <div style="padding:14px;background:rgba(139,92,246,0.05);border-radius:var(--radius-md);border:1px solid rgba(139,92,246,0.1);">
                        <h4 style="font-size:12px;color:var(--text-muted);margin-bottom:6px;">TEST</h4>
                        <p id="erTestName" style="font-size:15px;font-weight:600;color:var(--text-primary);margin:0;"></p>
                        <p id="erTestMeta" style="font-size:12px;color:var(--text-muted);margin:2px 0 0;"></p>
                        <p id="erRange" style="font-size:12px;color:var(--success);margin:4px 0 0;"></p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Result Value <span class="required">*</span></label>
                        <input type="text" name="result_value" id="erResultValue" class="form-control" required
                            placeholder="e.g. 5.2, Positive, Non-Reactive">
                    </div>
                    <div class="form-group">
                        <label>Numeric Value <small style="color:var(--text-muted);">(for range check)</small></label>
                        <input type="number" step="0.01" name="result_numeric" id="erResultNumeric" class="form-control"
                            placeholder="numeric value">
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" id="erNotes" class="form-control" placeholder="Additional notes or comments"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editResultModal')" data-translate="Cancel">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Result</button>
            </div>
        </form>
    </div>
</div>

<!-- open-on-load modal if ?patient_test_id is in URL -->
<?php if ($editTest): ?>
<script>
window.addEventListener('DOMContentLoaded', () => {
    const data = <?php echo json_encode($editTest); ?>;
    const result = <?php echo json_encode($editResult); ?>;
    populateEditModal(data.id, data, result);
    openModal('editResultModal');
    // clean URL so refresh doesn't re-open
    history.replaceState({}, '', 'results.php');
});
</script>
<?php endif; ?>

<style>
/* Batch entry row highlights */
.batch-row.has-value { background: rgba(16,185,129,0.05) !important; }
.batch-row.has-value td:first-child { border-left: 3px solid var(--success); }
.patient-batch-card { transition: box-shadow 0.2s; }
.patient-batch-card:hover { box-shadow: 0 4px 24px rgba(0,0,0,0.1); }
</style>

<script>
// ── Per-patient collapse ──────────────────────────────────────────────────
const ptExpanded = {};
function togglePatientSection(patId) {
    const sec  = document.getElementById('ptSection_' + patId);
    const icon = document.getElementById('ptIcon_' + patId);
    if (ptExpanded[patId] === false) {
        sec.style.display = '';
        icon.className = 'fas fa-chevron-up';
        ptExpanded[patId] = true;
    } else {
        sec.style.display = 'none';
        icon.className = 'fas fa-chevron-down';
        ptExpanded[patId] = false;
    }
}

// ── Clear one patient's inputs ─────────────────────────────────────────────
function clearPatient(patId) {
    document.querySelectorAll(`.batch-result-input[data-patient-id="${patId}"]`).forEach(i => {
        i.value = '';
        highlightRow(i);
    });
    // Also clear the numeric + notes on the same rows
    document.querySelectorAll(`#ptSection_${patId} input[type=number]`).forEach(i => i.value = '');
    document.querySelectorAll(`#ptSection_${patId} input[name="batch_notes[]"]`).forEach(i => i.value = '');
}

// ── Save only one patient's results ────────────────────────────────────────
function savePatient(patId) {
    // Temporarily blank out other patients' result-value inputs so they are skipped
    const allResultInputs = document.querySelectorAll('.batch-result-input');
    const savedValues = [];
    allResultInputs.forEach(inp => {
        savedValues.push(inp.value);
        if (parseInt(inp.dataset.patientId) !== patId) inp.value = '';
    });
    document.getElementById('batchForm').submit();
    // Restore (won't matter as page reloads, but defensive)
    allResultInputs.forEach((inp, i) => inp.value = savedValues[i]);
}

// ── Highlight row green when a value is typed ─────────────────────────────────
function highlightRow(input) {
    input.closest('tr').classList.toggle('has-value', input.value.trim() !== '');
}

// ── Edit result modal ────────────────────────────────────────────────────────
function openEditModal(ptId, rowData) {
    document.getElementById('erModalPtId').value = ptId;
    document.getElementById('erPatientName').textContent = rowData.patient_name || '';
    document.getElementById('erPatientMeta').textContent =
        (rowData.pid || '') +
        (rowData.age    ? ' | ' + rowData.age + ' yrs' : '') +
        (rowData.gender ? ' | ' + rowData.gender : '');
    document.getElementById('erTestName').textContent = rowData.test_name || '';
    document.getElementById('erTestMeta').textContent =
        (rowData.test_code || '') + (rowData.category ? ' · ' + rowData.category : '');
    document.getElementById('erRange').innerHTML = rowData.normal_range_text
        ? '<i class="fas fa-check-circle" style="margin-right:3px;"></i>Normal: ' + rowData.normal_range_text
        : '';
    document.getElementById('erResultValue').value  = rowData.result_value  || '';
    document.getElementById('erResultNumeric').value = rowData.result_numeric || '';
    document.getElementById('erNotes').value         = rowData.notes         || '';
    openModal('editResultModal');
}

function populateEditModal(ptId, testData, resultData) {
    openEditModal(ptId, Object.assign({}, testData, resultData || {}));
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>