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
            <span class="text-xs <?= $cam1Online ? 'text-green-400' : 'text-red-400' ?>" id="camOutStatus"><?= $cam1Online ? 'ONLINE' : 'OFFLINE' ?></span>
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
            <span class="text-xs <?= $cam2Online ? 'text-green-400' : 'text-red-400' ?>" id="camInStatus"><?= $cam2Online ? 'ONLINE' : 'OFFLINE' ?></span>
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

<!-- Hardware Health Monitor -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Raspberry Pi Monitor (2 cols) -->
    <div class="lg:col-span-2 glass rounded-2xl p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-medium flex items-center gap-2">
                <i class="fas fa-microchip text-green-400"></i> Raspberry Pi
            </h3>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500" id="piUptime">-</span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-700 text-gray-400" id="piStatusBadge">OFFLINE</span>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <!-- CPU Temperature -->
            <div class="text-center">
                <div class="relative w-24 h-24 mx-auto mb-2">
                    <svg class="w-24 h-24 -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="8"/>
                        <circle id="tempRing" cx="60" cy="60" r="52" fill="none" stroke="#ef4444" stroke-width="8"
                                stroke-dasharray="327" stroke-dashoffset="327" stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease, stroke 0.5s ease"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-xl font-bold" id="piTemp">-</span>
                        <span class="text-[10px] text-gray-400">°C</span>
                    </div>
                </div>
                <p class="text-xs text-gray-400"><i class="fas fa-temperature-half text-red-400 mr-1"></i>อุณหภูมิ</p>
            </div>

            <!-- CPU Usage -->
            <div class="text-center">
                <div class="relative w-24 h-24 mx-auto mb-2">
                    <svg class="w-24 h-24 -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="8"/>
                        <circle id="cpuRing" cx="60" cy="60" r="52" fill="none" stroke="#3b82f6" stroke-width="8"
                                stroke-dasharray="327" stroke-dashoffset="327" stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease, stroke 0.5s ease"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-xl font-bold" id="piCpu">-</span>
                        <span class="text-[10px] text-gray-400">%</span>
                    </div>
                </div>
                <p class="text-xs text-gray-400"><i class="fas fa-gauge-high text-blue-400 mr-1"></i>CPU</p>
            </div>

            <!-- RAM Usage -->
            <div class="text-center">
                <div class="relative w-24 h-24 mx-auto mb-2">
                    <svg class="w-24 h-24 -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="8"/>
                        <circle id="ramRing" cx="60" cy="60" r="52" fill="none" stroke="#a855f7" stroke-width="8"
                                stroke-dasharray="327" stroke-dashoffset="327" stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease, stroke 0.5s ease"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-xl font-bold" id="piRam">-</span>
                        <span class="text-[10px] text-gray-400">%</span>
                    </div>
                </div>
                <p class="text-xs text-gray-400"><i class="fas fa-memory text-purple-400 mr-1"></i>RAM</p>
                <p class="text-[10px] text-gray-500 mt-0.5" id="piRamDetail">-</p>
            </div>

            <!-- Disk Usage -->
            <div class="text-center">
                <div class="relative w-24 h-24 mx-auto mb-2">
                    <svg class="w-24 h-24 -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="8"/>
                        <circle id="diskRing" cx="60" cy="60" r="52" fill="none" stroke="#f59e0b" stroke-width="8"
                                stroke-dasharray="327" stroke-dashoffset="327" stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease, stroke 0.5s ease"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-xl font-bold" id="piDisk">-</span>
                        <span class="text-[10px] text-gray-400">%</span>
                    </div>
                </div>
                <p class="text-xs text-gray-400"><i class="fas fa-hard-drive text-yellow-400 mr-1"></i>Disk</p>
            </div>
        </div>
    </div>

    <!-- ESP32 Monitor (1 col) -->
    <div class="glass rounded-2xl p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-medium flex items-center gap-2">
                <i class="fas fa-wifi text-cyan-400"></i> ESP32
            </h3>
            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-700 text-gray-400" id="espStatusBadge">OFFLINE</span>
        </div>
        <div class="space-y-4">
            <!-- WiFi Signal Gauge -->
            <div class="text-center">
                <div class="relative w-24 h-24 mx-auto mb-2">
                    <svg class="w-24 h-24 -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="8"/>
                        <circle id="wifiRing" cx="60" cy="60" r="52" fill="none" stroke="#06b6d4" stroke-width="8"
                                stroke-dasharray="327" stroke-dashoffset="327" stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease, stroke 0.5s ease"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-xl font-bold" id="espRssi">-</span>
                        <span class="text-[10px] text-gray-400">dBm</span>
                    </div>
                </div>
                <p class="text-xs text-gray-400"><i class="fas fa-signal text-cyan-400 mr-1"></i>WiFi Signal</p>
            </div>

            <!-- ESP32 Info -->
            <div class="space-y-2 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-gray-400"><i class="fas fa-door-closed mr-2 w-4 text-center"></i>ประตู</span>
                    <span id="espDoor" class="text-gray-300">-</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-400"><i class="fas fa-clock mr-2 w-4 text-center"></i>Uptime</span>
                    <span id="espUptime" class="text-gray-300">-</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-400"><i class="fas fa-network-wired mr-2 w-4 text-center"></i>IP</span>
                    <span id="espIp" class="text-gray-300">-</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-400"><i class="fas fa-eye mr-2 w-4 text-center"></i>PIR นอก</span>
                    <span id="espPirOut" class="text-gray-300">-</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-400"><i class="fas fa-eye mr-2 w-4 text-center"></i>PIR ใน</span>
                    <span id="espPirIn" class="text-gray-300">-</span>
                </div>
            </div>
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

