<?php $pageTitle = 'กล้องสด - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<div class="page-head">
    <div>
        <h1>กล้องสด</h1>
        <div class="sub">Real-time monitor — กล้อง, PIR, ประตู, กิจกรรม</div>
    </div>
    <div class="page-head-actions">
        <!-- Camera Mode Toggle — segment buttons with labels -->
        <div class="cam-mode-toggle" role="tablist" aria-label="Camera mode">
            <button type="button" id="btnModeAlways" class="cam-mode-btn active" onclick="setCameraMode('always')" title="กล้องทำงานตลอดเวลา (ใช้ CPU มากกว่า)">
                <?= ico('eye', 14) ?> <span>Always</span>
            </button>
            <button type="button" id="btnModeStandby" class="cam-mode-btn" onclick="setCameraMode('standby')" title="กล้อง standby ตอนไม่มี motion (ประหยัดพลังงาน)">
                <?= ico('moon', 14) ?> <span>Standby</span>
            </button>
        </div>
        <button class="btn sm" id="btnFullscreen" onclick="toggleFullscreen()"><?= ico('maximize', 14) ?> Full Monitor</button>
        <a href="settings.php" class="btn sm ghost" id="linkSettings"><?= ico('settings', 14) ?> ตั้งค่ากล้อง</a>
    </div>
</div>

<style>
.cam-mode-toggle {
    display: inline-flex;
    background: var(--bg-2);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 3px;
    gap: 2px;
}
.cam-mode-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: transparent;
    border: none;
    color: var(--text-2);
    font-size: 12px;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
}
.cam-mode-btn:hover { color: var(--text-1); background: color-mix(in oklch, var(--accent) 8%, transparent); }
.cam-mode-btn.active {
    background: var(--accent);
    color: #fff;
    box-shadow: 0 1px 3px color-mix(in oklch, var(--accent) 40%, transparent);
}
</style>

<style>
/* Fullscreen Monitor Mode */
body.fs-mode { overflow: hidden !important; }
body.fs-mode .sidebar, body.fs-mode .topbar, body.fs-mode .page-head { display: none !important; }
body.fs-mode main { grid-column: 1 / -1 !important; }
body.fs-mode .app { grid-template-columns: 1fr !important; }
body.fs-mode .page { padding: 12px !important; }
body.fs-mode #monitorWrap {
    position: fixed; inset: 0; z-index: 9999;
    background: var(--bg);
    overflow-y: auto;
    padding: 12px;
}
body.fs-mode #fsBar { display: flex !important; }
.live-pulse { animation: pulse-fade 1.5s ease-in-out infinite; }
</style>

<!-- Fullscreen Top Bar (hidden until fs-mode) -->
<div id="fsBar" class="card" style="display:none; padding: 10px 16px; margin-bottom: 12px; align-items: center; justify-content: space-between;">
    <div class="row">
        <span class="badge danger live-pulse"><span class="dot"></span>LIVE</span>
        <strong style="font-size:14px;">BUNNY DOOR — SECURITY MONITOR</strong>
    </div>
    <div class="row">
        <div class="theme-switch" id="fsModeToggle">
            <button type="button" id="fsBtnAlways" class="on" onclick="setCameraMode('always')"><?= ico('eye', 12) ?></button>
            <button type="button" id="fsBtnStandby" onclick="setCameraMode('standby')"><?= ico('moon', 12) ?></button>
        </div>
        <span class="mono text-info" id="fsClock">—</span>
        <button class="btn sm" onclick="toggleFullscreen()"><?= ico('minimize', 12) ?> Exit</button>
    </div>
</div>

<div id="monitorWrap">

