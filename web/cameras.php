<?php $pageTitle = 'กล้องวงจรปิด - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h2 class="text-2xl font-bold">กล้องวงจรปิด</h2>
        <p class="text-gray-400 text-sm mt-1">ดูภาพสดจากกล้อง พร้อมระบบตรวจจับใบหน้าแบบ Real-time</p>
    </div>
    <a href="settings.php" class="text-sm text-blue-400 hover:underline flex items-center gap-1">
        <i class="fas fa-cog"></i> ตั้งค่ากล้อง
    </a>
</div>

<!-- Camera Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Camera Outside -->
    <div class="glass rounded-2xl overflow-hidden" id="camOutCard">
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
    <div class="glass rounded-2xl overflow-hidden" id="camInCard">
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

<!-- Sensor & Detection Info -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Motion Sensors -->
    <div class="glass rounded-2xl p-6">
        <h3 class="font-medium mb-4 flex items-center gap-2">
            <i class="fas fa-satellite-dish text-yellow-400"></i> เซ็นเซอร์ PIR
        </h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-yellow-500/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-arrow-right text-yellow-400"></i>
                    </div>
                    <div>
                        <p class="font-medium text-sm">ด้านนอก (PIR 1)</p>
                        <p class="text-xs text-gray-400">GPIO 27</p>
                    </div>
                </div>
                <span class="w-3 h-3 bg-gray-500 rounded-full" id="pir1Dot"></span>
            </div>
            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-arrow-left text-purple-400"></i>
                    </div>
                    <div>
                        <p class="font-medium text-sm">ด้านใน (PIR 2)</p>
                        <p class="text-xs text-gray-400">GPIO 26</p>
                    </div>
                </div>
                <span class="w-3 h-3 bg-gray-500 rounded-full" id="pir2Dot"></span>
            </div>
        </div>
    </div>

    <!-- Detection Status -->
    <div class="glass rounded-2xl p-6">
        <h3 class="font-medium mb-4 flex items-center gap-2">
            <i class="fas fa-face-smile text-blue-400"></i> การตรวจจับใบหน้า
        </h3>
        <div class="space-y-3" id="detectedFaces">
            <p class="text-gray-500 text-sm">ไม่มีใบหน้าที่ตรวจพบ</p>
        </div>
    </div>

    <!-- Anomaly Info -->
    <div class="glass rounded-2xl p-6">
        <h3 class="font-medium mb-4 flex items-center gap-2">
            <i class="fas fa-shield-halved text-red-400"></i> คอนเซปต์เพิ่มเติม
        </h3>
        <div class="space-y-3 text-sm text-gray-300">
            <div class="flex items-start gap-2">
                <i class="fas fa-check text-green-400 mt-1"></i>
                <span>เซ็นเซอร์ PIR 2 ตัว จับการเคลื่อนไหวทั้งนอกและในประตู</span>
            </div>
            <div class="flex items-start gap-2">
                <i class="fas fa-check text-green-400 mt-1"></i>
                <span>กล้อง 2 ตัว ถ่ายภาพทั้งขาเข้าและขาออก</span>
            </div>
            <div class="flex items-start gap-2">
                <i class="fas fa-check text-green-400 mt-1"></i>
                <span>ตรวจจับ Tailgating (คนแอบเข้าตามพร้อมกัน)</span>
            </div>
            <div class="flex items-start gap-2">
                <i class="fas fa-check text-green-400 mt-1"></i>
                <span>แจ้งเตือนใบหน้าไม่รู้จัก (No Face / Unknown)</span>
            </div>
            <div class="flex items-start gap-2">
                <i class="fas fa-check text-green-400 mt-1"></i>
                <span>บันทึก Snapshot ทุกครั้งที่มีการเข้า-ออก</span>
            </div>
        </div>
    </div>
</div>

<script>
let _camOutEnabled = false;
let _camInEnabled = false;