// Camera snapshots (refresh ทุก 5 วินาที) — ใช้ Image tag (ไม่ติด CORS)
function loadSnapshot(camId, imgId, placeholderId, dotId, statusId) {
    const img = new Image();
    const timeout = setTimeout(() => {
        img.src = '';
        setCamOffline(imgId, placeholderId, dotId, statusId);
    }, 5000);

    img.onload = () => {
        clearTimeout(timeout);
        document.getElementById(imgId).src = img.src;
        document.getElementById(imgId).style.display = '';
        document.getElementById(placeholderId).style.display = 'none';
        document.getElementById(dotId).className = 'w-2 h-2 bg-green-400 pulse-dot rounded-full';
        const st = document.getElementById(statusId);
        st.textContent = 'ONLINE';
        st.className = 'text-xs text-green-400';
    };
    img.onerror = () => {
        clearTimeout(timeout);
        setCamOffline(imgId, placeholderId, dotId, statusId);
    };
    img.src = FACE_SERVER + '/api/snapshot/' + camId + '?t=' + Date.now();
}

function setCamOffline(imgId, placeholderId, dotId, statusId) {
    document.getElementById(imgId).style.display = 'none';
    document.getElementById(placeholderId).style.display = '';
    document.getElementById(placeholderId).querySelector('p').textContent = 'กล้องออฟไลน์';
    document.getElementById(dotId).className = 'w-2 h-2 bg-red-400 rounded-full';
    const st = document.getElementById(statusId);
    st.textContent = 'OFFLINE';
    st.className = 'text-xs text-red-400';
}

function refreshDashboardSnapshots() {
    loadSnapshot('outside', 'streamOutside', 'streamOutPlaceholder', 'camOutDot', 'camOutStatus');
    loadSnapshot('inside', 'streamInside', 'streamInPlaceholder', 'camInDot', 'camInStatus');
}
refreshDashboardSnapshots();
setInterval(refreshDashboardSnapshots, 5000);

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

// ============================================================
// Hardware Health Monitor (Pi + ESP32)
// ============================================================
const CIRC = 327; // 2 * PI * 52

function setRing(id, percent, color) {
    const el = document.getElementById(id);
    if (!el) return;
    const offset = CIRC * (1 - Math.min(percent, 100) / 100);
    el.setAttribute('stroke-dashoffset', offset);
    if (color) el.setAttribute('stroke', color);
}

function colorByPercent(val, thresholds) {
    // thresholds: [green, yellow, red] e.g. [50, 75] => <50 green, 50-75 yellow, >75 red
    if (val < thresholds[0]) return '#22c55e';
    if (val < thresholds[1]) return '#f59e0b';
    return '#ef4444';
}