<!-- Camera Grid -->
<div class="grid cams section">
    <!-- Camera Outside -->
    <div class="card flush">
        <div class="card-head" style="display: flex; align-items: center; justify-content: space-between;">
            <div class="row">
                <span class="badge" id="cam1Badge">…</span>
                <div>
                    <div class="title" id="cam1Title">กล้องด้านนอกประตู</div>
                    <div class="sub" id="cam1Sub">ตรวจสอบ...</div>
                </div>
            </div>
            <button type="button" onclick="restartCamera('outside')" class="btn sm ghost" title="รีเฟรชกล้อง (release + reopen)"><?= ico('refresh', 12) ?></button>
        </div>
        <div style="padding: 0 14px 14px;">
            <div class="cam">
                <img id="cam1Stream" alt="Camera Outside" style="display:none">
                <div class="cam-feed" id="cam1Placeholder">CONNECTING...</div>
                <div class="cam-overlay">
                    <div class="row">
                        <span class="cam-chip" id="cam1RecChip"><span class="rec"></span><span id="cam1RecText">…</span></span>
                        <span class="cam-chip" id="cam1Faces">Faces: 0</span>
                    </div>
                    <div class="row">
                        <span class="cam-chip" id="cam1Motion">Motion: —</span>
                        <span class="cam-chip" id="cam1Time">—</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Camera Inside -->
    <div class="card flush">
        <div class="card-head" style="display: flex; align-items: center; justify-content: space-between;">
            <div class="row">
                <span class="badge" id="cam2Badge">…</span>
                <div>
                    <div class="title" id="cam2Title">กล้องด้านในประตู</div>
                    <div class="sub" id="cam2Sub">ตรวจสอบ...</div>
                </div>
            </div>
            <button type="button" onclick="restartCamera('inside')" class="btn sm ghost" title="รีเฟรชกล้อง (release + reopen)"><?= ico('refresh', 12) ?></button>
        </div>
        <div style="padding: 0 14px 14px;">
            <div class="cam">
                <img id="cam2Stream" alt="Camera Inside" style="display:none">
                <div class="cam-feed" id="cam2Placeholder">CONNECTING...</div>
                <div class="cam-overlay">
                    <div class="row">
                        <span class="cam-chip" id="cam2RecChip"><span class="rec"></span><span id="cam2RecText">…</span></span>
                        <span class="cam-chip" id="cam2Faces">Faces: 0</span>
                    </div>
                    <div class="row">
                        <span class="cam-chip" id="cam2Motion">Motion: —</span>
                        <span class="cam-chip" id="cam2Time">—</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monitor Panel: Door + PIR + Faces + LastAccess -->
<div class="grid cols-4 section">
    <!-- Door Status -->
    <div class="card">
        <div class="title" style="font-size: 13px; font-weight: 600; margin-bottom: 14px;">สถานะประตู</div>
        <div class="door-state locked" id="monDoorState" style="margin-bottom: 12px;">
            <div class="icon" id="monDoorIcon"><?= ico('lock', 22) ?></div>
            <div class="text">
                <strong id="monDoorText">ประตูล็อก</strong>
                <div class="sub" id="monDoorSub">ESP32: ตรวจสอบ...</div>
            </div>
        </div>
        <div class="btn-row">
            <button class="btn primary sm" onclick="monUnlock()"><?= ico('unlock', 14) ?> ปลดล็อก</button>
            <button class="btn sm" onclick="monLock()"><?= ico('lock', 14) ?> ล็อก</button>
        </div>
    </div>

    <!-- PIR Sensors -->
    <div class="card">
        <div class="title" style="font-size: 13px; font-weight: 600; margin-bottom: 14px;">เซ็นเซอร์ PIR</div>
        <div class="metric-row">
            <div class="lbl"><?= ico('arrow-right', 14) ?> ด้านนอก</div>
            <div class="val"><span class="badge" id="pir1Badge">…</span></div>
        </div>
        <div class="metric-row">
            <div class="lbl"><?= ico('arrow-left', 14) ?> ด้านใน</div>
            <div class="val"><span class="badge" id="pir2Badge">…</span></div>
        </div>
        <p class="tiny muted" style="margin-top: 10px; text-align: center;" id="pirEspInfo">ESP32: ตรวจสอบ...</p>
    </div>

    <!-- Detected Faces -->
    <div class="card flush">
        <div class="card-head">
            <div class="title">ใบหน้าที่ตรวจพบ</div>
        </div>
        <div id="detectedFaces" style="padding: 8px 14px 14px; max-height: 180px; overflow-y: auto;">
            <div class="empty" style="padding: 20px;">ไม่มีใบหน้า</div>
        </div>
    </div>

    <!-- Last Access -->
    <div class="card flush">
        <div class="card-head">
            <div class="title">เข้า-ออกล่าสุด</div>
        </div>
        <div id="lastAccessList" style="padding: 8px 14px 14px; max-height: 180px; overflow-y: auto;">
            <div class="empty" style="padding: 20px;">กำลังโหลด...</div>
        </div>
    </div>