// ดึง config กล้องจาก face_server แล้วอัพเดท UI
async function fetchCamConfig() {
    try {
        const data = await fetchAPI(FACE_SERVER + '/api/status');
        if (!data) throw new Error();

        updateCamUI(1, 'outside', data.camera_outside);
        updateCamUI(2, 'inside', data.camera_inside);

        // อัพเดท motion/faces
        updateCamStatus(data);
    } catch {
        // Face server offline
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
        // ไม่ได้กำหนดกล้อง
        titleEl.textContent = 'กล้อง' + label + 'ประตู';
        subEl.innerHTML = '<span class="text-yellow-400">ยังไม่ได้กำหนดกล้อง</span> — <a href="settings.php" class="text-blue-400 underline">ไปตั้งค่า</a>';
        dotEl.className = 'w-3 h-3 bg-yellow-400 rounded-full';
        badgeEl.textContent = 'ไม่ได้กำหนด';
        badgeEl.className = 'text-xs bg-yellow-500/20 text-yellow-400 px-2 py-1 rounded';
        imgEl.style.display = 'none';
        phEl.style.display = '';
        phEl.querySelector('i').className = 'fas fa-camera-rotate text-4xl text-yellow-600 mb-2';
        phEl.querySelector('p').innerHTML = 'ยังไม่ได้กำหนดกล้อง<br><a href="settings.php" class="text-blue-400 underline text-xs">ไปตั้งค่า</a>';
        if (side === 'outside') _camOutEnabled = false;
        else _camInEnabled = false;
        return;
    }

    // กล้องกำหนดแล้ว
    const devName = cfg.device_name || '/dev/video' + cfg.id;
    titleEl.textContent = 'กล้อง' + label + 'ประตู';
    subEl.textContent = devName + ' (video' + cfg.id + ')';

    if (cfg.active && cfg.has_frame) {
        dotEl.className = 'w-3 h-3 bg-green-400 pulse-dot rounded-full';
        badgeEl.textContent = 'LIVE';
        badgeEl.className = 'text-xs bg-green-500/20 text-green-400 px-2 py-1 rounded';
        if (side === 'outside') _camOutEnabled = true;
        else _camInEnabled = true;
    } else {
        dotEl.className = 'w-3 h-3 bg-red-400 rounded-full';
        badgeEl.textContent = 'OFFLINE';
        badgeEl.className = 'text-xs bg-red-500/20 text-red-400 px-2 py-1 rounded';
        imgEl.style.display = 'none';
        phEl.style.display = '';
        phEl.querySelector('i').className = 'fas fa-video-slash text-4xl text-gray-600 mb-2';
        phEl.querySelector('p').textContent = 'กล้องออฟไลน์';
        if (side === 'outside') _camOutEnabled = false;
        else _camInEnabled = false;
    }
}

function setCamError(num, msg) {
    const dotEl = document.getElementById('cam' + num + 'Dot');
    const badgeEl = document.getElementById('cam' + num + 'Badge');
    const phEl = document.getElementById('cam' + num + 'Placeholder');
    dotEl.className = 'w-3 h-3 bg-red-400 rounded-full';
    badgeEl.textContent = 'ERROR';
    badgeEl.className = 'text-xs bg-red-500/20 text-red-400 px-2 py-1 rounded';
    document.getElementById('cam' + num + 'Stream').style.display = 'none';
    phEl.style.display = '';
    phEl.querySelector('i').className = 'fas fa-exclamation-triangle text-4xl text-red-600 mb-2';
    phEl.querySelector('p').textContent = msg;
}

function updateCamStatus(data) {
    const co = data.camera_outside;
    const ci = data.camera_inside;

    document.getElementById('cam1Motion').textContent = 'Motion: ' + (co?.motion ? 'DETECTED' : 'None');
    document.getElementById('cam2Motion').textContent = 'Motion: ' + (ci?.motion ? 'DETECTED' : 'None');
    document.getElementById('cam1Faces').textContent = 'Faces: ' + (co?.faces?.length || 0);
    document.getElementById('cam2Faces').textContent = 'Faces: ' + (ci?.faces?.length || 0);

    const now = new Date().toLocaleTimeString('th-TH');
    document.getElementById('cam1Time').textContent = now;
    document.getElementById('cam2Time').textContent = now;

    // Detected faces list
    const allFaces = [...(co?.faces || []), ...(ci?.faces || [])];
    const facesDiv = document.getElementById('detectedFaces');
    if (allFaces.length > 0) {
        facesDiv.innerHTML = allFaces.map(([name, conf]) => `
            <div class="flex items-center justify-between p-2 bg-white/5 rounded-lg">
                <div class="flex items-center gap-2">
                    <i class="fas ${name === 'Unknown' ? 'fa-user-xmark text-red-400' : 'fa-user-check text-green-400'}"></i>
                    <span class="text-sm">${esc(name)}</span>
                </div>
                <span class="text-xs text-gray-400">${parseFloat(conf) || 0}%</span>
            </div>
        `).join('');
    } else {
        facesDiv.innerHTML = '<p class="text-gray-500 text-sm">ไม่มีใบหน้าที่ตรวจพบ</p>';
    }
}

// Refresh snapshot เฉพาะกล้องที่เปิดอยู่
function refreshSnapshots() {
    const ts = Date.now();
    if (_camOutEnabled) {
        const img1 = document.getElementById('cam1Stream');
        const ph1 = document.getElementById('cam1Placeholder');
        const tmpImg = new Image();
        tmpImg.onload = () => { img1.src = tmpImg.src; img1.style.display = ''; ph1.style.display = 'none'; };
        tmpImg.onerror = () => { img1.style.display = 'none'; ph1.style.display = ''; };
        tmpImg.src = FACE_SERVER + '/api/snapshot/outside?t=' + ts;
    }
    if (_camInEnabled) {
        const img2 = document.getElementById('cam2Stream');
        const ph2 = document.getElementById('cam2Placeholder');
        const tmpImg = new Image();
        tmpImg.onload = () => { img2.src = tmpImg.src; img2.style.display = ''; ph2.style.display = 'none'; };
        tmpImg.onerror = () => { img2.style.display = 'none'; ph2.style.display = ''; };
        tmpImg.src = FACE_SERVER + '/api/snapshot/inside?t=' + ts;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    fetchCamConfig();
    setTimeout(refreshSnapshots, 2000);
    setInterval(refreshSnapshots, 3000);
    setInterval(fetchCamConfig, 10000);
});
</script>

<?php include 'includes/footer.php'; ?>
