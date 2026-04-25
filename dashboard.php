<?php
require_once __DIR__ . '/auth.php';
$pageTitle = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . getUserName();

// this shows howmany patient have tests ordered today, howmany pending tests, howmany completed today, and today's revenue
$totalPatients = $pdo->query(query: "SELECT COUNT(DISTINCT patient_id) FROM patient_tests WHERE DATE(ordered_at) = CURDATE()")->fetchColumn();
$pendingTests = $pdo->query(query: "SELECT COUNT(*) FROM patient_tests WHERE status = 'pending'")->fetchColumn();
$completedToday = $pdo->query(query: "SELECT COUNT(*) FROM patient_tests WHERE status = 'completed' AND DATE(completed_at) = CURDATE()")->fetchColumn();

$revenue = $pdo->query(query: "
    SELECT COALESCE(SUM(tc.price), 0) 
    FROM patient_tests pt 
    JOIN test_catalog tc ON pt.test_id = tc.id 
    WHERE DATE(pt.ordered_at) = CURDATE()
")->fetchColumn();

$recent = $pdo->query(query: "
    SELECT pt.*, p.full_name as patient_name, p.patient_id as pid, tc.test_name, tc.category
    FROM patient_tests pt
    JOIN patients p ON pt.patient_id = p.id
    JOIN test_catalog tc ON pt.test_id = tc.id
    ORDER BY pt.ordered_at DESC
    LIMIT 10
")->fetchAll();

$pending = $pdo->query(query: "
    SELECT pt.*, p.full_name as patient_name, p.patient_id as pid, tc.test_name
    FROM patient_tests pt
    JOIN patients p ON pt.patient_id = p.id
    JOIN test_catalog tc ON pt.test_id = tc.id
    WHERE pt.status IN ('pending', 'in_progress')
    ORDER BY pt.ordered_at ASC
    LIMIT 10
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card cyan">
        <div class="stat-icon cyan">
            <i class="fas fa-user-injured"></i>
        </div>
        <div class="stat-info">
            <h4 data-translate="Patients (Today)">Patients (Today)</h4>
            <div class="stat-value"><?php echo number_format(num: $totalPatients); ?></div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon orange">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="stat-info">
            <h4 data-translate="Pending Tests">Pending Tests</h4>
            <div class="stat-value"><?php echo number_format(num: $pendingTests); ?></div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <h4 data-translate="Completed Tests (Today)">Completed Tests (Today)</h4>
            <div class="stat-value"><?php echo number_format($completedToday); ?></div>
        </div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon purple">
            <i class="fas fa-money-bill"></i>
        </div>
        <div class="stat-info">
            <h4 data-translate="Revenue (Today)">Revenue (Today)</h4>
            <div class="stat-value"><?php echo number_format(num: $revenue, decimals: 0); ?> IQD</div>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-flask" style="color:var(--accent-primary);margin-right:8px;"></i><span data-translate="Recent Tests">Recent Tests</span></h3>
            <a href="tests.php" class="btn btn-sm btn-secondary" data-translate="View All">View All</a>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th data-translate="Patient">Patient</th>
                        <th data-translate="Test">Test</th>
                        <th data-translate="Status">Status</th>
                        <th data-translate="Date">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent)): ?>
                        <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:30px;" data-translate="No tests yet">No tests yet</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent as $test): ?>
                        <tr>
                            <td>
                                <strong style="color:var(--text-primary);"><?php echo htmlspecialchars(string: $test['patient_name']); ?></strong>
                                <br><small style="color:var(--text-muted);"><?php echo $test['pid']; ?></small>
                            </td>
                            <td><?php echo htmlspecialchars(string: $test['test_name']); ?></td>
                            <td><span class="badge badge-<?php echo $test['status']; ?>"><?php echo ucfirst(string: str_replace(search: '_', replace: ' ', subject: $test['status'])); ?></span></td>
                            <td style="color:var(--text-muted);font-size:13px;"><?php echo formatDateTime(datetime: $test['ordered_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clipboard-list" style="color:var(--warning);margin-right:8px;"></i><span data-translate="Pending Queue">Pending Queue</span></h3>
            <a href="results.php" class="btn btn-sm btn-secondary" data-translate="Enter Results">Enter Results</a>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th data-translate="Patient">Patient</th>
                        <th data-translate="Test">Test</th>
                        <th data-translate="Status">Status</th>
                        <th data-translate="Action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending)): ?>
                        <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:30px;" data-translate="No pending tests">No pending tests</td></tr>
                    <?php else: ?>
                        <?php foreach ($pending as $pr): ?>
                        <tr>
                            <td>
                                <strong style="color:var(--text-primary);"><?php echo htmlspecialchars(string: $pr['patient_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars(string: $pr['test_name']); ?></td>
                            <td><span class="badge badge-<?php echo $pr['status']; ?>"><?php echo ucfirst(string: str_replace(search: '_', replace: ' ', subject: $pr['status'])); ?></span></td>
                            <td>
                                <?php if (hasRole(roles: ['admin', 'technician'])): ?>
                                    <a href="results.php?patient_test_id=<?php echo $pr['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> <span data-translate="Enter">Enter</span>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
