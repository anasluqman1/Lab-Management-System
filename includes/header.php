<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$notifCount = getUnreadNotificationCount($pdo, getUserId());
$cacheBust = '?v=' . time();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/main.css<?php echo $cacheBust; ?>">
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="css/<?php echo $extraCss . $cacheBust; ?>">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/lang.css<?php echo $cacheBust; ?>">
    <script>
        (function () {
            var saved = localStorage.getItem('labTheme') || 'dark';
            document.documentElement.setAttribute('data-theme', saved);
            var lang = localStorage.getItem('labLang') || 'en';
            document.documentElement.setAttribute('data-lang', lang);
            document.documentElement.setAttribute('dir', lang === 'ku' ? 'rtl' : 'ltr');
            document.documentElement.setAttribute('lang', lang === 'ku' ? 'ku' : 'en');
        })();
    </script>
</head>

<body>
    <div class="app-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-microscope" onclick="location.reload()"></i>
                </div>
                <div class="sidebar-brand">
                    <h2 onclick="location.reload()"><?php echo APP_NAME; ?></h2>
                    <span data-translate="Lab Management">Lab Management</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title" data-translate="Main">Main</div>
                    <a href="dashboard.php"
                        class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-th-large"></i> <span data-translate="Dashboard">Dashboard</span>
                    </a>
                    <?php if (hasRole(['admin', 'technician'])): ?>
                        <a href="patients.php" class="nav-item <?php echo $currentPage === 'patients' ? 'active' : ''; ?>">
                            <i class="fas fa-user-injured"></i> <span data-translate="Patients">Patients</span>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title" data-translate="Laboratory">Laboratory</div>
                    <?php if (hasRole(['admin', 'technician'])): ?>
                        <a href="tests.php" class="nav-item <?php echo $currentPage === 'tests' ? 'active' : ''; ?>">
                            <i class="fas fa-flask"></i> <span data-translate="Tests">Tests</span>
                        </a>
                        <a href="results.php" class="nav-item <?php echo $currentPage === 'results' ? 'active' : ''; ?>">
                            <i class="fas fa-file-medical-alt"></i> <span data-translate="Results">Results</span>
                        </a>
                    <?php endif; ?>
                    <a href="reports.php" class="nav-item <?php echo $currentPage === 'reports' ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice"></i> <span data-translate="Reports">Reports</span>
                    </a>
                </div>

                <?php if (hasRole(['admin', 'technician'])): ?>
                    <div class="nav-section">
                        <div class="nav-section-title" data-translate="Insights">Insights</div>
                        <a href="analytics.php"
                            class="nav-item <?php echo $currentPage === 'analytics' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i> <span data-translate="Analytics">Analytics</span>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (hasRole('admin')): ?>
                    <div class="nav-section">
                        <div class="nav-section-title" data-translate="Administration">Administration</div>
                        <a href="users.php" class="nav-item <?php echo $currentPage === 'users' ? 'active' : ''; ?>">
                            <i class="fas fa-users-cog"></i> <span data-translate="User Management">User Management</span>
                        </a>
                    </div>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar <?php echo getUserRole(); ?>-avatar">
                        <?php echo strtoupper(substr(getUserName(), 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo getUserName(); ?></div>
                        <div class="user-role"><?php echo ucfirst(getUserRole()); ?></div>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title">
                        <h2 data-translate="<?php echo $pageTitle ?? 'Dashboard'; ?>"><?php echo $pageTitle ?? 'Dashboard'; ?></h2>
                        <?php if (isset($pageSubtitle)): ?>
                            <span><?php echo $pageSubtitle; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="header-right">
                    <div class="header-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="globalSearch" data-translate="Search patients..." placeholder="Search patients..." autocomplete="off"
                            oninput="performSearch(this.value)">
                        <div class="search-results" id="searchResults"></div>
                    </div>

                    <div class="theme-switch" onclick="toggleTheme()" title="Switch theme">
                        <div class="theme-switch-track">
                            <i class="fas fa-moon theme-icon-moon"></i>
                            <i class="fas fa-sun theme-icon-sun"></i>
                            <div class="theme-switch-thumb"></div>
                        </div>
                    </div>

                    <!-- Language Switcher -->
                    <div class="lang-switch" style="position:relative;">
                        <button class="lang-switch-btn" onclick="toggleLangDropdown(event)" title="Language">
                            <i class="fas fa-globe"></i>
                            <span id="langBtnText">EN</span>
                        </button>
                        <div class="lang-dropdown" id="langDropdown">
                            <div class="lang-option" data-lang="en" onclick="setLang('en'); this.parentElement.classList.remove('show');">
                                <span class="lang-flag">🇬🇧</span>
                                <span>English</span>
                            </div>
                            <div class="lang-option" data-lang="ku" onclick="setLang('ku'); this.parentElement.classList.remove('show');">
                                <span class="lang-flag">🇮🇶</span>
                                <span>کوردی سۆرانی</span>
                            </div>
                        </div>
                    </div>

                    <div style="position:relative;">
                        <button class="notification-btn" onclick="toggleNotifications()">
                            <i class="fas fa-bell"></i>
                            <?php if ($notifCount > 0): ?>
                                <span class="notification-badge"
                                    id="notifBadge"><?php echo $notifCount > 9 ? '9+' : $notifCount; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="notification-dropdown" id="notifDropdown">
                            <div class="notification-header">
                                <h4 data-translate="Notifications">Notifications</h4>
                                <a href="#" onclick="markAllRead();return false;" data-translate="Mark all read">Mark all read</a>
                                &nbsp;|&nbsp;
                                <a href="#" onclick="clearAllNotifications();return false;" style="color:#e74c3c;" data-translate="Clear all">Clear
                                    all</a>
                            </div>
                            <div class="notification-list" id="notifList">
                                <div class="empty-state" style="padding:30px;">
                                    <i class="fas fa-bell-slash"></i>
                                    <p data-translate="No notifications">No notifications</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="logout.php" class="btn btn-sm btn-secondary" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </header>

            <div class="content-area">