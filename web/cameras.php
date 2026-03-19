<?php $pageTitle = 'กล้องวงจรปิด - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<div class="flex justify-between items-center mb-8" id="pageHeader">
    <div>
        <h2 class="text-2xl font-bold">กล้องวงจรปิด</h2>
        <p class="text-gray-400 text-sm mt-1">Monitor แบบ Real-time — กล้อง, เซ็นเซอร์, ประตู, กิจกรรม</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="text-xs text-gray-500" id="monitorClock"></span>
        <button onclick="toggleFullscreen()" class="bg-white/10 hover:bg-white/20 text-white px-3 py-2 rounded-lg text-sm transition flex items-center gap-2" id="btnFullscreen">
            <i class="fas fa-expand" id="fsIcon"></i> <span id="fsLabel">Full Monitor</span>
        </button>
        <a href="settings.php" class="text-sm text-blue-400 hover:underline flex items-center gap-1" id="linkSettings">
            <i class="fas fa-cog"></i> ตั้งค่า
        </a>
    </div>
</div>

<style>
/* Fullscreen Monitor Mode */
body.fs-mode { overflow: hidden !important; }
body.fs-mode .sidebar, body.fs-mode nav, body.fs-mode #pageHeader,
body.fs-mode #linkSettings, body.fs-mode footer { display: none !important; }
body.fs-mode main, body.fs-mode .main-content { margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
body.fs-mode #monitorWrap {
    position: fixed; inset: 0; z-index: 9999;
    background: #0a0a0f;
    overflow-y: auto;
    padding: 12px;
}
body.fs-mode #fsBar {
    display: flex !important;
}

/* Glow effects */
.fs-glow { box-shadow: 0 0 20px rgba(59,130,246,0.15), 0 0 60px rgba(59,130,246,0.05); }
.fs-cam-border { border: 1px solid rgba(59,130,246,0.2); }

/* Scanline overlay */
@keyframes scanline { 0% { top: -5%; } 100% { top: 105%; } }
.scanline::after {
    content: ''; position: absolute; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, transparent, rgba(59,130,246,0.15), transparent);
    animation: scanline 4s linear infinite; pointer-events: none;
}

/* Live pulse */
@keyframes livePulse { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }
.live-pulse { animation: livePulse 1.5s ease-in-out infinite; }
</style>

<!-- Fullscreen Top Bar (hidden until fs-mode) -->
<div id="fsBar" class="hidden items-center justify-between px-4 py-2 mb-3 rounded-xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
    <div class="flex items-center gap-3">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 bg-red-500 rounded-full live-pulse"></span>
            <span class="text-xs font-mono text-red-400 tracking-wider">LIVE</span>
        </div>
        <span class="text-sm font-medium text-white/80">BUNNY DOOR — SECURITY MONITOR</span>
    </div>
    <div class="flex items-center gap-4">
        <span class="text-xs font-mono text-cyan-400" id="fsClock"></span>
        <button onclick="toggleFullscreen()" class="bg-white/10 hover:bg-white/20 text-white px-3 py-1.5 rounded-lg text-xs transition">
            <i class="fas fa-compress mr-1"></i> Exit
        </button>
    </div>
</div>

<div id="monitorWrap">

