/**
 * Language System - English / Kurdish Sorani
 * Stores all translations and handles switching
 */

const translations = {
    en: {
        // Sidebar
        'Lab Management': 'Lab Management',
        'Main': 'Main',
        'Dashboard': 'Dashboard',
        'Patients': 'Patients',
        'Laboratory': 'Laboratory',
        'Tests': 'Tests',
        'Results': 'Results',
        'Reports': 'Reports',
        'Insights': 'Insights',
        'Analytics': 'Analytics',
        'Administration': 'Administration',
        'User Management': 'User Management',

        // Header
        'Search patients...': 'Search patients...',
        'Switch theme': 'Switch theme',
        'Notifications': 'Notifications',
        'Mark all read': 'Mark all read',
        'Clear all': 'Clear all',
        'No notifications': 'No notifications',
        'Logout': 'Logout',

        // Dashboard
        'Welcome back, ': 'Welcome back, ',
        'Patients (Today)': 'Patients (Today)',
        'Pending Tests': 'Pending Tests',
        'Completed Tests (Today)': 'Completed Tests (Today)',
        'Revenue (Today)': 'Revenue (Today)',
        'Recent Tests': 'Recent Tests',
        'View All': 'View All',
        'Patient': 'Patient',
        'Test': 'Test',
        'Status': 'Status',
        'Date': 'Date',
        'No tests yet': 'No tests yet',
        'Pending Queue': 'Pending Queue',
        'Enter Results': 'Enter Results',
        'Action': 'Action',
        'No pending tests': 'No pending tests',
        'Enter': 'Enter',

        // Patients page
        'Manage patient records': 'Manage patient records',
        'Search patient name or ID...': 'Search patient name or ID...',
        'Register Patient': 'Register Patient',
        'Patient ID': 'Patient ID',
        'Full Name': 'Full Name',
        'Age': 'Age',
        'Gender': 'Gender',
        'Blood Group': 'Blood Group',
        'Phone': 'Phone',
        'Email': 'Email',
        'Registered': 'Registered',
        'Actions': 'Actions',
        'No patients found': 'No patients found',
        'Register New Patient': 'Register New Patient',
        'Date of Birth': 'Date of Birth',
        'Select Gender': 'Select Gender',
        'Male': 'Male',
        'Female': 'Female',
        'Other': 'Other',
        'Select Blood Group': 'Select Blood Group',
        'Phone number': 'Phone number',
        'Email address': 'Email address',
        'Patient address': 'Patient address',
        'Address': 'Address',
        'Cancel': 'Cancel',
        'Edit Patient': 'Edit Patient',
        'Update Patient': 'Update Patient',
        'Enter full name': 'Enter full name',
        'Delete this patient?': 'Delete this patient?',

        // Tests page
        'Manage tests': 'Manage tests',
        'Assign Tests': 'Assign Tests',
        'Test Name': 'Test Name',
        'Category': 'Category',
        'Price': 'Price',
        'Select All': 'Select All',
        'Assign Selected Tests': 'Assign Selected Tests',
        'Referred Doctor': 'Referred Doctor',
        'Doctor name (optional)': 'Doctor name (optional)',
        'Ordered By': 'Ordered By',
        'Ordered At': 'Ordered At',

        // Results page
        'Enter test results': 'Enter test results',
        'Result': 'Result',
        'Normal Range': 'Normal Range',
        'Save Results': 'Save Results',
        'Save All Results': 'Save All Results',
        'Notes': 'Notes',
        'Normal': 'Normal',
        'Abnormal': 'Abnormal',
        'High': 'High',
        'Low': 'Low',

        // Reports page
        'View and share reports': 'View and share reports',
        'View Report': 'View Report',
        'Share': 'Share',
        'Print': 'Print',
        'Download PDF': 'Download PDF',

        // Users page
        'Manage system users and roles': 'Manage system users and roles',
        'users in system': 'users in system',
        'Add User': 'Add User',
        'User': 'User',
        'Username': 'Username',
        'Role': 'Role',
        'Created': 'Created',
        'Active': 'Active',
        'Inactive': 'Inactive',
        'Add New User': 'Add New User',
        'Password': 'Password',
        'Technician': 'Technician',
        'Doctor': 'Doctor',
        'Admin': 'Admin',
        'Create User': 'Create User',
        'Edit User': 'Edit User',
        'New Password': 'New Password',
        '(leave blank to keep current)': '(leave blank to keep current)',
        'Update User': 'Update User',
        'Delete this user?': 'Delete this user?',

        // Analytics
        'System analytics': 'System analytics',

        // Login page
        'Laboratory Information Management': 'Laboratory Information Management',
        'Sign In': 'Sign In',
        'Enter username': 'Enter username',
        'Enter password': 'Enter password',
        'Please enter both username and password.': 'Please enter both username and password.',
        'Invalid username or password.': 'Invalid username or password.',

        // General
        'pending': 'Pending',
        'in_progress': 'In Progress',
        'completed': 'Completed',
        'Save': 'Save',
        'Edit': 'Edit',
        'Delete': 'Delete',
        'Close': 'Close',
        'Confirm': 'Confirm',
        'Yes': 'Yes',
        'No': 'No',
        'Loading...': 'Loading...',
        'Error': 'Error',
        'Success': 'Success',
        'Warning': 'Warning',
        'Are you sure?': 'Are you sure?',

        // Language
        'English': 'English',
        'Kurdish': 'کوردی',
        'Language': 'Language'
    },

    ku: {
        // Sidebar
        'Lab Management': 'بەڕێوەبردنی تاقیگە',
        'Main': 'سەرەکی',
        'Dashboard': 'داشبۆرد',
        'Patients': 'نەخۆشەکان',
        'Laboratory': 'تاقیگە',
        'Tests': 'تاقیکردنەوەکان',
        'Results': 'ئەنجامەکان',
        'Reports': 'ڕاپۆرتەکان',
        'Insights': 'تێبینییەکان',
        'Analytics': 'شیکاری',
        'Administration': 'بەڕێوەبردن',
        'User Management': 'بەڕێوەبردنی بەکارهێنەران',

        // Header
        'Search patients...': 'گەڕان بۆ نەخۆش...',
        'Switch theme': 'گۆڕینی ڕووکار',
        'Notifications': 'ئاگادارییەکان',
        'Mark all read': 'هەموو وەک خوێندراو',
        'Clear all': 'سڕینەوەی هەموو',
        'No notifications': 'هیچ ئاگادارییەک نییە',
        'Logout': 'چوونەدەرەوە',

        // Dashboard
        'Welcome back, ': 'بەخێربێیتەوە، ',
        'Patients (Today)': 'نەخۆشەکان (ئەمڕۆ)',
        'Pending Tests': 'تاقیکردنەوەی چاوەڕوانکراو',
        'Completed Tests (Today)': 'تاقیکردنەوەی تەواوبوو (ئەمڕۆ)',
        'Revenue (Today)': 'داهات (ئەمڕۆ)',
        'Recent Tests': 'دوایین تاقیکردنەوەکان',
        'View All': 'بینینی هەموو',
        'Patient': 'نەخۆش',
        'Test': 'تاقیکردنەوە',
        'Status': 'بارودۆخ',
        'Date': 'بەروار',
        'No tests yet': 'هێشتا هیچ تاقیکردنەوەیەک نییە',
        'Pending Queue': 'ڕیزی چاوەڕوانکراو',
        'Enter Results': 'تۆمارکردنی ئەنجامەکان',
        'Action': 'کردار',
        'No pending tests': 'هیچ تاقیکردنەوەیەکی چاوەڕوانکراو نییە',
        'Enter': 'تۆمار',

        // Patients page
        'Manage patient records': 'بەڕێوەبردنی تۆماری نەخۆشەکان',
        'Search patient name or ID...': 'گەڕان بە ناو یان ناسنامەی نەخۆش...',
        'Register Patient': 'تۆمارکردنی نەخۆش',
        'Patient ID': 'ناسنامەی نەخۆش',
        'Full Name': 'ناوی تەواو',
        'Age': 'تەمەن',
        'Gender': 'ڕەگەز',
        'Blood Group': 'گرووپی خوێن',
        'Phone': 'ژمارەی مۆبایل',
        'Email': 'ئیمەیل',
        'Registered': 'تۆمارکراو',
        'Actions': 'کردارەکان',
        'No patients found': 'هیچ نەخۆشێک نەدۆزرایەوە',
        'Register New Patient': 'تۆمارکردنی نەخۆشی نوێ',
        'Date of Birth': 'بەرواری لەدایکبوون',
        'Select Gender': 'ڕەگەز هەڵبژێرە',
        'Male': 'نێر',
        'Female': 'مێ',
        'Other': 'ئەوانی تر',
        'Select Blood Group': 'گرووپی خوێن هەڵبژێرە',
        'Phone number': 'ژمارەی مۆبایل',
        'Email address': 'ناونیشانی ئیمەیل',
        'Patient address': 'ناونیشانی نەخۆش',
        'Address': 'ناونیشان',
        'Cancel': 'پاشگەزبوونەوە',
        'Edit Patient': 'دەستکاریکردنی نەخۆش',
        'Update Patient': 'نوێکردنەوەی نەخۆش',
        'Enter full name': 'ناوی تەواو بنووسە',
        'Delete this patient?': 'ئایا دەتەوێت ئەم نەخۆشە بسڕیتەوە؟',

        // Tests page
        'Manage tests': 'بەڕێوەبردنی تاقیکردنەوەکان',
        'Assign Tests': 'دیاریکردنی تاقیکردنەوە',
        'Test Name': 'ناوی تاقیکردنەوە',
        'Category': 'پۆل',
        'Price': 'نرخ',
        'Select All': 'هەڵبژاردنی هەموو',
        'Assign Selected Tests': 'دیاریکردنی تاقیکردنەوە هەڵبژێردراوەکان',
        'Referred Doctor': 'دکتۆری ناردراو',
        'Doctor name (optional)': 'ناوی دکتۆر (ئارەزوومەندانە)',
        'Ordered By': 'داواکراوە لەلایەن',
        'Ordered At': 'کاتی داواکردن',

        // Results page
        'Enter test results': 'تۆمارکردنی ئەنجامی تاقیکردنەوە',
        'Result': 'ئەنجام',
        'Normal Range': 'ڕێژەی ئاسایی',
        'Save Results': 'پاشەکەوتکردنی ئەنجامەکان',
        'Save All Results': 'پاشەکەوتکردنی هەموو ئەنجامەکان',
        'Notes': 'تێبینییەکان',
        'Normal': 'ئاسایی',
        'Abnormal': 'نائاسایی',
        'High': 'بەرز',
        'Low': 'نزم',

        // Reports page
        'View and share reports': 'بینین و هاوبەشکردنی ڕاپۆرتەکان',
        'View Report': 'بینینی ڕاپۆرت',
        'Share': 'هاوبەشکردن',
        'Print': 'چاپکردن',
        'Download PDF': 'داگرتنی PDF',

        // Users page
        'Manage system users and roles': 'بەڕێوەبردنی بەکارهێنەران و ڕۆڵەکان',
        'users in system': 'بەکارهێنەر لە سیستەم',
        'Add User': 'زیادکردنی بەکارهێنەر',
        'User': 'بەکارهێنەر',
        'Username': 'ناوی بەکارهێنەر',
        'Role': 'ڕۆڵ',
        'Created': 'دروستکراو',
        'Active': 'چالاک',
        'Inactive': 'ناچالاک',
        'Add New User': 'زیادکردنی بەکارهێنەری نوێ',
        'Password': 'وشەی نهێنی',
        'Technician': 'تەکنیشن',
        'Doctor': 'دکتۆر',
        'Admin': 'بەڕێوەبەر',
        'Create User': 'دروستکردنی بەکارهێنەر',
        'Edit User': 'دەستکاریکردنی بەکارهێنەر',
        'New Password': 'وشەی نهێنی نوێ',
        '(leave blank to keep current)': '(بەتاڵ بهێڵەرەوە بۆ مانەوەی ئێستا)',
        'Update User': 'نوێکردنەوەی بەکارهێنەر',
        'Delete this user?': 'ئایا دەتەوێت ئەم بەکارهێنەرە بسڕیتەوە؟',

        // Analytics
        'System analytics': 'شیکاری سیستەم',

        // Login page
        'Laboratory Information Management': 'بەڕێوەبردنی زانیاری تاقیگە',
        'Sign In': 'چوونەژوورەوە',
        'Enter username': 'ناوی بەکارهێنەر بنووسە',
        'Enter password': 'وشەی نهێنی بنووسە',
        'Please enter both username and password.': 'تکایە ناوی بەکارهێنەر و وشەی نهێنی بنووسە.',
        'Invalid username or password.': 'ناوی بەکارهێنەر یان وشەی نهێنی هەڵەیە.',

        // General
        'pending': 'چاوەڕوانکراو',
        'in_progress': 'لە پرۆسەدا',
        'completed': 'تەواوبوو',
        'Save': 'پاشەکەوتکردن',
        'Edit': 'دەستکاری',
        'Delete': 'سڕینەوە',
        'Close': 'داخستن',
        'Confirm': 'دڵنیاکردنەوە',
        'Yes': 'بەڵێ',
        'No': 'نەخێر',
        'Loading...': 'چاوەڕوانبە...',
        'Error': 'هەڵە',
        'Success': 'سەرکەوتوو',
        'Warning': 'ئاگاداری',
        'Are you sure?': 'دڵنیایت؟',

        // Language
        'English': 'English',
        'Kurdish': 'کوردی',
        'Language': 'زمان'
    }
};

