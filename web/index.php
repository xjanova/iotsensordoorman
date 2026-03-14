<?php $pageTitle = 'Dashboard - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<?php
// ดึงสถานะทั้งหมดจาก DB (ไม่พึ่ง face_server API)
$db = getDB();

// สถานะระบบ (เช็ค heartbeat ว่า stale ไหม — ถ้าเกิน 60 วิ ถือว่า offline)
$sysStatus = [];
foreach ($db->query("SELECT component, status, last_heartbeat FROM system_status")->fetchAll() as $row) {
    $isStale = $row['last_heartbeat'] && (strtotime('now') - strtotime($row['last_heartbeat'])) > 60;
    $sysStatus[$row['component']] = $isStale ? 'OFFLINE' : $row['status'];
}
$cam1Online = ($sysStatus['camera_outside'] ?? '') === 'ONLINE';
$cam2Online = ($sysStatus['camera_inside'] ?? '') === 'ONLINE';
$faceServerOnline = ($sysStatus['face_server'] ?? '') === 'ONLINE';
$esp32Online = ($sysStatus['esp32'] ?? '') === 'ONLINE';

// สถิติวันนี้
$stmt = $db->query("SELECT COUNT(DISTINCT employee_id) as c FROM access_logs WHERE direction='IN' AND DATE(created_at) = CURDATE() AND is_authorized = 1");
$todayIn = $stmt->fetch()['c'] ?? 0;

$stmt = $db->query("SELECT COUNT(DISTINCT employee_id) as c FROM access_logs WHERE direction='OUT' AND DATE(created_at) = CURDATE() AND is_authorized = 1");
$todayOut = $stmt->fetch()['c'] ?? 0;

$currentlyInside = max(0, $todayIn - $todayOut);

$stmt = $db->query("SELECT COUNT(*) as c FROM anomaly_alerts WHERE is_resolved = 0");
$unresolvedAlerts = $stmt->fetch()['c'] ?? 0;