<!-- Camera Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Camera Outside -->
    <div class="glass rounded-2xl overflow-hidden">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 bg-gray-500 rounded-full" id="cam1Dot"></span>
                <div>
                    <h3 class="font-medium" id="cam1Title">กล้องด้านนอกประตู</h3>
                    <p class="text-xs text-gray-400" id="cam1Sub">กำลังตรวจสอบ...</p>
                </div>
            </div>
            <span class="text-xs bg-gray-500/20 text-gray-400 px-2 py-1 rounded" id="cam1Badge">...</span>
        </div>
        <div class="stream-container aspect-video relative">
            <img id="cam1Stream" src="" class="w-full" alt="Camera Outside" style="display:none">
            <div id="cam1Placeholder" class="w-full h-full flex items-center justify-center bg-black/50 aspect-video">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-gray-600 mb-2"></i>
                    <p class="text-gray-500 text-sm">กำลังตรวจสอบ...</p>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-4">
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-xs text-gray-300" id="cam1Motion">Motion: -</p>
                        <p class="text-xs text-gray-300" id="cam1Faces">Faces: 0</p>
                    </div>
                    <p class="text-xs text-gray-400" id="cam1Time"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Camera Inside -->
    <div class="glass rounded-2xl overflow-hidden">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 bg-gray-500 rounded-full" id="cam2Dot"></span>
                <div>
                    <h3 class="font-medium" id="cam2Title">กล้องด้านในประตู</h3>
                    <p class="text-xs text-gray-400" id="cam2Sub">กำลังตรวจสอบ...</p>
                </div>
            </div>
            <span class="text-xs bg-gray-500/20 text-gray-400 px-2 py-1 rounded" id="cam2Badge">...</span>
        </div>
        <div class="stream-container aspect-video relative">
            <img id="cam2Stream" src="" class="w-full" alt="Camera Inside" style="display:none">
            <div id="cam2Placeholder" class="w-full h-full flex items-center justify-center bg-black/50 aspect-video">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-gray-600 mb-2"></i>
                    <p class="text-gray-500 text-sm">กำลังตรวจสอบ...</p>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-4">
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-xs text-gray-300" id="cam2Motion">Motion: -</p>
                        <p class="text-xs text-gray-300" id="cam2Faces">Faces: 0</p>
                    </div>
                    <p class="text-xs text-gray-400" id="cam2Time"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monitor Panel: PIR + Door + Faces + Activity -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
    <!-- Door Status -->
    <div class="glass rounded-2xl p-5">
        <h3 class="font-medium mb-4 flex items-center gap-2 text-sm">
            <i class="fas fa-door-closed text-blue-400"></i> สถานะประตู
        </h3>
        <div class="text-center py-2">
            <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3 bg-red-500/20" id="monDoorIcon">
                <i class="fas fa-lock text-2xl text-red-400" id="monDoorIconInner"></i>
            </div>
            <p class="text-lg font-bold text-red-400" id="monDoorText">ประตูล็อก</p>
            <p class="text-xs text-gray-500 mt-1" id="monDoorSub">ESP32: ตรวจสอบ...</p>
        </div>
        <div class="flex gap-2 mt-4">
            <button onclick="monUnlock()" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-1.5 rounded-lg text-xs transition">
                <i class="fas fa-lock-open mr-1"></i> ปลดล็อก
            </button>
            <button onclick="monLock()" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-1.5 rounded-lg text-xs transition">
                <i class="fas fa-lock mr-1"></i> ล็อก
            </button>
        </div>
    </div>

    <!-- PIR Sensors -->
    <div class="glass rounded-2xl p-5">
        <h3 class="font-medium mb-4 flex items-center gap-2 text-sm">
            <i class="fas fa-satellite-dish text-yellow-400"></i> เซ็นเซอร์ PIR
        </h3>
        <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                <div class="flex items-center gap-2">
                    <i class="fas fa-arrow-right text-yellow-400 text-xs"></i>
                    <span class="text-sm">นอก</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400" id="pir1Label">-</span>
                    <span class="w-3 h-3 bg-gray-500 rounded-full" id="pir1Dot"></span>
                </div>
            </div>
            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                <div class="flex items-center gap-2">
                    <i class="fas fa-arrow-left text-purple-400 text-xs"></i>
                    <span class="text-sm">ใน</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400" id="pir2Label">-</span>
                    <span class="w-3 h-3 bg-gray-500 rounded-full" id="pir2Dot"></span>
                </div>
            </div>
            <div class="text-center pt-1">
                <p class="text-xs text-gray-600" id="pirEspInfo">ESP32: ตรวจสอบ...</p>
            </div>
        </div>
    </div>

    <!-- Detected Faces -->
    <div class="glass rounded-2xl p-5">
        <h3 class="font-medium mb-4 flex items-center gap-2 text-sm">
            <i class="fas fa-face-smile text-blue-400"></i> ใบหน้าที่ตรวจพบ
        </h3>
        <div class="space-y-2 max-h-[180px] overflow-y-auto" id="detectedFaces">
            <p class="text-gray-500 text-sm text-center py-4">ไม่มีใบหน้า</p>
        </div>
    </div>

    <!-- Last Access -->
    <div class="glass rounded-2xl p-5">
        <h3 class="font-medium mb-4 flex items-center gap-2 text-sm">
            <i class="fas fa-clock-rotate-left text-green-400"></i> เข้า-ออกล่าสุด
        </h3>
        <div class="space-y-2 max-h-[180px] overflow-y-auto" id="lastAccessList">
            <p class="text-gray-500 text-sm text-center py-4">กำลังโหลด...</p>
        </div>
    </div>
