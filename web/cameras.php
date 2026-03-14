<?php $pageTitle = 'กล้องวงจรปิด - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<?php
// ดึงสถานะกล้องจาก DB
$db = getDB();
$camStatus = [];
foreach ($db->query("SELECT component, status FROM system_status WHERE component LIKE 'camera_%'")->fetchAll() as $row) {
    $camStatus[$row['component']] = $row['status'];
}
$cam1Online = ($camStatus['camera_outside'] ?? '') === 'ONLINE';
$cam2Online = ($camStatus['camera_inside'] ?? '') === 'ONLINE';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h2 class="text-2xl font-bold">กล้องวงจรปิด</h2>
        <p class="text-gray-400 text-sm mt-1">ดูภาพสดจากกล้อง พร้อมระบบตรวจจับใบหน้าแบบ Real-time</p>
    </div>
</div>

<!-- Camera Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Camera 1: Outside -->
    <div class="glass rounded-2xl overflow-hidden">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 <?= $cam1Online ? 'bg-green-400 pulse-dot' : 'bg-gray-500' ?> rounded-full"></span>
                <div>
                    <h3 class="font-medium">กล้องด้านนอกประตู</h3>
                    <p class="text-xs text-gray-400">Camera 1 - <?= $cam1Online ? 'ออนไลน์' : 'ออฟไลน์' ?></p>
                </div>
            </div>
            <?php if ($cam1Online): ?>
            <span class="text-xs bg-green-500/20 text-green-400 px-2 py-1 rounded">LIVE</span>
            <?php else: ?>
            <span class="text-xs bg-gray-500/20 text-gray-400 px-2 py-1 rounded">OFFLINE</span>
            <?php endif; ?>
        </div>
        <div class="stream-container aspect-video relative">
            <?php if ($cam1Online): ?>
            <img src="<?= FACE_SERVER_URL ?>/api/snapshot/outside" id="cam1Stream" class="w-full" alt="Camera 1">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center bg-black/50 aspect-video">
                <div class="text-center">
                    <i class="fas fa-video-slash text-4xl text-gray-600 mb-2"></i>
                    <p class="text-gray-500 text-sm">กล้องด้านนอกออฟไลน์</p>
                </div>
            </div>
            <?php endif; ?>
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

    <!-- Camera 2: Inside -->
    <div class="glass rounded-2xl overflow-hidden">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 <?= $cam2Online ? 'bg-green-400 pulse-dot' : 'bg-gray-500' ?> rounded-full"></span>
                <div>
                    <h3 class="font-medium">กล้องด้านในประตู</h3>
                    <p class="text-xs text-gray-400">Camera 2 - <?= $cam2Online ? 'ออนไลน์' : 'ออฟไลน์' ?></p>
                </div>
            </div>
            <?php if ($cam2Online): ?>
            <span class="text-xs bg-green-500/20 text-green-400 px-2 py-1 rounded">LIVE</span>
            <?php else: ?>
            <span class="text-xs bg-gray-500/20 text-gray-400 px-2 py-1 rounded">OFFLINE</span>
            <?php endif; ?>
        </div>
        <div class="stream-container aspect-video relative">
            <?php if ($cam2Online): ?>
            <img src="<?= FACE_SERVER_URL ?>/api/snapshot/inside" id="cam2Stream" class="w-full" alt="Camera 2">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center bg-black/50 aspect-video">
                <div class="text-center">
                    <i class="fas fa-video-slash text-4xl text-gray-600 mb-2"></i>
                    <p class="text-gray-500 text-sm">กล้องด้านในออฟไลน์</p>
                </div>
            </div>
            <?php endif; ?>
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
const CAM_SERVER = '<?= FACE_SERVER_URL ?>';

// Refresh snapshot ทุก 3 วินาที (ไม่ใช้ MJPEG stream เพื่อประหยัด resource Pi)
function refreshSnapshots() {
    const ts = Date.now();
    const cam1 = document.getElementById('cam1Stream');
    const cam2 = document.getElementById('cam2Stream');
    if (cam1) cam1.src = CAM_SERVER + '/api/snapshot/outside?t=' + ts;
    if (cam2) cam2.src = CAM_SERVER + '/api/snapshot/inside?t=' + ts;
}

async function updateCameraStatus() {
    try {
        const data = await fetchAPI(CAM_SERVER + '/api/status');
        if (!data) return;

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
        }
    } catch(e) {}
}

// Snapshot refresh ทุก 3 วินาที, status ทุก 10 วินาที
document.addEventListener('DOMContentLoaded', () => {
    refreshSnapshots();
    updateCameraStatus();
    setInterval(refreshSnapshots, 3000);
    setInterval(updateCameraStatus, 10000);
});
</script>

<?php include 'includes/footer.php'; ?>