</div>

<!-- Activity Timeline -->
<div class="card flush section">
    <div class="card-head">
        <div class="title">กิจกรรมเข้า-ออกวันนี้ (รายชั่วโมง)</div>
        <div class="actions">
            <span class="badge ok"><span class="dot"></span>เข้า</span>
            <span class="badge info"><span class="dot"></span>ออก</span>
            <span class="badge danger"><span class="dot"></span>ปฏิเสธ</span>
        </div>
    </div>
    <div class="card-body">
        <div class="bar-chart" id="activityChart"></div>
        <div class="chart-axis">
            <span>00</span><span>03</span><span>06</span><span>09</span><span>12</span><span>15</span><span>18</span><span>21</span><span>23</span>
        </div>
    </div>
</div>

</div><!-- /monitorWrap -->

<script>
let _camOutEnabled = false;
let _camInEnabled = false;

// ============================================================
// Camera Config
// ============================================================
async function fetchCamConfig() {
    try {
        const data = await fetchAPI(FACE_SERVER + '/api/status');
        if (!data) throw new Error();
        updateCamUI(1, 'outside', data.camera_outside, data.camera_mode);
        updateCamUI(2, 'inside', data.camera_inside, data.camera_mode);
        updateCamStatus(data);
    } catch {
        setCamError(1, 'Face Server ออฟไลน์');
        setCamError(2, 'Face Server ออฟไลน์');
    }
}

function updateCamUI(num, side, cfg, camMode) {
    const titleEl = document.getElementById('cam' + num + 'Title');
    const subEl = document.getElementById('cam' + num + 'Sub');
    const badgeEl = document.getElementById('cam' + num + 'Badge');
    const imgEl = document.getElementById('cam' + num + 'Stream');
    const phEl = document.getElementById('cam' + num + 'Placeholder');
    const recChip = document.getElementById('cam' + num + 'RecChip');
    const recText = document.getElementById('cam' + num + 'RecText');
    const label = side === 'outside' ? 'ด้านนอก' : 'ด้านใน';

    if (!cfg || cfg.id < 0) {
        titleEl.textContent = 'กล้อง' + label + 'ประตู';
        subEl.innerHTML = 'ยังไม่ได้กำหนด — <a href="settings.php" class="text-accent">ตั้งค่า</a>';
        badgeEl.textContent = 'UNASSIGNED'; badgeEl.className = 'badge warn';
        imgEl.style.display = 'none'; phEl.style.display = '';
        phEl.textContent = 'NO CAMERA ASSIGNED';
        recChip.classList.remove('ok', 'standby');
        recText.textContent = 'OFFLINE';
        if (side === 'outside') _camOutEnabled = false; else _camInEnabled = false;
        return;
    }
    const devName = cfg.device_name || '/dev/video' + cfg.id;
    titleEl.textContent = 'กล้อง' + label + 'ประตู';
    subEl.textContent = devName + ' (video' + cfg.id + ')';

    if (cfg.active && cfg.has_frame) {
        badgeEl.textContent = 'LIVE'; badgeEl.className = 'badge ok';
        // standby + ไม่มี motion (ไม่เจอหน้า) = ไม่ได้ recording จริง → STANDBY (จุดเทา ไม่กระพริบ)
        // ไม่งั้น (always mode หรือ standby+เจอหน้า) → REC (จุดแดงกระพริบ)
        if (camMode === 'standby' && !cfg.motion) {
            recChip.classList.remove('ok');
            recChip.classList.add('standby');
            recText.textContent = 'STANDBY';
        } else {
            recChip.classList.remove('ok', 'standby');
            recText.textContent = 'REC';
        }
        if (side === 'outside') _camOutEnabled = true; else _camInEnabled = true;
    } else {
        badgeEl.textContent = 'OFFLINE'; badgeEl.className = 'badge danger';
        imgEl.style.display = 'none'; phEl.style.display = '';
        phEl.textContent = 'CAMERA OFFLINE';
        recChip.classList.remove('ok', 'standby');
        recText.textContent = 'OFFLINE';
        if (side === 'outside') _camOutEnabled = false; else _camInEnabled = false;
    }
}

