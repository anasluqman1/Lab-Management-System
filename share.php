<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_email'])) {
        $email = sanitize($_POST['email']);
        $url = sanitize($_POST['url']);
        $pName = sanitize($_POST['patient_name']);

        $subject = APP_NAME . " - Lab Test Results for $pName";
        $message = "
        <html>
        <body style='font-family:Arial,sans-serif;background:#f8fafc;padding:20px;'>
            <div style='max-width:500px;margin:0 auto;background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);'>
                <div style='background:linear-gradient(135deg,#06b6d4,#3b82f6);padding:24px;text-align:center;'>
                    <h1 style='color:white;margin:0;font-size:22px;'>" . APP_NAME . "</h1>
                    <p style='color:rgba(255,255,255,0.8);margin:4px 0 0;font-size:14px;'>Laboratory Test Results</p>
                </div>
                <div style='padding:24px;'>
                    <p style='color:#334155;font-size:15px;'>Dear <strong>$pName</strong>,</p>
                    <p style='color:#64748b;font-size:14px;line-height:1.6;'>Your laboratory test results are ready. Click the button below to view your report.</p>
                    <div style='text-align:center;margin:24px 0;'>
                        <a href='$url' style='display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:white;text-decoration:none;border-radius:10px;font-weight:600;font-size:15px;'>View Your Results</a>
                    </div>
                    <p style='color:#94a3b8;font-size:12px;text-align:center;'>If the button doesn't work, copy this link:<br><a href='$url' style='color:#06b6d4;'>$url</a></p>
                </div>
                <div style='background:#f8fafc;padding:16px;text-align:center;border-top:1px solid #e2e8f0;'>
                    <p style='color:#94a3b8;font-size:11px;margin:0;'>This is an automated message from " . APP_NAME . ". Please do not reply.</p>
                </div>
            </div>
        </body>
        </html>";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . APP_NAME . " <noreply@medlabpro.com>\r\n";

        $sent = @mail($email, $subject, $message, $headers);
        echo json_encode(['success' => $sent, 'message' => $sent ? 'Email sent' : 'Failed to send email. Configure SMTP on your server.']);
        exit;
    }

    $patientId = intval($_POST['patient_id']);
    $testIds = sanitize($_POST['test_ids']);
    $method = sanitize($_POST['method']);

    $token = generateToken();

    try {
        $sharedBy = isLoggedIn() ? getUserId() : null;

        $stmt = $pdo->prepare("INSERT INTO shared_reports (token, patient_id, test_ids, shared_by, shared_via, expires_at) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))");
        $stmt->execute([$token, $patientId, $testIds, $sharedBy, $method]);

        $url = BASE_URL . "/public_report.php?token=$token";
        echo json_encode(['success' => true, 'url' => $url, 'token' => $token]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
