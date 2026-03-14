    </main>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2" style="min-width:320px;"></div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="glass rounded-2xl p-6 max-w-sm w-full mx-4 shadow-2xl">
        <div class="text-center mb-4">
            <div class="w-14 h-14 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-exclamation-triangle text-red-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-bold" id="confirmTitle">ยืนยันการดำเนินการ</h3>
            <p class="text-gray-400 text-sm mt-1" id="confirmMessage">คุณแน่ใจหรือไม่?</p>
        </div>
        <div class="flex gap-3">
            <button onclick="closeConfirm()" class="flex-1 bg-white/10 hover:bg-white/20 text-white py-2.5 rounded-xl transition">
                ยกเลิก
            </button>
            <button id="confirmBtn" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-xl transition">
                ยืนยัน
            </button>
        </div>
    </div>
</div>

<script>
// ============================================================
// Global Utilities
// ============================================================
const FACE_SERVER = '<?= FACE_SERVER_URL ?>';

async function fetchAPI(url) {
    try {
        const res = await fetch(url);
        return await res.json();
    } catch(e) {
        console.error('API Error:', e);
        return null;
    }
}

async function postAPI(url, data = {}) {
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return await res.json();
    } catch(e) {
        console.error('API Error:', e);
        return null;
    }
}

// Check server status periodically
async function checkServerStatus() {
    const dot = document.getElementById('serverStatus');
    const text = document.getElementById('serverStatusText');
    try {
        const res = await fetch(FACE_SERVER + '/api/status', { signal: AbortSignal.timeout(3000) });
        if (res.ok) {
            dot.className = 'pulse-dot w-2 h-2 bg-green-400 rounded-full';
            text.textContent = 'System Online';
        } else {
            dot.className = 'w-2 h-2 bg-yellow-400 rounded-full';
            text.textContent = 'Partial Online';
        }
    } catch {
        dot.className = 'w-2 h-2 bg-red-400 rounded-full';
        text.textContent = 'Server Offline';
    }
}
document.addEventListener('DOMContentLoaded', () => {
    checkServerStatus();
    setInterval(checkServerStatus, 10000);
});

// XSS protection - escape HTML in user data
function esc(str) {
    if (str == null) return '';
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}

// Format datetime
function formatDateTime(iso) {
    if (!iso) return '-';
    const d = new Date(iso);
    return d.toLocaleDateString('th-TH') + ' ' + d.toLocaleTimeString('th-TH');
}

function formatTime(iso) {
    if (!iso) return '-';
    return new Date(iso).toLocaleTimeString('th-TH');
}

// ============================================================
// Toast Notifications
// ============================================================
function showToast(message, type = 'success', duration = 3000) {
    const container = document.getElementById('toastContainer');
    const colors = {
        success: { bg: 'bg-green-500/20 border-green-500/30', icon: 'fa-check-circle text-green-400' },
        error:   { bg: 'bg-red-500/20 border-red-500/30', icon: 'fa-times-circle text-red-400' },
        warning: { bg: 'bg-yellow-500/20 border-yellow-500/30', icon: 'fa-exclamation-circle text-yellow-400' },
        info:    { bg: 'bg-blue-500/20 border-blue-500/30', icon: 'fa-info-circle text-blue-400' },
    };
    const c = colors[type] || colors.info;
    const toast = document.createElement('div');
    toast.className = `flex items-center gap-3 ${c.bg} border backdrop-blur-xl rounded-xl px-4 py-3 shadow-lg transform translate-x-full transition-transform duration-300`;
    toast.innerHTML = `<i class="fas ${c.icon} text-lg"></i><span class="text-sm text-white flex-1">${esc(message)}</span><button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-white"><i class="fas fa-times text-xs"></i></button>`;
    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.remove('translate-x-full'));
    if (duration > 0) {
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
}

// ============================================================
// Confirmation Modal
// ============================================================
let _confirmCallback = null;

function showConfirm(title, message, callback) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    _confirmCallback = callback;
    const modal = document.getElementById('confirmModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeConfirm() {
    const modal = document.getElementById('confirmModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    _confirmCallback = null;
}

document.getElementById('confirmBtn').addEventListener('click', () => {
    if (_confirmCallback) _confirmCallback();
    closeConfirm();
});

// Close modal on backdrop click
document.getElementById('confirmModal').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) closeConfirm();
});
</script>
</body>
</html>
