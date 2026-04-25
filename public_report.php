<?php
// public page - no login required
require_once __DIR__ . '/config.php';

$token = sanitize($_GET['token'] ?? '');

if (empty($token)) {
    die('Invalid link.');
}

$stmt = $pdo->prepare("SELECT * FROM shared_reports WHERE token = ? AND (expires_at IS NULL OR expires_at > NOW())");
$stmt->execute([$token]);
$shared = $stmt->fetch();

if (!$shared) {
    echo '<!DOCTYPE html><html><head><title>Report Not Found</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    </head><body style="font-family:Inter,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;background:#0f172a;color:#94a3b8;text-align:center;">
    <div><h1 style="font-size:64px;color:#ef4444;">404</h1><p style="font-size:18px;">This report link is invalid or has expired.</p></div></body></html>';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$shared['patient_id']]);
$patient = $stmt->fetch();

$testIds = explode(',', $shared['test_ids']);
$placeholders = str_repeat('?,', count($testIds) - 1) . '?';
$stmt = $pdo->prepare("
    SELECT pt.*, tc.test_name, tc.test_code, tc.unit, tc.normal_range_text, tc.category,
           tr.result_value, tr.result_numeric, tr.is_abnormal, tr.notes
    FROM patient_tests pt
    JOIN test_catalog tc ON pt.test_id = tc.id
    JOIN test_results tr ON pt.id = tr.patient_test_id
    WHERE pt.id IN ($placeholders)
    ORDER BY tc.category, tc.test_name
");
$stmt->execute(array_map('intval', $testIds));
$results = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Results - <?php echo htmlspecialchars($patient['full_name']); ?></title>
    <link rel="stylesheet" href="css/report.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .public-banner {
            background: linear-gradient(135deg, #06b6d4, #3b82f6);
            color: white;
            text-align: center;
            padding: 14px;
            font-size: 14px;
            font-weight: 500;
        }

        .public-banner i {
            margin-right: 6px;
        }
    </style>
</head>

<body>
    <div class="public-banner no-print">
        <i class="fas fa-shield-alt"></i> This is a secure, read-only view of your lab results.
        <button onclick="window.print()"
            style="margin-left:12px;padding:6px 16px;background:white;color:#06b6d4;border:none;border-radius:6px;font-weight:600;cursor:pointer;font-size:13px;">
            <i class="fas fa-print"></i> Print
        </button>
    </div>

    <div class="a4-page" style="margin-top:20px;">
        <div class="report-header">
            <div class="lab-logo">
                <div class="logo-circle">
                    <i class="fas fa-microscope"></i>
                </div>
            </div>
            <div class="lab-info">
                <h1><?php echo APP_NAME; ?></h1>
                <p class="lab-subtitle">Clinical & Diagnostic Laboratory</p>
                <p class="lab-contact">123 Medical Center Drive | Phone: (555) 123-4567</p>
            </div>
        </div>

        <div class="report-divider"></div>
        <h2 class="report-title">LABORATORY TEST REPORT</h2>

        <div class="patient-section">
            <div class="patient-grid">
                <div class="info-item">
                    <span class="info-label">Patient Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($patient['full_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Patient ID</span>
                    <span class="info-value"><?php echo $patient['patient_id']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Age / Gender</span>
                    <span class="info-value"><?php echo $patient['age']; ?> years /
                        <?php echo $patient['gender']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Report Date</span>
                    <span class="info-value"><?php echo formatDate($shared['created_at']); ?></span>
                </div>
            </div>
        </div>

        <div class="results-section">
            <?php
            $lastCat = '';
            foreach ($results as $r):
                if ($r['category'] !== $lastCat):
                    if ($lastCat !== ''): ?>
                        </tbody>
                        </table>
                    <?php endif;
                    $lastCat = $r['category'];
                    ?>
                    <h3 class="category-title"><?php echo $lastCat; ?></h3>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Test Name</th>
                                <th>Result</th>
                                <th>Unit</th>
                                <th>Reference Range</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php endif; ?>
                        <tr class="<?php echo $r['is_abnormal'] ? 'abnormal-row' : ''; ?>">
                            <td><strong><?php echo htmlspecialchars($r['test_name']); ?></strong></td>
                            <td class="result-value <?php echo $r['is_abnormal'] ? 'abnormal' : 'normal'; ?>">
                                <strong><?php echo htmlspecialchars($r['result_value']); ?></strong>
                            </td>
                            <td><?php echo $r['unit']; ?></td>
                            <td style="font-size:11px;"><?php echo $r['normal_range_text']; ?></td>
                            <td>
                                <?php if ($r['is_abnormal']): ?>
                                    <span class="flag-abnormal">⚠ Abnormal</span>
                                <?php else: ?>
                                    <span class="flag-normal">✓ Normal</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!empty($results)): ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="report-footer">
            <div class="signature-block">
                <div class="sig-line"></div>
                <p>Laboratory Technician</p>
            </div>
            <div class="signature-block">
                <div class="sig-line"></div>
                <p>Pathologist / Doctor</p>
            </div>
        </div>

        <div class="report-disclaimer">
            <p>This report is electronically generated. Results relate only to the sample tested. Please correlate
                clinically.</p>
            <p>Generated by <?php echo APP_NAME; ?></p>
        </div>
    </div>
</body>

</html>