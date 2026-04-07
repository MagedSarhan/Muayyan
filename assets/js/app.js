/**
 * MOEEN  - Core JavaScript
 */

// Sidebar Toggle
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}

// Notification Toggle
function toggleNotifications() {
    const dropdown = document.getElementById('notifDropdown');
    dropdown.classList.toggle('show');
}

// Close notification dropdown on outside click
document.addEventListener('click', function (e) {
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    if (notifBtn && notifDropdown && !notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
        notifDropdown.classList.remove('show');
    }
});

// Mark All Notifications Read
function markAllRead() {
    fetch(getBaseUrl() + '/api/notifications.php?action=mark_all_read', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            document.querySelectorAll('.notification-item.unread').forEach(el => el.classList.remove('unread'));
            const badge = document.querySelector('.notification-bell .badge');
            if (badge) badge.remove();
        })
        .catch(err => console.error(err));
}

// Get base URL
function getBaseUrl() {
    return '/MOEEN ';
}

// Animated Counter
function animateCounters() {
    document.querySelectorAll('.stat-value[data-count]').forEach(el => {
        const target = parseFloat(el.dataset.count);
        const decimals = el.dataset.decimals ? parseInt(el.dataset.decimals) : 0;
        const duration = 1500;
        const start = 0;
        const startTime = performance.now();

        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
            const value = start + (target - start) * eased;
            el.textContent = value.toFixed(decimals);
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    });
}

// Flash message auto-dismiss
document.addEventListener('DOMContentLoaded', function () {
    // Animate counters
    animateCounters();

    // Auto-dismiss alerts
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    // Animate elements on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.stat-card, .card, .course-card').forEach(el => {
        observer.observe(el);
    });
});

// Confirm Delete
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Toast notification
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    container.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '1060';
    document.body.appendChild(container);
    return container;
}

// Print report
function printReport(elementId) {
    const content = document.getElementById(elementId);
    if (!content) return;
    const win = window.open('', '_blank');
    win.document.write(`
        <html><head><title>MOEEN  Report</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>body{padding:20px;font-family:Inter,sans-serif} @media print{.no-print{display:none}}</style>
        </head><body>${content.innerHTML}
        <script>setTimeout(()=>window.print(),500)</script>
        </body></html>
    `);
    win.document.close();
}

// Chart default options
const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { font: { family: 'Inter', size: 12 }, usePointStyle: true, padding: 15 } }
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 } } },
        y: { grid: { color: '#f0f0f0' }, ticks: { font: { family: 'Inter', size: 11 } } }
    }
};