function formatUptime(seconds) {
    const d = Math.floor(seconds / 86400);
    const h = Math.floor((seconds % 86400) / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (d > 0) return d + 'ว ' + h + 'ชม';
    if (h > 0) return h + 'ชม ' + m + 'น';
    return m + ' นาที';
}

// Fetch Pi health
async function fetchPiHealth() {
    try {
        const resp = await fetch('api/pi_health.php', {signal: AbortSignal.timeout(5000)});
        if (!resp.ok) throw new Error();
        const d = await resp.json();

        // Temperature ring (max 85°C scale)
        const temp = d.cpu_temp ?? 0;
        const tempPct = Math.min(temp / 85 * 100, 100);
        document.getElementById('piTemp').textContent = temp ? temp.toFixed(1) : '-';
        setRing('tempRing', tempPct, colorByPercent(temp, [55, 70]));

        // CPU ring
        document.getElementById('piCpu').textContent = d.cpu_percent.toFixed(0);
        setRing('cpuRing', d.cpu_percent, colorByPercent(d.cpu_percent, [50, 80]));

        // RAM ring
        document.getElementById('piRam').textContent = d.ram_percent.toFixed(0);
        setRing('ramRing', d.ram_percent, colorByPercent(d.ram_percent, [60, 85]));
        document.getElementById('piRamDetail').textContent = d.ram_used_mb + ' / ' + d.ram_total_mb + ' MB';

        // Disk ring
        document.getElementById('piDisk').textContent = d.disk_percent.toFixed(0);
        setRing('diskRing', d.disk_percent, colorByPercent(d.disk_percent, [70, 90]));

        // Uptime
        document.getElementById('piUptime').textContent = 'Uptime: ' + formatUptime(d.uptime);

        // Status badge
        const badge = document.getElementById('piStatusBadge');
        badge.textContent = 'ONLINE';
        badge.className = 'text-xs px-2 py-0.5 rounded-full bg-green-500/20 text-green-400';
    } catch {
        const badge = document.getElementById('piStatusBadge');
        badge.textContent = 'OFFLINE';
        badge.className = 'text-xs px-2 py-0.5 rounded-full bg-red-500/20 text-red-400';
    }
}

// Fetch ESP32 health
async function fetchEspHealth() {
    try {
        const resp = await fetch(FACE_SERVER + '/api/esp32/health', {signal: AbortSignal.timeout(5000)});
        if (!resp.ok) throw new Error();
        const d = await resp.json();

        const badge = document.getElementById('espStatusBadge');
        if (!d.online) {
            badge.textContent = 'OFFLINE';
            badge.className = 'text-xs px-2 py-0.5 rounded-full bg-red-500/20 text-red-400';
            setRing('wifiRing', 0);
            return;
        }

        badge.textContent = 'ONLINE';
        badge.className = 'text-xs px-2 py-0.5 rounded-full bg-green-500/20 text-green-400';

        // WiFi signal ring (RSSI: -30 best, -90 worst)
        const rssi = d.rssi ?? -90;
        const wifiPct = Math.max(0, Math.min(100, (rssi + 90) / 60 * 100));
        document.getElementById('espRssi').textContent = rssi;
        setRing('wifiRing', wifiPct, wifiPct > 60 ? '#22c55e' : wifiPct > 30 ? '#f59e0b' : '#ef4444');

        // Door status
        const doorEl = document.getElementById('espDoor');
        if (d.door === 'locked') {
            doorEl.innerHTML = '<span class="text-red-400"><i class="fas fa-lock mr-1"></i>ล็อก</span>';
        } else {
            doorEl.innerHTML = '<span class="text-green-400"><i class="fas fa-lock-open mr-1"></i>เปิด</span>';
        }

        // Uptime
        document.getElementById('espUptime').textContent = formatUptime(d.uptime_sec ?? 0);

        // IP
        document.getElementById('espIp').textContent = d.ip ?? '-';

        // PIR sensors
        document.getElementById('espPirOut').innerHTML = d.pir_outside
            ? '<span class="text-yellow-400"><i class="fas fa-circle text-[8px] mr-1"></i>ตรวจจับ</span>'
            : '<span class="text-gray-500">ปกติ</span>';
        document.getElementById('espPirIn').innerHTML = d.pir_inside
            ? '<span class="text-yellow-400"><i class="fas fa-circle text-[8px] mr-1"></i>ตรวจจับ</span>'
            : '<span class="text-gray-500">ปกติ</span>';
    } catch {
        const badge = document.getElementById('espStatusBadge');
        badge.textContent = 'OFFLINE';
        badge.className = 'text-xs px-2 py-0.5 rounded-full bg-red-500/20 text-red-400';
    }
}

// Poll both
fetchPiHealth();
fetchEspHealth();
setInterval(fetchPiHealth, 5000);
setInterval(fetchEspHealth, 5000);

// Auto-refresh page ทุก 60 วินาที เพื่ออัปเดตสถิติ/สถานะจาก DB
setTimeout(() => location.reload(), 60000);
</script>

<?php include 'includes/footer.php'; ?>