</div>

<!-- Activity Timeline (กราฟ เข้า-ออก รายชั่วโมง วันนี้) -->
<div class="glass rounded-2xl p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-medium flex items-center gap-2 text-sm">
            <i class="fas fa-chart-bar text-cyan-400"></i> กิจกรรมเข้า-ออกวันนี้ (รายชั่วโมง)
        </h3>
        <div class="flex items-center gap-4 text-xs">
            <span class="flex items-center gap-1"><span class="w-3 h-2 bg-green-500 rounded-sm inline-block"></span> เข้า</span>
            <span class="flex items-center gap-1"><span class="w-3 h-2 bg-blue-500 rounded-sm inline-block"></span> ออก</span>
            <span class="flex items-center gap-1"><span class="w-3 h-2 bg-red-500 rounded-sm inline-block"></span> ปฏิเสธ</span>
        </div>
    </div>
    <div class="flex items-end gap-1 h-32" id="activityChart">
        <!-- JS fills bars -->
    </div>
    <div class="flex justify-between mt-1 text-[10px] text-gray-600 px-0.5">
        <span>00</span><span>03</span><span>06</span><span>09</span><span>12</span><span>15</span><span>18</span><span>21</span><span>23</span>
    </div>
</div>

</div><!-- /monitorWrap -->

<script>
let _camOutEnabled = false;
let _camInEnabled = false;

// ============================================================
// Clock
// ============================================================
function updateMonClock() {
    document.getElementById('monitorClock').textContent =
        new Date().toLocaleString('th-TH', { dateStyle: 'long', timeStyle: 'medium' });
}

// ============================================================
// Camera Config
// ============================================================
async function fetchCamConfig() {
    try {
        const data = await fetchAPI(FACE_SERVER + '/api/status');
        if (!data) throw new Error();
        updateCamUI(1, 'outside', data.camera_outside);
        updateCamUI(2, 'inside', data.camera_inside);
        updateCamStatus(data);
    } catch {
        setCamError(1, 'Face Server ออฟไลน์');
        setCamError(2, 'Face Server ออฟไลน์');
    }
}

function updateCamUI(num, side, cfg) {
    const dotEl = document.getElementById('cam' + num + 'Dot');
    const titleEl = document.getElementById('cam' + num + 'Title');
    const subEl = document.getElementById('cam' + num + 'Sub');
    const badgeEl = document.getElementById('cam' + num + 'Badge');
    const imgEl = document.getElementById('cam' + num + 'Stream');
    const phEl = document.getElementById('cam' + num + 'Placeholder');
    const label = side === 'outside' ? 'ด้านนอก' : 'ด้านใน';

    if (!cfg || cfg.id < 0) {
        titleEl.textContent = 'กล้อง' + label + 'ประตู';
        subEl.innerHTML = '<span class="text-yellow-400">ยังไม่ได้กำหนด</span> — <a href="settings.php" class="text-blue-400 underline">ตั้งค่า</a>';
        dotEl.className = 'w-3 h-3 bg-yellow-400 rounded-full';
        badgeEl.textContent = 'ไม่ได้กำหนด';
        badgeEl.className = 'text-xs bg-yellow-500/20 text-yellow-400 px-2 py-1 rounded';
        imgEl.style.display = 'none'; phEl.style.display = '';
        phEl.querySelector('i').className = 'fas fa-camera-rotate text-4xl text-yellow-600 mb-2';
        phEl.querySelector('p').innerHTML = 'ยังไม่ได้กำหนดกล้อง';
        if (side === 'outside') _camOutEnabled = false; else _camInEnabled = false;
        return;
    }

    const devName = cfg.device_name || '/dev/video' + cfg.id;
    titleEl.textContent = 'กล้อง' + label + 'ประตู';
    subEl.textContent = devName + ' (video' + cfg.id + ')';

    if (cfg.active && cfg.has_frame) {
        dotEl.className = 'w-3 h-3 bg-green-400 pulse-dot rounded-full';
        badgeEl.textContent = 'LIVE'; badgeEl.className = 'text-xs bg-green-500/20 text-green-400 px-2 py-1 rounded';
        if (side === 'outside') _camOutEnabled = true; else _camInEnabled = true;
    } else {
        dotEl.className = 'w-3 h-3 bg-red-400 rounded-full';
        badgeEl.textContent = 'OFFLINE'; badgeEl.className = 'text-xs bg-red-500/20 text-red-400 px-2 py-1 rounded';
        imgEl.style.display = 'none'; phEl.style.display = '';
        phEl.querySelector('i').className = 'fas fa-video-slash text-4xl text-gray-600 mb-2';
        phEl.querySelector('p').textContent = 'กล้องออฟไลน์';
        if (side === 'outside') _camOutEnabled = false; else _camInEnabled = false;
    }
}

