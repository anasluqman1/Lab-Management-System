<?php
/**
 * Analytics Dashboard - Lightweight CSS-only charts and statistics
 */
$pageTitle = 'Analytics';
$pageSubtitle = 'Laboratory performance and revenue insights';
$extraCss = 'analytics.css';
require_once __DIR__ . '/auth.php';
requireRole(roles: ['admin', 'technician']);

// Whitelist period input and compute date boundaries in PHP (not SQL)
$allowedPeriods = ['today', 'week', 'month', 'year', 'all'];
$period = $_GET['period'] ?? 'month';
if (!in_array($period, $allowedPeriods)) {
    $period = 'month';
}

// Friday-based week start
$dayOfWeek = (int) date('N'); // 1=Mon ... 5=Fri ... 7=Sun
$daysSinceFriday = ($dayOfWeek >= 5) ? $dayOfWeek - 5 : $dayOfWeek + 2;

$periodDates = [
    'today' => date('Y-m-d'),
    'week' => date('Y-m-d', strtotime("-{$daysSinceFriday} days")),
    'month' => date('Y-m-01'),
    'year' => date('Y-01-01'),
    'all' => '2000-01-01'
];
$periodLabelsMap = [
    'today' => 'Today',
    'week' => 'This Week',
    'month' => 'This Month',
    'year' => 'This Year',
    'all' => 'All Time'
];
$periodIcons = [
    'today' => 'fa-clock',
    'week' => 'fa-calendar-day',
    'month' => 'fa-calendar-week',
    'year' => 'fa-calendar-alt',
    'all' => 'fa-infinity'
];

$periodLabel = $periodLabelsMap[$period];
$dateFilter = $periodDates[$period];