// ประวัติล่าสุด
$stmt = $db->query("SELECT al.*, e.first_name, e.last_name, e.emp_code
    FROM access_logs al LEFT JOIN employees e ON al.employee_id = e.id
    ORDER BY al.created_at DESC LIMIT 10");
$recentLogs = $stmt->fetchAll();
?>

<!-- Page Header -->
<div class="flex justify-between items-center mb-8">
    <div>
        <h2 class="text-2xl font-bold">Dashboard</h2>
        <p class="text-gray-400 text-sm mt-1">ภาพรวมระบบควบคุมการเข้า-ออกห้องสโตร์</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="text-sm text-gray-400" id="currentTime"></span>
        <button onclick="unlockDoor()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition">
            <i class="fas fa-lock-open"></i> เปิดประตู
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="stat-card glass rounded-2xl p-6 card-hover">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-400 text-sm">เข้างานวันนี้</p>
                <p class="text-3xl font-bold text-white mt-2" id="statIn"><?= $todayIn ?></p>
            </div>
            <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-arrow-right-to-bracket text-green-400 text-xl"></i>
            </div>
        </div>
        <p class="text-xs text-green-400 mt-3"><i class="fas fa-arrow-up mr-1"></i> เข้าห้องสโตร์</p>
    </div>

    <div class="stat-card glass rounded-2xl p-6 card-hover">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-400 text-sm">ออกงานวันนี้</p>
                <p class="text-3xl font-bold text-white mt-2" id="statOut"><?= $todayOut ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-arrow-right-from-bracket text-blue-400 text-xl"></i>
            </div>
        </div>
        <p class="text-xs text-blue-400 mt-3"><i class="fas fa-arrow-down mr-1"></i> ออกจากห้องสโตร์</p>
    </div>

    <div class="stat-card glass rounded-2xl p-6 card-hover">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-400 text-sm">อยู่ในพื้นที่</p>
                <p class="text-3xl font-bold text-white mt-2" id="statInside"><?= $currentlyInside ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-person text-purple-400 text-xl"></i>
            </div>
        </div>
        <p class="text-xs text-purple-400 mt-3"><i class="fas fa-location-dot mr-1"></i> คนในห้องสโตร์ตอนนี้</p>
    </div>

    <div class="stat-card glass rounded-2xl p-6 card-hover">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-400 text-sm">แจ้งเตือน</p>
                <p class="text-3xl font-bold text-white mt-2" id="statAlerts"><?= $unresolvedAlerts ?></p>
            </div>
            <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-bell text-red-400 text-xl"></i>
            </div>
        </div>
        <p class="text-xs text-red-400 mt-3"><i class="fas fa-exclamation-triangle mr-1"></i> ยังไม่ได้ดำเนินการ</p>
    </div>
</div>

<!-- Camera Feeds + Door Status -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Camera Outside -->
    <div class="glass rounded-2xl overflow-hidden card-hover">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 <?= $cam1Online ? 'bg-green-400 pulse-dot' : 'bg-red-400' ?> rounded-full" id="camOutDot"></span>
                <span class="font-medium">กล้องด้านนอก</span>
            </div>
            <span class="text-xs <?= $cam1Online ? 'text-green-400' : 'text-red-400' ?>"><?= $cam1Online ? 'ONLINE' : 'OFFLINE' ?></span>
        </div>
        <div class="stream-container aspect-video">
            <img id="streamOutside" src="" alt="Camera Outside" class="w-full" style="display:none">
            <div id="streamOutPlaceholder" class="absolute inset-0 flex items-center justify-center bg-gray-800">
                <div class="text-center text-gray-500">
                    <i class="fas fa-video-slash text-3xl mb-2"></i>
                    <p class="text-sm"><?= $cam1Online ? 'กำลังโหลด...' : 'กล้องออฟไลน์' ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Camera Inside -->
    <div class="glass rounded-2xl overflow-hidden card-hover">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 <?= $cam2Online ? 'bg-green-400 pulse-dot' : 'bg-red-400' ?> rounded-full" id="camInDot"></span>
                <span class="font-medium">กล้องด้านใน</span>
            </div>
            <span class="text-xs <?= $cam2Online ? 'text-green-400' : 'text-red-400' ?>"><?= $cam2Online ? 'ONLINE' : 'OFFLINE' ?></span>
        </div>
        <div class="stream-container aspect-video">
            <img id="streamInside" src="" alt="Camera Inside" class="w-full" style="display:none">
            <div id="streamInPlaceholder" class="absolute inset-0 flex items-center justify-center bg-gray-800">
                <div class="text-center text-gray-500">
                    <i class="fas fa-video-slash text-3xl mb-2"></i>
                    <p class="text-sm"><?= $cam2Online ? 'กำลังโหลด...' : 'กล้องออฟไลน์' ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Door Status & Controls -->
    <div class="glass rounded-2xl p-6 card-hover">
        <h3 class="font-medium mb-4 flex items-center gap-2">
            <i class="fas fa-door-closed text-blue-400"></i> สถานะประตู
        </h3>
        <div class="text-center py-6">
            <div class="w-24 h-24 mx-auto rounded-full flex items-center justify-center mb-4 bg-red-500/20" id="doorIcon">
                <i class="fas fa-lock text-4xl text-red-400" id="doorIconInner"></i>
            </div>
            <p class="text-xl font-bold text-red-400" id="doorStatusText">ประตูล็อก</p>
            <p class="text-sm text-gray-400 mt-1" id="doorStatusSub"></p>
        </div>
        <div class="space-y-3 mt-4">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-400">Face Server</span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 <?= $faceServerOnline ? 'bg-green-400' : 'bg-red-400' ?> rounded-full"></span>
                    <?= $faceServerOnline ? 'Online' : 'Offline' ?>
                </span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-400">ESP32</span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 <?= $esp32Online ? 'bg-green-400' : 'bg-red-400' ?> rounded-full"></span>
                    <?= $esp32Online ? 'Online' : 'Offline' ?>
                </span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-400">กล้องนอก</span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 <?= $cam1Online ? 'bg-green-400' : 'bg-red-400' ?> rounded-full"></span>
                    <?= $cam1Online ? 'Online' : 'Offline' ?>
                </span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-400">กล้องใน</span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 <?= $cam2Online ? 'bg-green-400' : 'bg-red-400' ?> rounded-full"></span>
                    <?= $cam2Online ? 'Online' : 'Offline' ?>
                </span>
            </div>
        </div>
        <div class="flex gap-2 mt-6">
            <button onclick="unlockDoor()" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg text-sm transition">
                <i class="fas fa-lock-open mr-1"></i> ปลดล็อก
            </button>
            <button onclick="lockDoor()" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg text-sm transition">
                <i class="fas fa-lock mr-1"></i> ล็อก
            </button>
        </div>
    </div>
</div>

<!-- Recent Logs -->
<div class="glass rounded-2xl p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-medium flex items-center gap-2">
            <i class="fas fa-clock-rotate-left text-blue-400"></i> ประวัติล่าสุด
        </h3>
        <a href="logs.php" class="text-blue-400 text-sm hover:underline">ดูทั้งหมด &rarr;</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-gray-400 border-b border-white/10">
                    <th class="text-left py-3 px-2">เวลา</th>
                    <th class="text-left py-3 px-2">พนักงาน</th>
                    <th class="text-left py-3 px-2">ทิศทาง</th>
                    <th class="text-left py-3 px-2">ความมั่นใจ</th>
                    <th class="text-left py-3 px-2">สถานะ</th>
                </tr>
            </thead>
            <tbody id="recentLogs">
                <?php if (empty($recentLogs)): ?>
                <tr><td colspan="5" class="text-center text-gray-500 py-8">ไม่มีข้อมูล</td></tr>
                <?php else: ?>
                <?php foreach ($recentLogs as $log): ?>
                <tr class="border-b border-white/5 hover:bg-white/5">
                    <td class="py-3 px-2 text-gray-300"><?= date('H:i:s', strtotime($log['created_at'])) ?></td>
                    <td class="py-3 px-2"><?= $log['first_name'] ? esc($log['first_name']) . ' ' . esc($log['last_name']) : '<span class="text-red-400">ไม่รู้จัก</span>' ?></td>
                    <td class="py-3 px-2">
                        <?php if ($log['direction'] === 'IN'): ?>
                        <span class="bg-green-500/20 text-green-400 px-2 py-1 rounded text-xs">เข้า</span>
                        <?php else: ?>
                        <span class="bg-blue-500/20 text-blue-400 px-2 py-1 rounded text-xs">ออก</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-2">
                        <?php $conf = floatval($log['confidence'] ?? 0); ?>
                        <div class="flex items-center gap-2">
                            <div class="w-16 bg-gray-700 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full <?= $conf > 70 ? 'bg-green-400' : ($conf > 40 ? 'bg-yellow-400' : 'bg-red-400') ?>"
                                     style="width: <?= $conf ?>%"></div>
                            </div>
                            <span class="text-xs text-gray-400"><?= $conf ?>%</span>
                        </div>
                    </td>
                    <td class="py-3 px-2">
                        <?php if ($log['is_authorized']): ?>
                        <span class="text-green-400"><i class="fas fa-check-circle"></i></span>
                        <?php else: ?>
                        <span class="text-red-400"><i class="fas fa-times-circle"></i></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// ============================================================
// Dashboard Logic (ใช้ PHP render ข้อมูลหลัก, JS เฉพาะ snapshot + door + clock)
// ============================================================

// Camera snapshots (refresh ทุก 3 วินาที)
<?php if ($cam1Online || $cam2Online): ?>
function refreshDashboardSnapshots() {
    const ts = Date.now();
    <?php if ($cam1Online): ?>
    const outImg = new Image();
    outImg.onload = () => {
        document.getElementById('streamOutside').src = outImg.src;
        document.getElementById('streamOutside').style.display = '';
        document.getElementById('streamOutPlaceholder').style.display = 'none';
    };
    outImg.src = FACE_SERVER + '/api/snapshot/outside?t=' + ts;
    <?php endif; ?>

    <?php if ($cam2Online): ?>
    const inImg = new Image();
    inImg.onload = () => {
        document.getElementById('streamInside').src = inImg.src;
        document.getElementById('streamInside').style.display = '';
        document.getElementById('streamInPlaceholder').style.display = 'none';
    };
    inImg.src = FACE_SERVER + '/api/snapshot/inside?t=' + ts;
    <?php endif; ?>
}
refreshDashboardSnapshots();
setInterval(refreshDashboardSnapshots, 3000);
<?php endif; ?>

// Door controls
async function unlockDoor() {
    const data = await postAPI(FACE_SERVER + '/api/door/unlock');
    if (data?.success) {
        document.getElementById('doorIcon').className = 'w-24 h-24 mx-auto rounded-full flex items-center justify-center mb-4 bg-green-500/20';
        document.getElementById('doorIconInner').className = 'fas fa-lock-open text-4xl text-green-400';
        document.getElementById('doorStatusText').textContent = 'ประตูเปิด';
        document.getElementById('doorStatusText').className = 'text-xl font-bold text-green-400';
    }
}

async function lockDoor() {
    const data = await postAPI(FACE_SERVER + '/api/door/lock');
    if (data?.success) {
        document.getElementById('doorIcon').className = 'w-24 h-24 mx-auto rounded-full flex items-center justify-center mb-4 bg-red-500/20';
        document.getElementById('doorIconInner').className = 'fas fa-lock text-4xl text-red-400';
        document.getElementById('doorStatusText').textContent = 'ประตูล็อก';
        document.getElementById('doorStatusText').className = 'text-xl font-bold text-red-400';
    }
}

// Clock
function updateClock() {
    document.getElementById('currentTime').textContent =
        new Date().toLocaleString('th-TH', { dateStyle: 'long', timeStyle: 'medium' });
}
updateClock();
setInterval(updateClock, 1000);

// Auto-refresh page ทุก 30 วินาที เพื่ออัปเดตสถิติ/สถานะจาก DB
setTimeout(() => location.reload(), 30000);
</script>

<?php include 'includes/footer.php'; ?>