function setCamError(num, msg) {
    document.getElementById('cam' + num + 'Dot').className = 'w-3 h-3 bg-red-400 rounded-full';
    const b = document.getElementById('cam' + num + 'Badge');
    b.textContent = 'ERROR'; b.className = 'text-xs bg-red-500/20 text-red-400 px-2 py-1 rounded';
    document.getElementById('cam' + num + 'Stream').style.display = 'none';
    const ph = document.getElementById('cam' + num + 'Placeholder');
    ph.style.display = '';
    ph.querySelector('i').className = 'fas fa-exclamation-triangle text-4xl text-red-600 mb-2';
    ph.querySelector('p').textContent = msg;
}

function updateCamStatus(data) {
    const co = data.camera_outside, ci = data.camera_inside;
    document.getElementById('cam1Motion').textContent = 'Motion: ' + (co?.motion ? 'DETECTED' : 'None');
    document.getElementById('cam2Motion').textContent = 'Motion: ' + (ci?.motion ? 'DETECTED' : 'None');
    document.getElementById('cam1Faces').textContent = 'Faces: ' + (co?.faces?.length || 0);
    document.getElementById('cam2Faces').textContent = 'Faces: ' + (ci?.faces?.length || 0);
    const now = new Date().toLocaleTimeString('th-TH');
    document.getElementById('cam1Time').textContent = now;
    document.getElementById('cam2Time').textContent = now;

    // Faces list
    const allFaces = [...(co?.faces || []), ...(ci?.faces || [])];
    const fd = document.getElementById('detectedFaces');
    if (allFaces.length > 0) {
        fd.innerHTML = allFaces.map(([name, conf]) => `
            <div class="flex items-center justify-between p-2 bg-white/5 rounded-lg">
                <div class="flex items-center gap-2">
                    <i class="fas ${name === 'Unknown' ? 'fa-user-xmark text-red-400' : 'fa-user-check text-green-400'} text-xs"></i>
                    <span class="text-sm">${esc(name)}</span>
                </div>
                <span class="text-xs text-gray-400">${parseFloat(conf)?.toFixed(0) || 0}%</span>
            </div>`).join('');
    } else {
        fd.innerHTML = '<p class="text-gray-500 text-sm text-center py-4"><i class="fas fa-eye-slash text-xl mb-1"></i><br>ไม่มีใบหน้า</p>';
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
// ESP32: Door + PIR (real-time)
// ============================================================
async function checkESP32() {
    try {
        const d = await fetchAPI(FACE_SERVER + '/api/esp32/health');
        if (d && d.online) {
            // Door
            if (d.door === 'locked') {
                document.getElementById('monDoorIcon').className = 'w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3 bg-red-500/20';
                document.getElementById('monDoorIconInner').className = 'fas fa-lock text-2xl text-red-400';
                document.getElementById('monDoorText').textContent = 'ประตูล็อก';
                document.getElementById('monDoorText').className = 'text-lg font-bold text-red-400';
            } else {
                document.getElementById('monDoorIcon').className = 'w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3 bg-green-500/20';
                document.getElementById('monDoorIconInner').className = 'fas fa-lock-open text-2xl text-green-400';
                document.getElementById('monDoorText').textContent = 'ประตูเปิด';
                document.getElementById('monDoorText').className = 'text-lg font-bold text-green-400';
            }
            document.getElementById('monDoorSub').textContent = 'ESP32: ' + (d.ip || '-') + ' | RSSI: ' + (d.rssi || '-') + 'dBm';

            // PIR
            document.getElementById('pir1Dot').className = d.pir_outside ? 'w-3 h-3 bg-yellow-400 pulse-dot rounded-full' : 'w-3 h-3 bg-gray-500 rounded-full';
            document.getElementById('pir1Label').textContent = d.pir_outside ? 'ตรวจจับ!' : 'ปกติ';
            document.getElementById('pir1Label').className = d.pir_outside ? 'text-xs text-yellow-400 font-medium' : 'text-xs text-gray-500';
            document.getElementById('pir2Dot').className = d.pir_inside ? 'w-3 h-3 bg-yellow-400 pulse-dot rounded-full' : 'w-3 h-3 bg-gray-500 rounded-full';
            document.getElementById('pir2Label').textContent = d.pir_inside ? 'ตรวจจับ!' : 'ปกติ';
            document.getElementById('pir2Label').className = d.pir_inside ? 'text-xs text-yellow-400 font-medium' : 'text-xs text-gray-500';
            document.getElementById('pirEspInfo').innerHTML = '<span class="text-green-400"><i class="fas fa-circle text-[6px] mr-1"></i>ESP32 Online</span>';
        } else {
            document.getElementById('monDoorSub').textContent = 'ESP32: ออฟไลน์';
            document.getElementById('pir1Dot').className = 'w-3 h-3 bg-red-400 rounded-full';
            document.getElementById('pir2Dot').className = 'w-3 h-3 bg-red-400 rounded-full';
            document.getElementById('pir1Label').textContent = '-';
            document.getElementById('pir2Label').textContent = '-';
            document.getElementById('pirEspInfo').innerHTML = '<span class="text-red-400"><i class="fas fa-circle text-[6px] mr-1"></i>ESP32 Offline</span>';
        }
    } catch {
        document.getElementById('monDoorSub').textContent = 'ไม่สามารถเชื่อมต่อ';
        document.getElementById('pirEspInfo').innerHTML = '<span class="text-red-400">ไม่สามารถเชื่อมต่อ</span>';
    }
}

async function monUnlock() {
    await postAPI(FACE_SERVER + '/api/door/unlock');
    checkESP32();
}
async function monLock() {
    await postAPI(FACE_SERVER + '/api/door/lock');
    checkESP32();
}

// ============================================================
// Last Access Log (5 รายการล่าสุด)
// ============================================================
async function loadLastAccess() {
    try {
        const res = await fetchAPI('api/access_logs.php?limit=5&page=1');
        const el = document.getElementById('lastAccessList');
        if (!res?.data?.length) {
            el.innerHTML = '<p class="text-gray-500 text-sm text-center py-4">ยังไม่มีข้อมูล</p>';
            return;
        }
        el.innerHTML = res.data.map(log => {
            const name = log.first_name ? esc(log.first_name) : '<span class="text-red-400">ไม่รู้จัก</span>';
            const dir = log.direction === 'IN'
                ? '<span class="text-green-400 text-[10px]"><i class="fas fa-arrow-right"></i> เข้า</span>'
                : '<span class="text-blue-400 text-[10px]"><i class="fas fa-arrow-left"></i> ออก</span>';
            const time = new Date(log.created_at).toLocaleTimeString('th-TH', {hour:'2-digit',minute:'2-digit'});
            const auth = log.is_authorized == 1
                ? '<i class="fas fa-lock-open text-green-400 text-[10px]"></i>'
                : '<i class="fas fa-lock text-red-400 text-[10px]"></i>';
            return `<div class="flex items-center justify-between p-2 bg-white/5 rounded-lg text-xs">
                <div class="flex items-center gap-2">${auth} <span>${name}</span></div>
                <div class="flex items-center gap-2">${dir} <span class="text-gray-500">${time}</span></div>
            </div>`;
        }).join('');
    } catch {
        document.getElementById('lastAccessList').innerHTML = '<p class="text-gray-500 text-sm text-center">โหลดไม่ได้</p>';
    }
}

// ============================================================
// Activity Chart (เข้า-ออก รายชั่วโมง วันนี้)
// ============================================================
async function loadActivityChart() {
    const chart = document.getElementById('activityChart');
    try {
        const today = new Date().toISOString().split('T')[0];
        const res = await fetchAPI('api/access_logs.php?limit=500&page=1&date_from=' + today + '&date_to=' + today);
        if (!res?.data) return;

        // Count per hour
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
            const inH = Math.max(2, (hoursIn[h] / maxVal) * 100);
            const outH = Math.max(0, (hoursOut[h] / maxVal) * 100);
            const denH = Math.max(0, (hoursDenied[h] / maxVal) * 100);
            const total = hoursIn[h] + hoursOut[h] + hoursDenied[h];
            const isCurrent = h === currentHour;
            const opacity = h <= currentHour ? '' : 'opacity-30';

            chart.innerHTML += `
                <div class="flex-1 flex flex-col items-center justify-end gap-[1px] group relative ${opacity}" title="${h}:00 — เข้า:${hoursIn[h]} ออก:${hoursOut[h]} ปฏิเสธ:${hoursDenied[h]}">
                    ${denH > 0 ? `<div class="w-full bg-red-500 rounded-t-sm" style="height:${denH}%"></div>` : ''}
                    ${outH > 0 ? `<div class="w-full bg-blue-500 rounded-t-sm" style="height:${outH}%"></div>` : ''}
                    <div class="w-full ${isCurrent ? 'bg-green-400' : 'bg-green-500'} rounded-t-sm ${isCurrent ? 'ring-1 ring-green-400/50' : ''}" style="height:${total > 0 ? inH : 2}%"></div>
                    ${isCurrent ? '<div class="w-1 h-1 bg-green-400 rounded-full mt-1 pulse-dot"></div>' : ''}
                    <div class="absolute bottom-full mb-2 bg-gray-900 text-white text-[10px] px-2 py-1 rounded shadow-lg opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-10">
                        ${h}:00 — เข้า:${hoursIn[h]} ออก:${hoursOut[h]} ปฏิเสธ:${hoursDenied[h]}
                    </div>
                </div>`;
        }
    } catch {
        chart.innerHTML = '<p class="text-gray-500 text-sm w-full text-center">โหลดกราฟไม่ได้</p>';
    }
}

// ============================================================
// Fullscreen Monitor Mode
// ============================================================
let _isFullscreen = false;

function toggleFullscreen() {
    _isFullscreen = !_isFullscreen;
    const body = document.body;

    if (_isFullscreen) {
        body.classList.add('fs-mode');
        // Move monitorWrap + fsBar to body level for proper fixed positioning
        document.getElementById('fsBar').style.display = '';

        // Add glow + scanline to camera cards
        document.querySelectorAll('.stream-container').forEach(el => {
            el.classList.add('scanline');
        });
        document.querySelectorAll('#monitorWrap .glass').forEach(el => {
            el.classList.add('fs-glow', 'fs-cam-border');
        });

        // Try native fullscreen
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen().catch(() => {});
        }
    } else {
        body.classList.remove('fs-mode');
        document.getElementById('fsBar').style.display = 'none';

        document.querySelectorAll('.stream-container').forEach(el => {
            el.classList.remove('scanline');
        });
        document.querySelectorAll('#monitorWrap .glass').forEach(el => {
            el.classList.remove('fs-glow', 'fs-cam-border');
        });

        if (document.exitFullscreen && document.fullscreenElement) {
            document.exitFullscreen().catch(() => {});
        }
    }
}

// ESC key to exit fullscreen
document.addEventListener('fullscreenchange', () => {
    if (!document.fullscreenElement && _isFullscreen) {
        toggleFullscreen();
    }
});

// Update fullscreen clock
function updateFsClock() {
    const el = document.getElementById('fsClock');
    if (el) el.textContent = new Date().toLocaleString('th-TH', { dateStyle: 'long', timeStyle: 'medium' });
}

// ============================================================
// Init
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    updateMonClock();
    fetchCamConfig();
    checkESP32();
    loadLastAccess();
    loadActivityChart();

    setTimeout(refreshSnapshots, 2000);
    setInterval(refreshSnapshots, 3000);
    setInterval(fetchCamConfig, 10000);
    setInterval(checkESP32, 3000);
    setInterval(loadLastAccess, 10000);
    setInterval(loadActivityChart, 30000);
    setInterval(updateMonClock, 1000);
    setInterval(updateFsClock, 1000);

    // Auto fullscreen ถ้ามี ?fs=1 ใน URL
    if (new URLSearchParams(window.location.search).get('fs') === '1') {
        setTimeout(toggleFullscreen, 500);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
