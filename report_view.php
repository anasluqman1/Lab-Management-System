<?php
require_once __DIR__ . '/auth.php';

$patientId = intval($_GET['patient'] ?? 0);
$testIds = $_GET['tests'] ?? [];
$doctorName = trim(htmlspecialchars($_GET['doctor_name'] ?? ''));

if (!$patientId || empty($testIds)) {
    redirect('reports.php');
}

$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

if (!$patient) {
    redirect('reports.php');
}

$placeholders = str_repeat('?,', count($testIds) - 1) . '?';
$stmt = $pdo->prepare("
    SELECT pt.*, tc.test_name, tc.test_code, tc.unit, tc.normal_range_text, tc.category, tc.normal_range_min, tc.normal_range_max,
           tr.result_value, tr.result_numeric, tr.is_abnormal, tr.notes, tr.entered_at,
           u.full_name as entered_by_name
    FROM patient_tests pt
    JOIN test_catalog tc ON pt.test_id = tc.id
    JOIN test_results tr ON pt.id = tr.patient_test_id
    LEFT JOIN users u ON tr.entered_by = u.id
    WHERE pt.id IN ($placeholders) AND pt.patient_id = ?
    ORDER BY tc.category, tc.test_name
");
$params = array_map('intval', $testIds);
$params[] = $patientId;
$stmt->execute($params);
$results = $stmt->fetchAll();

// save doctor name to tests for analytics tracking
if (!empty($doctorName) && !empty($testIds)) {
    $ph = str_repeat('?,', count($testIds) - 1) . '?';
    $upd = $pdo->prepare("UPDATE patient_tests SET referred_doctor = ? WHERE id IN ($ph)");
    $updParams = array_merge([$doctorName], array_map('intval', $testIds));
    $upd->execute($updParams);
}

$testIdStr = implode(',', array_map('intval', $testIds));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Report - <?php echo htmlspecialchars($patient['full_name']); ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/report.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="action-bar no-print">
        <a href="reports.php?patient=<?php echo $patientId; ?>" class="action-btn back">
            <i class="fas fa-arrow-left"></i> Back to Reports
        </a>
        <div class="action-group">
            <button class="action-btn print" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <button class="action-btn pdf" onclick="window.print()">
                <i class="fas fa-file-pdf"></i> Save PDF
            </button>
            <button class="action-btn email" onclick="shareReport('email')">
                <i class="fas fa-envelope"></i> Email
            </button>
            <button class="action-btn whatsapp" onclick="shareReport('whatsapp')">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </button>
            <button class="action-btn viber" onclick="shareReport('viber')">
                <i class="fab fa-viber"></i> Viber
            </button>
            <button class="action-btn qr" onclick="shareReport('qr')">
                <i class="fas fa-qrcode"></i> QR Code
            </button>
        </div>
    </div>

    <div class="a4-page">
        <div class="report-header">
            <div class="lab-logo">
                <div class="logo-circle">
                    <i class="fas fa-microscope"></i>
                </div>
            </div>
            <div class="lab-info">
                <h1><?php echo APP_NAME; ?></h1>
                <p class="lab-subtitle">Clinical & Diagnostic Laboratory</p>
                <p class="lab-contact">123 Medical Center Drive | Phone: (555) 123-4567 | Email: info@medlabpro.com</p>
                <p class="lab-contact">License No: LAB-2024-001 | Accredited Laboratory</p>
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
                    <span class="info-value"><?php echo date('F d, Y'); ?></span>
                </div>
                <?php if (!empty($patient['blood_group'])): ?>
                    <div class="info-item">
                        <span class="info-label">Blood Group</span>
                        <span class="info-value"
                            style="font-weight:700;color:#ef4444;"><?php echo htmlspecialchars($patient['blood_group']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($doctorName)): ?>
                    <div class="info-item">
                        <span class="info-label">Referred to</span>
                        <span class="info-value"><?php echo $doctorName; ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($patient['phone']): ?>
                    <div class="info-item">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?php echo htmlspecialchars($patient['phone']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($patient['email']): ?>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($patient['email']); ?></span>
                    </div>
                <?php endif; ?>
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
                            <td>
                                <strong><?php echo htmlspecialchars($r['test_name']); ?></strong>
                                <br><small style="color:#888;"><?php echo $r['test_code']; ?></small>
                            </td>
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

        <?php
        $notesArr = array_filter(array_column($results, 'notes'));
        if (!empty($notesArr)):
            ?>
            <div class="notes-section">
                <h3>Notes</h3>
                <ul>
                    <?php foreach ($notesArr as $note): ?>
                        <li><?php echo htmlspecialchars($note); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="report-footer">
            <div class="signature-block">
                <div class="sig-line"></div>
                <p style="font-size:11px;color:#888;margin-bottom:2px;">Technician</p>
                <p>Dr.Darya Ahmed</p>
            </div>
            <div class="signature-block">
                <div class="sig-line"></div>
                <p style="font-size:11px;color:#888;margin-bottom:2px;">Doctor</p>
                <p><?php echo !empty($doctorName) ? $doctorName : 'Pathologist / Doctor'; ?></p>
            </div>
        </div>

        <div class="report-disclaimer">
            <p>This report is electronically generated and is valid without signature. Results relate only to the sample
                tested.
                Please correlate clinically. For any queries, contact the laboratory.</p>
            <p style="margin-top:4px;">Generated on <?php echo date('F d, Y \a\t h:i A'); ?> by <?php echo APP_NAME; ?>
            </p>
        </div>
    </div>

    <div class="share-modal-overlay no-print" id="shareModal" style="display:none;">
        <div class="share-modal">
            <div class="share-modal-header">
                <h3 id="shareTitle">Share Report</h3>
                <button onclick="document.getElementById('shareModal').style.display='none'">&times;</button>
            </div>
            <div class="share-modal-body" id="shareBody"></div>
        </div>
    </div>

    <script>
        function shareReport(method) {
            const patientId = <?php echo $patientId; ?>;
            const tests = '<?php echo $testIdStr; ?>';

            fetch('share.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `patient_id=${patientId}&test_ids=${tests}&method=${method}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const shareUrl = data.url;
                        const modal = document.getElementById('shareModal');
                        const title = document.getElementById('shareTitle');
                        const body = document.getElementById('shareBody');

                        switch (method) {
                            case 'email':
                                title.textContent = 'Send via Email';
                                body.innerHTML = `
                            <form onsubmit="sendEmail(event, '${shareUrl}')">
                                <div style="margin-bottom:15px;">
                                    <label style="display:block;margin-bottom:5px;font-weight:500;">Recipient Email</label>
                                    <input type="email" id="recipientEmail" required placeholder="patient@email.com"
                                           value="<?php echo htmlspecialchars($patient['email'] ?? ''); ?>"
                                           style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:14px;">
                                </div>
                                <button type="submit" style="width:100%;padding:12px;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:white;border:none;border-radius:8px;font-size:15px;cursor:pointer;font-weight:600;">
                                    <i class="fas fa-paper-plane"></i> Send Email
                                </button>
                            </form>`;
                                break;

                            case 'whatsapp':
                                const waMsg = encodeURIComponent(`Your lab test results are ready. View here: ${shareUrl}`);
                                const waPhone = '<?php echo preg_replace('/[^0-9]/', '', $patient['phone'] ?? ''); ?>';
                                window.open(`https://wa.me/${waPhone}?text=${waMsg}`, '_blank');
                                return;

                            case 'viber':
                                const vMsg = encodeURIComponent(`Your lab test results are ready. View here: ${shareUrl}`);
                                window.open(`viber://forward?text=${vMsg}`, '_blank');
                                return;

                            case 'qr':
                                title.textContent = 'QR Code';
                                const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(shareUrl)}`;
                                body.innerHTML = `
                            <div style="text-align:center;">
                                <p style="margin-bottom:15px;color:#666;">Scan this QR code to view the report</p>
                                <img src="${qrUrl}" alt="QR Code" style="border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
                                <p style="margin-top:15px;font-size:12px;color:#999;word-break:break-all;">${shareUrl}</p>
                                <button onclick="navigator.clipboard.writeText('${shareUrl}');this.textContent='Copied!'" 
                                        style="margin-top:10px;padding:8px 20px;background:#06b6d4;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;">
                                    <i class="fas fa-copy"></i> Copy Link
                                </button>
                            </div>`;
                                break;
                        }
                        modal.style.display = 'flex';
                    }
                })
                .catch(err => alert('Error creating share link'));
        }

        function sendEmail(e, url) {
            e.preventDefault();
            const email = document.getElementById('recipientEmail').value;
            fetch('share.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `send_email=1&email=${encodeURIComponent(email)}&url=${encodeURIComponent(url)}&patient_name=<?php echo urlencode($patient['full_name']); ?>`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('shareBody').innerHTML = '<div style="text-align:center;padding:20px;"><i class="fas fa-check-circle" style="font-size:48px;color:#10b981;"></i><p style="margin-top:15px;font-size:16px;">Email sent successfully!</p></div>';
                    } else {
                        alert(data.message || 'Failed to send email. Make sure your server has mail configured.');
                    }
                });
        }
    </script>
</body>

</html>