// ============================================================
// LANGUAGE SWITCHING
// ============================================================
function getLang() {
    return localStorage.getItem('labLang') || 'en';
}

function setLang(lang) {
    localStorage.setItem('labLang', lang);
    applyLang(lang);
}

function toggleLangDropdown(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('langDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

function applyLang(lang) {
    const t = translations[lang] || translations.en;
    const dir = lang === 'ku' ? 'rtl' : 'ltr';

    document.documentElement.setAttribute('lang', lang === 'ku' ? 'ku' : 'en');
    document.documentElement.setAttribute('dir', dir);
    document.documentElement.setAttribute('data-lang', lang);

    // Update all elements with data-translate attribute
    document.querySelectorAll('[data-translate]').forEach(el => {
        const key = el.getAttribute('data-translate');
        if (t[key] !== undefined) {
            // Handle inputs (placeholder)
            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                el.placeholder = t[key];
            } else {
                // Preserve icons - only replace text nodes
                const icon = el.querySelector('i, .fas, .fab, .far');
                if (icon) {
                    // Element has an icon, replace only text part
                    const nodes = el.childNodes;
                    let replaced = false;
                    for (let i = 0; i < nodes.length; i++) {
                        if (nodes[i].nodeType === 3 && nodes[i].textContent.trim()) {
                            nodes[i].textContent = ' ' + t[key];
                            replaced = true;
                            break;
                        }
                    }
                    if (!replaced) {
                        // Add text after the icon
                        el.appendChild(document.createTextNode(' ' + t[key]));
                    }
                } else {
                    el.textContent = t[key];
                }
            }
        }
    });

    // Update the lang button text
    const langBtnText = document.getElementById('langBtnText');
    if (langBtnText) {
        langBtnText.textContent = lang === 'ku' ? 'کوردی' : 'EN';
    }

    // Highlight active language in dropdown
    document.querySelectorAll('.lang-option').forEach(opt => {
        opt.classList.toggle('active', opt.getAttribute('data-lang') === lang);
    });
}

// Close lang dropdown when clicking outside
document.addEventListener('click', function (e) {
    const langDropdown = document.getElementById('langDropdown');
    const langBtn = document.querySelector('.lang-switch-btn');
    if (langDropdown && langBtn && !langBtn.contains(e.target) && !langDropdown.contains(e.target)) {
        langDropdown.classList.remove('show');
    }
});

// Apply language on page load
document.addEventListener('DOMContentLoaded', function () {
    applyLang(getLang());
});