function setCamError(num, msg) {
    const b = document.getElementById('cam' + num + 'Badge');
    b.textContent = 'ERROR'; b.className = 'badge danger';
    document.getElementById('cam' + num + 'Stream').style.display = 'none';
    const ph = document.getElementById('cam' + num + 'Placeholder');
    ph.style.display = '';
    ph.textContent = msg.toUpperCase();
}

function updateCamStatus(data) {
    const co = data.camera_outside, ci = data.camera_inside;
    document.getElementById('cam1Motion').textContent = 'Motion: ' + (co?.motion ? 'YES' : 'None');
    document.getElementById('cam2Motion').textContent = 'Motion: ' + (ci?.motion ? 'YES' : 'None');
    document.getElementById('cam1Faces').textContent = 'Faces: ' + (co?.faces?.length || 0);
    document.getElementById('cam2Faces').textContent = 'Faces: ' + (ci?.faces?.length || 0);
    const now = new Date().toLocaleTimeString('th-TH');
    document.getElementById('cam1Time').textContent = now;
    document.getElementById('cam2Time').textContent = now;

    // Faces list
    const allFaces = [...(co?.faces || []), ...(ci?.faces || [])];
    const fd = document.getElementById('detectedFaces');
    if (allFaces.length > 0) {
        fd.innerHTML = allFaces.map(([name, conf]) => {
            const unknown = name === 'Unknown';
            return `<div class="row" style="padding:6px 0; border-bottom:1px solid var(--border); justify-content: space-between;">
                <div class="row gap-2">
                    <span class="badge ${unknown ? 'danger' : 'ok'}"><span class="dot"></span>${esc(name)}</span>
                </div>
                <span class="mono tiny muted">${parseFloat(conf)?.toFixed(0) || 0}%</span>
            </div>`;
        }).join('');
    } else {
        fd.innerHTML = '<div class="empty" style="padding: 20px;">ไม่มีใบหน้า</div>';
    }
}

// ============================================================
// Snapshot Refresh
// ============================================================
function refreshSnapshots() {
    const ts = Date.now();
    if (_camOutEnabled) {
        const img = new Image();
        img.onload = () => { document.getElementById('cam1Stream').src = img.src; document.getElementById('cam1Stream').style.display = ''; document.getElementById('cam1Placeholder').style.display = 'none'; };
        img.src = FACE_SERVER + '/api/snapshot/outside?t=' + ts;
    }
    if (_camInEnabled) {
        const img = new Image();
        img.onload = () => { document.getElementById('cam2Stream').src = img.src; document.getElementById('cam2Stream').style.display = ''; document.getElementById('cam2Placeholder').style.display = 'none'; };
        img.src = FACE_SERVER + '/api/snapshot/inside?t=' + ts;
    }
}

