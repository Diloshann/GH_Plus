// js/main.js — GH+ Frontend Logic

// ============================================================
// Password toggle
// ============================================================
document.querySelectorAll('.pw-toggle').forEach(btn => {
    btn.addEventListener('click', function () {
        const input = this.previousElementSibling;
        if (input && input.type === 'password') {
            input.type = 'text';
            this.textContent = '🙈';
        } else if (input) {
            input.type = 'password';
            this.textContent = '👁';
        }
    });
});

// ============================================================
// NIC auto-format & validation
// ============================================================
const nicInput = document.getElementById('nic');
if (nicInput) {
    nicInput.addEventListener('input', function () {
        let v = this.value.toUpperCase().replace(/[^0-9V]/g, '');
        this.value = v;
        const old = /^\d{9}V?$/.test(v);
        const newNic = /^\d{12}$/.test(v);
        const msg = document.getElementById('nic-msg');
        if (msg) {
            if (v.length === 0) { msg.textContent = ''; return; }
            if (old || newNic) {
                msg.textContent = '✔ Valid NIC format';
                msg.style.color = 'green';
            } else {
                msg.textContent = 'Enter 9-digit+V or 12-digit NIC';
                msg.style.color = '#C8102E';
            }
        }
    });
}

// ============================================================
// Tabs
// ============================================================
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const group = this.dataset.group || 'default';
        document.querySelectorAll(`.tab-btn[data-group="${group}"]`).forEach(b => b.classList.remove('active'));
        document.querySelectorAll(`.tab-content[data-group="${group}"]`).forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        const target = document.getElementById(this.dataset.tab);
        if (target) target.classList.add('active');
    });
});

// ============================================================
// Modals
// ============================================================
function openModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.add('open');
}

function closeModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.remove('open');
}

document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => openModal(btn.dataset.modalOpen));
});

document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => closeModal(btn.dataset.modalClose));
});

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
        if (e.target === this) this.classList.remove('open');
    });
});

// ============================================================
// Confirm delete
// ============================================================
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
        if (!confirm(this.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
});

// ============================================================
// File upload preview
// ============================================================
const fileInput = document.getElementById('upload_files');
const filePreview = document.getElementById('file-preview');
if (fileInput && filePreview) {
    fileInput.addEventListener('change', function () {
        filePreview.innerHTML = '';
        Array.from(this.files).forEach(f => {
            const div = document.createElement('div');
            div.style.cssText = 'display:inline-block;margin:4px;padding:4px 10px;background:#f0f0f0;border-radius:4px;font-size:12px;';
            div.textContent = f.name + ' (' + (f.size / 1024).toFixed(1) + ' KB)';
            filePreview.appendChild(div);
        });
    });
}

// ============================================================
// Profile photo preview
// ============================================================
const photoInput = document.getElementById('profile_photo');
const photoPreview = document.getElementById('photo-preview');
if (photoInput && photoPreview) {
    photoInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => { photoPreview.src = e.target.result; };
            reader.readAsDataURL(file);
        }
    });
}

// ============================================================
// Auto-dismiss alerts after 4 seconds
// ============================================================
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    }, 4000);
});

// ============================================================
// Sidebar toggle (mobile) with overlay
// ============================================================
const sidebarToggle = document.getElementById('sidebar-toggle');
const sidebar       = document.querySelector('.sidebar');

// Create overlay element dynamically
let overlay = document.querySelector('.sidebar-overlay');
if (!overlay) {
    overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
}

function openSidebar()  { sidebar && sidebar.classList.add('open');    overlay.classList.add('open'); }
function closeSidebar() { sidebar && sidebar.classList.remove('open'); overlay.classList.remove('open'); }

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function () {
        sidebar && sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
}
overlay.addEventListener('click', closeSidebar);

// Close sidebar on nav link click (mobile)
document.querySelectorAll('.sidebar-nav a').forEach(link => {
    link.addEventListener('click', function () {
        if (window.innerWidth <= 768) closeSidebar();
    });
});

// ============================================================
// Search table (client-side instant filter)
// ============================================================
const liveSearch = document.getElementById('live-search');
if (liveSearch) {
    liveSearch.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('table tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}
