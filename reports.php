<?php
$pageTitle = 'Reports';
$pageSubtitle = 'Generate and view lab reports';
require_once __DIR__ . '/auth.php';

$patients = $pdo->query("
    SELECT DISTINCT p.id, p.patient_id, p.full_name, p.age, p.gender
    FROM patients p
    JOIN patient_tests pt ON p.id = pt.patient_id
    JOIN test_results tr ON pt.id = tr.patient_test_id
    ORDER BY p.full_name
")->fetchAll();

$selId = intval($_GET['patient'] ?? 0);
$tests = [];
$patientInfo = null;

if ($selId) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$selId]);
    $patientInfo = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT pt.id, tc.test_name, tc.test_code, tc.category, tr.result_value, tr.is_abnormal, pt.completed_at
        FROM patient_tests pt
        JOIN test_catalog tc ON pt.test_id = tc.id
        JOIN test_results tr ON pt.id = tr.patient_test_id
        WHERE pt.patient_id = ? AND pt.status = 'completed'
        ORDER BY tc.category, tc.test_name
    ");
    $stmt->execute([$selId]);
    $tests = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>

<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h3><i class="fas fa-file-invoice" style="color:var(--accent-primary);margin-right:8px;"></i>Generate Report
        </h3>
    </div>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;">
        <div>
            <div class="form-group">
                <label data-translate="Patient">Select Patient</label>
                <select class="form-control"
                    onchange="if(this.value)window.location='reports.php?patient='+this.value;">
                    <option value="">Choose a patient...</option>
                    <?php foreach ($patients as $pw): ?>
                        <option value="<?php echo $pw['id']; ?>" <?php echo $selId == $pw['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pw['full_name']); ?> (<?php echo $pw['patient_id']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($patientInfo): ?>
                <div
                    style="padding:16px;background:rgba(6,182,212,0.05);border-radius:var(--radius-md);border:1px solid rgba(6,182,212,0.1);margin-top:12px;">
                    <h4 style="font-size:15px;color:var(--text-primary);margin-bottom:8px;">
                        <?php echo htmlspecialchars($patientInfo['full_name']); ?>
                    </h4>
                    <p style="font-size:13px;color:var(--text-muted);">ID: <?php echo $patientInfo['patient_id']; ?></p>
                    <p style="font-size:13px;color:var(--text-muted);">Age: <?php echo $patientInfo['age']; ?> |
                        <?php echo $patientInfo['gender']; ?>
                    </p>
                    <?php if ($patientInfo['phone']): ?>
                        <p style="font-size:13px;color:var(--text-muted);"><i class="fas fa-phone"></i>
                            <?php echo $patientInfo['phone']; ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <?php if ($selId && !empty($tests)): ?>
                <form method="GET" action="report_view.php">
                    <input type="hidden" name="patient" value="<?php echo $selId; ?>">
                    <div class="form-group">
                        <label>Select Tests to Include in Report</label>
                        <div
                            style="border:1px solid var(--border-color);border-radius:var(--radius-md);padding:16px;max-height:350px;overflow-y:auto;">
                            <label
                                style="display:flex;align-items:center;gap:10px;padding:8px 0;cursor:pointer;color:var(--accent-primary);font-weight:600;font-size:14px;border-bottom:1px solid var(--border-color);margin-bottom:8px;">
                                <input type="checkbox" id="selectAll" onclick="toggleAll(this)"
                                    style="accent-color:var(--accent-primary);"> Select All
                            </label>
                            <?php
                            $lastCat = '';
                            foreach ($tests as $pt):
                                if ($pt['category'] !== $lastCat):
                                    $lastCat = $pt['category'];
                                    ?>
                                    <h5
                                        style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin:12px 0 6px;padding-top:8px;border-top:1px solid var(--border-color);">
                                        <?php echo $lastCat; ?>
                                    </h5>
                                <?php endif; ?>
                                <label
                                    style="display:flex;align-items:center;gap:10px;padding:6px 0;cursor:pointer;color:var(--text-secondary);font-size:13px;"
                                    class="test-check">
                                    <input type="checkbox" name="tests[]" value="<?php echo $pt['id']; ?>"
                                        style="accent-color:var(--accent-primary);">
                                    <span><?php echo htmlspecialchars($pt['test_name']); ?></span>
                                    <span
                                        style="margin-left:auto;font-weight:600;color:<?php echo $pt['is_abnormal'] ? 'var(--danger)' : 'var(--success)'; ?>;">
                                        <?php echo $pt['result_value']; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:16px;">
                        <label><i class="fas fa-user-md" style="color:var(--accent-primary);margin-right:6px;"></i>Referred
                            to Doctor</label>
                        <input type="text" name="doctor_name" class="form-control"
                            placeholder="Enter doctor name (e.g. Dr. Ahmed Hassan)" style="margin-top:6px;">
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:12px;">
                        <i class="fas fa-file-medical"></i> Generate Report
                    </button>
                </form>
            <?php elseif ($selId): ?>
                <div class="empty-state">
                    <i class="fas fa-flask"></i>
                    <h4>No Completed Tests</h4>
                    <p>This patient has no completed test results yet.</p>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-hand-pointer"></i>
                    <h4>Select a Patient</h4>
                    <p>Choose a patient from the dropdown to see their completed tests.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function toggleAll(el) {
        document.querySelectorAll('.test-check input[type="checkbox"]').forEach(cb => cb.checked = el.checked);
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>