// ============================================================
// ESP32: Door + PIR
// ============================================================
function applyMonDoorState(state) {
    const card = document.getElementById('monDoorState');
    const text = document.getElementById('monDoorText');
    const icon = document.getElementById('monDoorIcon');
    if (state === 'unlocked') {
        card.classList.add('unlocked'); card.classList.remove('locked');
        text.textContent = 'ประตูปลดล็อก';
        icon.innerHTML = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 7-2.7"/></svg>`;
    } else {
        card.classList.add('locked'); card.classList.remove('unlocked');
        text.textContent = 'ประตูล็อก';
        icon.innerHTML = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>`;
    }
}

async function checkESP32() {
    try {
        const d = await fetchAPI(FACE_SERVER + '/api/esp32/health');
        if (d && d.online) {
            applyMonDoorState(d.door === 'locked' ? 'locked' : 'unlocked');
            document.getElementById('monDoorSub').textContent = 'ESP32: ' + (d.ip || '-') + ' · RSSI: ' + (d.rssi || '-') + 'dBm';

            const pir1 = document.getElementById('pir1Badge');
            const pir2 = document.getElementById('pir2Badge');
            if (d.pir_outside) { pir1.textContent = 'ตรวจจับ!'; pir1.className = 'badge warn'; }
            else { pir1.textContent = 'ปกติ'; pir1.className = 'badge'; }
            if (d.pir_inside) { pir2.textContent = 'ตรวจจับ!'; pir2.className = 'badge warn'; }
            else { pir2.textContent = 'ปกติ'; pir2.className = 'badge'; }
            document.getElementById('pirEspInfo').innerHTML = '<span class="text-ok">● ESP32 Online</span>';
        } else {
            document.getElementById('monDoorSub').textContent = 'ESP32: ออฟไลน์';
            document.getElementById('pir1Badge').textContent = '—';
            document.getElementById('pir2Badge').textContent = '—';
            document.getElementById('pir1Badge').className = 'badge';
            document.getElementById('pir2Badge').className = 'badge';
            document.getElementById('pirEspInfo').innerHTML = '<span class="text-danger">● ESP32 Offline</span>';
        }
    } catch {
        document.getElementById('monDoorSub').textContent = 'ไม่สามารถเชื่อมต่อ';
        document.getElementById('pirEspInfo').innerHTML = '<span class="text-danger">ไม่สามารถเชื่อมต่อ</span>';
    }
}

async function monUnlock() { await postAPI(FACE_SERVER + '/api/door/unlock'); checkESP32(); }
async function monLock()   { await postAPI(FACE_SERVER + '/api/door/lock');   checkESP32(); }

// ============================================================
// Last Access Log
// ============================================================
async function loadLastAccess() {
    try {
        const res = await fetchAPI('api/access_logs.php?limit=5&page=1');
        const el = document.getElementById('lastAccessList');
        if (!res?.data?.length) {
            el.innerHTML = '<div class="empty" style="padding: 20px;">ยังไม่มีข้อมูล</div>';
            return;
        }
        el.innerHTML = res.data.map(log => {
            const name = log.first_name ? esc(log.first_name) : '<span class="text-danger">ไม่รู้จัก</span>';
            const dir = log.direction === 'IN'
                ? '<span class="text-ok tiny">→ เข้า</span>'
                : '<span class="text-info tiny">← ออก</span>';
            const time = new Date(log.created_at).toLocaleTimeString('th-TH', {hour:'2-digit',minute:'2-digit'});
            return `<div class="row" style="padding:6px 0; border-bottom:1px solid var(--border); justify-content: space-between; font-size: 12px;">
                <span>${name}</span>
                <div class="row gap-2">${dir}<span class="mono muted">${time}</span></div>
            </div>`;
        }).join('');
    } catch {
        document.getElementById('lastAccessList').innerHTML = '<div class="empty" style="padding: 20px;">โหลดไม่ได้</div>';
    }
}

// ============================================================
// Activity Chart
// ============================================================
async function loadActivityChart() {
    const chart = document.getElementById('activityChart');
    try {
        const today = new Date().toISOString().split('T')[0];
        const res = await fetchAPI('api/access_logs.php?limit=500&page=1&date_from=' + today + '&date_to=' + today);
        if (!res?.data) return;
        const hoursIn = new Array(24).fill(0);
        const hoursOut = new Array(24).fill(0);
        const hoursDenied = new Array(24).fill(0);
        res.data.forEach(log => {
            const h = new Date(log.created_at).getHours();
            if (log.is_authorized == 0) hoursDenied[h]++;
            else if (log.direction === 'IN') hoursIn[h]++;
            else hoursOut[h]++;
        });
        const maxVal = Math.max(1, ...hoursIn, ...hoursOut, ...hoursDenied);
        const currentHour = new Date().getHours();
        chart.innerHTML = '';
        for (let h = 0; h < 24; h++) {
            const inH  = (hoursIn[h] / maxVal) * 100;
            const outH = (hoursOut[h] / maxVal) * 100;
            const denH = (hoursDenied[h] / maxVal) * 100;
            const isCurrent = h === currentHour;
            const dim = h > currentHour ? 'dim' : '';
            chart.innerHTML += `
                <div class="bar-col ${dim}">
                    <div class="tip">${h.toString().padStart(2,'0')}:00 · เข้า ${hoursIn[h]} ออก ${hoursOut[h]} ปฏิเสธ ${hoursDenied[h]}</div>
                    ${denH > 0 ? `<div class="bar denied" style="height:${denH}%"></div>` : ''}
                    ${outH > 0 ? `<div class="bar out" style="height:${outH}%"></div>` : ''}
                    <div class="bar in ${isCurrent ? 'current' : ''}" style="height:${Math.max(2, inH)}%"></div>
                </div>`;
        }
    } catch {
        chart.innerHTML = '<div class="empty" style="width:100%;">โหลดกราฟไม่ได้</div>';
    }
}

// ============================================================
// Fullscreen Monitor Mode
// ============================================================
let _isFullscreen = false;
function toggleFullscreen() {
    _isFullscreen = !_isFullscreen;
    document.body.classList.toggle('fs-mode', _isFullscreen);
    document.getElementById('fsBar').style.display = _isFullscreen ? 'flex' : 'none';
    if (_isFullscreen && document.documentElement.requestFullscreen) {
        document.documentElement.requestFullscreen().catch(() => {});
    } else if (!_isFullscreen && document.exitFullscreen && document.fullscreenElement) {
        document.exitFullscreen().catch(() => {});
    }
}
document.addEventListener('fullscreenchange', () => {
    if (!document.fullscreenElement && _isFullscreen) toggleFullscreen();
});
function updateFsClock() {
    const el = document.getElementById('fsClock');
    if (el) el.textContent = new Date().toLocaleString('th-TH', { dateStyle: 'long', timeStyle: 'medium' });
}

// ============================================================
// Camera Mode Toggle (always / standby)
// ============================================================
async function setCameraMode(mode) {
    const res = await postAPI(FACE_SERVER + '/api/camera/mode', { mode });
    if (res?.success) updateModeUI(mode);
}

async function restartCamera(which) {
    const label = which === 'outside' ? 'นอก' : 'ใน';
    showToast(`กำลังรีเฟรชกล้อง${label}...`, 'info');
    try {
        const r = await postAPI(FACE_SERVER + '/api/camera/restart', { camera: which });
        if (r?.success) {
            showToast(`รีเฟรชกล้อง${label}สำเร็จ — กำลังเปิดใหม่`, 'success');
        } else {
            showToast(r?.error || 'รีเฟรชไม่สำเร็จ', 'error');
        }
    } catch (e) {
        showToast('Pi ไม่ตอบ: ' + e.message, 'error');
    }
}
function updateModeUI(mode) {
    document.getElementById('btnModeAlways').classList.toggle('active', mode === 'always');
    document.getElementById('btnModeStandby').classList.toggle('active', mode === 'standby');
    // legacy 'on' class — กันโค้ดเก่าใน fullscreen bar
    document.getElementById('btnModeAlways').classList.toggle('on', mode === 'always');
    document.getElementById('btnModeStandby').classList.toggle('on', mode === 'standby');
    const fa = document.getElementById('fsBtnAlways'), fs = document.getElementById('fsBtnStandby');
    if (fa) fa.classList.toggle('on', mode === 'always');
    if (fs) fs.classList.toggle('on', mode === 'standby');
}
async function fetchCameraMode() {
    try { const data = await fetchAPI(FACE_SERVER + '/api/status'); if (data?.camera_mode) updateModeUI(data.camera_mode); } catch {}
}

// ============================================================
// Init
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    fetchCamConfig();
    fetchCameraMode();
    checkESP32();
    loadLastAccess();
    loadActivityChart();

    setTimeout(refreshSnapshots, 2000);
    setInterval(refreshSnapshots, 3000);
    setInterval(fetchCamConfig, 10000);
    setInterval(checkESP32, 3000);
    setInterval(loadLastAccess, 10000);
    setInterval(loadActivityChart, 30000);
    setInterval(updateFsClock, 1000);

    if (new URLSearchParams(window.location.search).get('fs') === '1') {
        setTimeout(toggleFullscreen, 500);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
