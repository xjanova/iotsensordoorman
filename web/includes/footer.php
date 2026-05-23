    </div><!-- /.page -->
</main>
</div><!-- /.app -->

<!-- Toast Container -->
<div id="toastContainer" style="position:fixed; top:20px; right:20px; z-index:60; display:flex; flex-direction:column; gap:10px;"></div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal-backdrop" style="display:none;">
    <div class="modal" style="max-width: 420px;">
        <div class="modal-head">
            <h3 id="confirmTitle">ยืนยันการดำเนินการ</h3>
            <button onclick="closeConfirm()" class="icon-btn" aria-label="ปิด"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="modal-body">
            <p id="confirmMessage" style="font-size:13px; color:var(--text-2); margin:0;">คุณแน่ใจหรือไม่?</p>
        </div>
        <div class="modal-foot">
            <button onclick="closeConfirm()" class="btn">ยกเลิก</button>
            <button id="confirmBtn" class="btn danger">ยืนยัน</button>
        </div>
    </div>
</div>

<script>
// ============================================================
// Constants
// ============================================================
const FACE_SERVER = '<?= FACE_SERVER_URL ?>';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

// ============================================================
// Global utilities (legacy API — keep stable for existing pages)
// ============================================================
async function fetchAPI(url) {
    try { const r = await fetch(url); return await r.json(); }
    catch (e) { console.error('API Error:', e); return null; }
}
async function postAPI(url, data = {}) {
    try {
        const r = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type':'application/json', 'X-CSRF-Token': CSRF_TOKEN},
            body: JSON.stringify(data)
        });
        return await r.json();
    } catch (e) { console.error('API Error:', e); return null; }
}
async function deleteAPI(url) {
    try {
        const r = await fetch(url, {
            method: 'DELETE',
            headers: {'X-CSRF-Token': CSRF_TOKEN},
        });
        return await r.json();
    } catch (e) { console.error('API Error:', e); return null; }
}
function esc(str) { if (str == null) return ''; const d = document.createElement('div'); d.textContent = String(str); return d.innerHTML; }
function formatDateTime(iso) { if (!iso) return '-'; const d = new Date(iso); return d.toLocaleDateString('th-TH') + ' ' + d.toLocaleTimeString('th-TH'); }
function formatTime(iso) { if (!iso) return '-'; return new Date(iso).toLocaleTimeString('th-TH'); }

// ============================================================
// Toast
// ============================================================
function showToast(message, type = 'success', duration = 3000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const icons = {
        success: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>',
        error:   '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/></svg>',
        warning: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.86a2 2 0 0 1 3.4 0l8 13.86A2 2 0 0 1 20 21H4a2 2 0 0 1-1.7-3.28z"/><path d="M12 9v5M12 17.5h.01"/></svg>',
        info:    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>',
    };
    const cls = { success: 'ok', error: 'error', warning: 'warn', info: 'info' };
    const t = cls[type] || 'info';
    const toast = document.createElement('div');
    toast.className = 'toast ' + t;
    toast.innerHTML = `<span class="text-${t==='ok'?'ok':t}">${icons[type] || icons.info}</span><span class="msg">${esc(message)}</span><button class="close" aria-label="ปิด">&times;</button>`;
    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));
    const close = () => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 250); };
    toast.querySelector('.close').addEventListener('click', close);
    if (duration > 0) setTimeout(close, duration);
}

// ============================================================
// Confirm modal — keep signature: showConfirm(title, message, callback)
// ============================================================
let _confirmCallback = null;
function showConfirm(title, message, callback) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    _confirmCallback = callback;
    const m = document.getElementById('confirmModal');
    m.style.display = 'flex';
}
function closeConfirm() {
    const m = document.getElementById('confirmModal');
    m.style.display = 'none';
    _confirmCallback = null;
}
document.getElementById('confirmBtn').addEventListener('click', () => { if (_confirmCallback) _confirmCallback(); closeConfirm(); });
document.getElementById('confirmModal').addEventListener('click', (e) => { if (e.target === e.currentTarget) closeConfirm(); });

// ============================================================
// Theme + Accent (persisted in localStorage)
// ============================================================
const THEME_KEY  = 'doorman.theme';
const ACCENT_KEY = 'doorman.accent';

function applyTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem(THEME_KEY, t);
    document.getElementById('themeLight').classList.toggle('on', t === 'light');
    document.getElementById('themeDark').classList.toggle('on', t === 'dark');
}
function applyAccent(a) {
    document.documentElement.setAttribute('data-accent', a);
    localStorage.setItem(ACCENT_KEY, a);
    const label = { emerald: 'Emerald', indigo: 'Indigo', blue: 'Blue', amber: 'Amber' }[a] || 'Indigo';
    document.getElementById('accentLabel').textContent = label;
    document.querySelectorAll('.accent-menu .opt').forEach(opt => {
        opt.classList.toggle('active', opt.dataset.accent === a);
    });
}

// Init from persisted
(function initThemeAccent() {
    const t = localStorage.getItem(THEME_KEY) || 'dark';
    const a = localStorage.getItem(ACCENT_KEY) || 'indigo';
    applyTheme(t);
    applyAccent(a);
})();

document.getElementById('themeLight').addEventListener('click', () => applyTheme('light'));
document.getElementById('themeDark').addEventListener('click', () => applyTheme('dark'));

const accentTrigger = document.getElementById('accentTrigger');
const accentMenu = document.getElementById('accentMenu');
accentTrigger.addEventListener('click', (e) => {
    e.stopPropagation();
    const open = accentMenu.classList.toggle('open');
    accentTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
});
document.querySelectorAll('.accent-menu .opt').forEach(opt => {
    opt.addEventListener('click', () => {
        applyAccent(opt.dataset.accent);
        accentMenu.classList.remove('open');
    });
});
document.addEventListener('click', (e) => {
    if (!accentMenu.contains(e.target) && !accentTrigger.contains(e.target)) {
        accentMenu.classList.remove('open');
        accentTrigger.setAttribute('aria-expanded', 'false');
    }
});

// ============================================================
// Topbar clock
// ============================================================
function updateTopClock() {
    const now = new Date();
    const date = now.toLocaleDateString('th-TH', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
    const time = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const dateEl = document.getElementById('topClockDate');
    const timeEl = document.getElementById('topClockTime');
    if (dateEl) dateEl.textContent = date;
    if (timeEl) timeEl.textContent = time;
}
updateTopClock();
setInterval(updateTopClock, 1000);

// ============================================================
// Topbar door pill — poll ESP32 health every 5s
// ============================================================
async function refreshTopDoorPill() {
    const pill = document.getElementById('topDoorPill');
    const label = document.getElementById('topDoorLabel');
    if (!pill || !label) return;
    try {
        const d = await fetch(FACE_SERVER + '/api/esp32/health', { signal: AbortSignal.timeout(3000) });
        if (!d.ok) throw new Error();
        const data = await d.json();
        if (data.online) {
            const unlocked = data.door === 'unlocked' || data.door === 'unlock';
            pill.classList.toggle('unlocked', unlocked);
            pill.classList.toggle('locked', !unlocked);
            label.textContent = unlocked ? 'ประตูปลดล็อก' : 'ประตูล็อก';
        } else {
            pill.classList.remove('unlocked');
            pill.classList.add('locked');
            label.textContent = 'ESP32 ออฟไลน์';
        }
    } catch {
        pill.classList.remove('unlocked');
        pill.classList.add('locked');
        label.textContent = 'ตรวจสอบ...';
    }
}
refreshTopDoorPill();
setInterval(refreshTopDoorPill, 5000);

// ============================================================
// Topbar unlock button — same endpoint as dashboard
// ============================================================
const topUnlockBtn = document.getElementById('topUnlockBtn');
if (topUnlockBtn) {
    let _topUnlockBusy = false;
    topUnlockBtn.addEventListener('click', async () => {
        if (_topUnlockBusy) return;
        _topUnlockBusy = true;
        topUnlockBtn.disabled = true;
        const res = await postAPI(FACE_SERVER + '/api/door/unlock');
        if (res?.success) {
            showToast('ปลดล็อกประตูแล้ว', 'success');
            refreshTopDoorPill();
            setTimeout(refreshTopDoorPill, 9000); // หลัง auto-lock 8s
        } else {
            showToast('สั่งปลดล็อกไม่สำเร็จ', 'error');
        }
        setTimeout(() => { _topUnlockBusy = false; topUnlockBtn.disabled = false; }, 1500);
    });
}
</script>
</body>
</html>
