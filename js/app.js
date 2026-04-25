/**
 * App.js - Main JavaScript for MedLab Pro
 * Handles: search, notifications, modals, toasts, sidebar toggle, theme
 */

// ============================================================
// THEME TOGGLE (Dark / Light)
// ============================================================
function toggleTheme() {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme') || 'dark';
    const next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('labTheme', next);
}

// ============================================================
// MODAL MANAGEMENT
// ============================================================
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// Close modal on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('show');
        document.body.style.overflow = '';
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.show').forEach(m => {
            m.classList.remove('show');
        });
        document.body.style.overflow = '';
    }
});

// ============================================================
// SIDEBAR TOGGLE (Mobile)
// ============================================================
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

// ============================================================
// TOAST AUTO-HIDE
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const toast = document.getElementById('toast');
    if (toast) {
        setTimeout(() => {
            toast.style.animation = 'toastOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
});

// Toast slide-out animation
const toastOutStyle = document.createElement('style');
toastOutStyle.textContent = `
    @keyframes toastOut {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(100px); }
    }
`;
document.head.appendChild(toastOutStyle);

// ============================================================
// GLOBAL SEARCH
// ============================================================
let searchTimeout = null;

function performSearch(query) {
    const results = document.getElementById('searchResults');
    if (!results) return;

    clearTimeout(searchTimeout);

    if (query.length < 2) {
        results.classList.remove('show');
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch('search.php?q=' + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
                if (data.length === 0) {
                    results.innerHTML = '<div style="padding:16px;text-align:center;color:#64748b;font-size:13px;">No patients found</div>';
                } else {
                    results.innerHTML = data.map(p => `
                        <a href="patients.php?search=${encodeURIComponent(p.full_name)}" class="search-result-item">
                            <div style="width:36px;height:36px;border-radius:8px;background:rgba(6,182,212,0.1);display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-user" style="color:#06b6d4;font-size:14px;"></i>
                            </div>
                            <div>
                                <div class="result-name">${p.full_name}</div>
                                <div class="result-id">${p.patient_id} · ${p.gender} · ${p.age} yrs</div>
                            </div>
                        </a>
                    `).join('');
                }
                results.classList.add('show');
            })
            .catch(err => {
                results.classList.remove('show');
            });
    }, 300);
}

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    const searchResults = document.getElementById('searchResults');
    const searchInput = document.getElementById('globalSearch');
    if (searchResults && searchInput && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.classList.remove('show');
    }
});

// ============================================================
// NOTIFICATIONS
// ============================================================
function toggleNotifications() {
    const dropdown = document.getElementById('notifDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
        if (dropdown.classList.contains('show')) {
            loadNotifications();
        }
    }
}

function loadNotifications() {
    const list = document.getElementById('notifList');
    if (!list) return;

    fetch('notifications.php')
        .then(res => res.json())
        .then(data => {
            const badge = document.getElementById('notifBadge');
            
            if (data.notifications.length === 0) {
                list.innerHTML = '<div style="padding:30px;text-align:center;color:#64748b;"><i class="fas fa-bell-slash" style="font-size:24px;display:block;margin-bottom:8px;opacity:0.5;"></i>No notifications</div>';
            } else {
                list.innerHTML = data.notifications.map(n => {
                    const iconClass = n.type === 'result' ? 'result' : n.type === 'test' ? 'test' : 'alert';
                    const icon = n.type === 'result' ? 'fa-check-circle' : n.type === 'test' ? 'fa-flask' : 'fa-exclamation-circle';
                    const time = timeAgo(n.created_at);
                    return `
                        <div class="notification-item ${n.is_read == 0 ? 'unread' : ''}" onclick="${n.link ? `window.location='${n.link}'` : ''}">
                            <div class="notification-icon ${iconClass}">
                                <i class="fas ${icon}"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notif-title">${n.title}</div>
                                <div class="notif-time">${n.message || ''} · ${time}</div>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            if (badge) {
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(err => console.error('Failed to load notifications:', err));
}

function markAllRead() {
    fetch('notifications.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'mark_all_read=1'
    }).then(() => {
        loadNotifications();
        const badge = document.getElementById('notifBadge');
        if (badge) badge.style.display = 'none';
    });
}

function clearAllNotifications() {
    if (!confirm('Are you sure you want to clear all notifications? This cannot be undone.')) {
        return;
    }
    fetch('notifications.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'clear_all=1'
    }).then(() => {
        loadNotifications();
        const badge = document.getElementById('notifBadge');
        if (badge) badge.style.display = 'none';
    });
}

// Close notification dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('notifDropdown');
    const btn = document.querySelector('.notification-btn');
    if (dropdown && btn && !btn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

// ============================================================
// UTILITY FUNCTIONS
// ============================================================
function timeAgo(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return 'just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + 'd ago';
    return date.toLocaleDateString();
}