// Multi-period summary stats using prepared statements
$stmtTests = $pdo->prepare("
    SELECT 
        COUNT(*) as total_tests,
        SUM(CASE WHEN pt.status = 'completed' THEN 1 ELSE 0 END) as completed_tests,
        COALESCE(SUM(tc.price), 0) as total_revenue
    FROM patient_tests pt
    JOIN test_catalog tc ON pt.test_id = tc.id
    WHERE pt.ordered_at >= ?
");
$stmtPatients = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE created_at >= ?");

$multiStats = [];
foreach ($periodDates as $key => $dateVal) {
    $stmtTests->execute([$dateVal]);
    $row = $stmtTests->fetch();
    $stmtPatients->execute([$dateVal]);
    $row['new_patients'] = $stmtPatients->fetchColumn();
    $multiStats[$key] = $row;
}

$totalTests = $multiStats[$period]['total_tests'] ?? 0;
$completedTests = $multiStats[$period]['completed_tests'] ?? 0;
$totalRevenue = $multiStats[$period]['total_revenue'] ?? 0;
$newPatients = $multiStats[$period]['new_patients'] ?? 0;

// Charts data — all using prepared statements
$stmtCat = $pdo->prepare("
    SELECT tc.category, COUNT(*) as cnt 
    FROM patient_tests pt 
    JOIN test_catalog tc ON pt.test_id = tc.id 
    WHERE pt.ordered_at >= ? 
    GROUP BY tc.category 
    ORDER BY cnt DESC
");
$stmtCat->execute([$dateFilter]);
$byCat = $stmtCat->fetchAll();

$stmtTop = $pdo->prepare("
    SELECT tc.test_name, COUNT(*) as cnt, tc.price, SUM(tc.price) as revenue
    FROM patient_tests pt 
    JOIN test_catalog tc ON pt.test_id = tc.id 
    WHERE pt.ordered_at >= ? 
    GROUP BY tc.id 
    ORDER BY cnt DESC 
    LIMIT 10
");
$stmtTop->execute([$dateFilter]);
$topTests = $stmtTop->fetchAll();

$stmtDaily = $pdo->prepare("
    SELECT DATE(ordered_at) as day, COUNT(*) as cnt 
    FROM patient_tests 
    WHERE ordered_at >= ? 
    GROUP BY DATE(ordered_at) 
    ORDER BY day DESC
    LIMIT 14
");
$stmtDaily->execute([$dateFilter]);
$dailyTests = $stmtDaily->fetchAll();
$dailyTests = array_reverse($dailyTests);
$maxDaily = max(array_column($dailyTests, 'cnt') ?: [1]);

$maxCat = !empty($byCat) ? max(array_column($byCat, 'cnt')) : 1;

$catColors = ['#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899', '#10b981', '#f59e0b', '#ef4444', '#14b8a6', '#6366f1', '#f43f5e'];

$stmtDoc = $pdo->prepare("
    SELECT pt.referred_doctor, COUNT(*) as cnt, COALESCE(SUM(tc.price), 0) as revenue,
           GROUP_CONCAT(DISTINCT tc.test_name ORDER BY tc.test_name SEPARATOR ', ') as test_names
    FROM patient_tests pt
    JOIN test_catalog tc ON pt.test_id = tc.id
    WHERE pt.referred_doctor IS NOT NULL AND pt.referred_doctor != '' AND pt.ordered_at >= ?
    GROUP BY pt.referred_doctor
    ORDER BY cnt DESC
");
$stmtDoc->execute([$dateFilter]);
$testsByDoctor = $stmtDoc->fetchAll();
$docColors = ['#0d9488', '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899', '#ef4444', '#10b981', '#06b6d4', '#6366f1', '#f43f5e'];

include __DIR__ . '/includes/header.php';
?>

<!-- Multi-Period Summary Table -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h3><i class="fas fa-chart-line" style="color:var(--accent-primary);margin-right:8px;"></i>Performance Overview
        </h3>
    </div>
    <div class="table-container">
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Period</th>
                    <th><i class="fas fa-user-plus" style="color:#f59e0b;margin-right:4px;"></i>Patients</th>
                    <th><i class="fas fa-flask" style="color:#06b6d4;margin-right:4px;"></i>Tests</th>
                    <th><i class="fas fa-check-circle" style="color:#10b981;margin-right:4px;"></i>Completed</th>
                    <th><i class="fas fa-money-bill" style="color:#8b5cf6;margin-right:4px;"></i>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($multiStats as $key => $s): ?>
                    <tr class="<?php echo $key === $period ? 'active-period-row' : ''; ?>">
                        <td>
                            <a href="?period=<?php echo $key; ?>"
                                class="period-link <?php echo $key === $period ? 'active' : ''; ?>">
                                <i class="fas <?php echo $periodIcons[$key]; ?>"
                                    style="margin-right:6px;"></i><?php echo $periodLabelsMap[$key]; ?>
                            </a>
                        </td>
                        <td><strong><?php echo number_format($s['new_patients']); ?></strong></td>
                        <td><strong><?php echo number_format($s['total_tests']); ?></strong></td>
                        <td><strong><?php echo number_format($s['completed_tests']); ?></strong></td>
                        <td style="color:var(--success);font-weight:700;"><?php echo number_format($s['total_revenue']); ?>
                            IQD</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:24px;">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-bar" style="color:var(--accent-primary);margin-right:8px;"></i>Tests Over Time
                (<?php echo $periodLabel; ?>)</h3>
        </div>
        <div class="css-bar-chart">
            <?php if (empty($dailyTests)): ?>
                <div class="empty-state" style="padding:40px 0;"><i class="fas fa-chart-bar"></i>
                    <h4>No Data</h4>
                    <p>No tests in this period.</p>
                </div>
            <?php else: ?>
                <?php foreach ($dailyTests as $dt):
                    $pct = $maxDaily > 0 ? round(num: ($dt['cnt'] / $maxDaily) * 100) : 0;
                    ?>
                    <div class="bar-row">
                        <span
                            class="bar-label"><?php echo date(format: 'M d', timestamp: strtotime(datetime: $dt['day'])); ?></span>
                        <div class="bar-track">
                            <div class="bar-fill" style="width:<?php echo $pct; ?>%;">
                                <span class="bar-value"><?php echo $dt['cnt']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie" style="color:var(--info);margin-right:8px;"></i>By Category</h3>
        </div>
        <div class="css-category-chart">
            <?php if (empty($byCat)): ?>
                <div class="empty-state" style="padding:40px 0;"><i class="fas fa-chart-pie"></i>
                    <h4>No Data</h4>
                </div>
            <?php else: ?>
                <?php foreach ($byCat as $i => $cat):
                    $catPct = $maxCat > 0 ? round(num: ($cat['cnt'] / $maxCat) * 100) : 0;
                    $color = $catColors[$i % count(value: $catColors)];
                    ?>
                    <div class="cat-row">
                        <div class="cat-header">
                            <span class="cat-dot" style="background:<?php echo $color; ?>;"></span>
                            <span class="cat-name"><?php echo htmlspecialchars($cat['category']); ?></span>
                            <span class="cat-count"><?php echo $cat['cnt']; ?></span>
                        </div>
                        <div class="cat-track">
                            <div class="cat-fill" style="width:<?php echo $catPct; ?>%;background:<?php echo $color; ?>;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-trophy" style="color:var(--warning);margin-right:8px;"></i>Most Popular Tests
            (<?php echo $periodLabel; ?>)</h3>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Test Name</th>
                    <th>Count</th>
                    <th>Unit Price</th>
                    <th>Revenue</th>
                    <th>Share</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topTests as $i => $pt): ?>
                    <tr>
                        <td style="font-weight:700;color:var(--accent-primary);"><?php echo $i + 1; ?></td>
                        <td style="color:var(--text-primary);font-weight:500;">
                            <?php echo htmlspecialchars(string: $pt['test_name']); ?>
                        </td>
                        <td><strong><?php echo $pt['cnt']; ?></strong></td>
                        <td style="color:var(--text-muted);"><?php echo number_format(num: $pt['price'], ); ?> IQD</td>

                        <td style="color:var(--success);font-weight:600;">
                            <?php echo number_format(num: $pt['revenue'], ); ?> IQD

                        </td>
                        <td>
                            <div
                                style="background:rgba(6,182,212,0.1);height:6px;border-radius:3px;overflow:hidden;width:100px;">
                                <div
                                    style="background:var(--accent-primary);height:100%;width:<?php echo $totalTests > 0 ? round(num: ($pt['cnt'] / $totalTests) * 100) : 0; ?>%;border-radius:3px;">
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-user-md" style="color:var(--accent-primary);margin-right:8px;"></i>Tests by Referred Doctor
            (<?php echo $periodLabel; ?>)</h3>
    </div>
    <?php if (empty($testsByDoctor)): ?>
        <div class="empty-state" style="padding:40px 0;"><i class="fas fa-user-md"></i>
            <h4>No Data Yet</h4>
            <p>Referred doctor names are saved when reports are generated.</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Doctor Name</th>
                        <th>Tests</th>
                        <th>Revenue</th>
                        <th>Test Names</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($testsByDoctor as $i => $doc): ?>
                        <tr>
                            <td style="font-weight:700;color:<?php echo $docColors[$i % count(value: $docColors)]; ?>;">
                                <?php echo $i + 1; ?>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div
                                        style="width:34px;height:34px;border-radius:8px;background:<?php echo $docColors[$i % count(value: $docColors)]; ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;flex-shrink:0;">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                    <strong
                                        style="color:var(--text-primary);"><?php echo htmlspecialchars(string: $doc['referred_doctor']); ?></strong>
                                </div>
                            </td>
                            <td><strong><?php echo $doc['cnt']; ?></strong></td>
                            <td style="color:var(--success);font-weight:600;">
                                <?php echo number_format(num: $doc['revenue'], ); ?> IQD
                            </td>
                            <td style="font-size:12px;color:var(--text-muted);max-width:300px;">
                                <?php echo htmlspecialchars(string: $doc['test_names']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>