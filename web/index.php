<?php $pageTitle = 'Dashboard - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

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
                <p class="text-3xl font-bold text-white mt-2" id="statIn">-</p>
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
                <p class="text-3xl font-bold text-white mt-2" id="statOut">-</p>
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
                <p class="text-3xl font-bold text-white mt-2" id="statInside">-</p>
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
                <p class="text-3xl font-bold text-white mt-2" id="statAlerts">-</p>
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
                <span class="w-2 h-2 bg-green-400 rounded-full pulse-dot" id="camOutDot"></span>
                <span class="font-medium">กล้องด้านนอก</span>
            </div>
            <span class="text-xs text-gray-400">Camera 1</span>
        </div>
        <div class="stream-container aspect-video">
            <img id="streamOutside" src="" alt="Camera Outside" class="w-full" onerror="this.style.display='none'">
            <div id="streamOutPlaceholder" class="absolute inset-0 flex items-center justify-center bg-gray-800">
                <div class="text-center text-gray-500">
                    <i class="fas fa-video-slash text-3xl mb-2"></i>
                    <p class="text-sm">กล้องไม่พร้อมใช้งาน</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Camera Inside -->
    <div class="glass rounded-2xl overflow-hidden card-hover">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 bg-green-400 rounded-full pulse-dot" id="camInDot"></span>
                <span class="font-medium">กล้องด้านใน</span>
            </div>
            <span class="text-xs text-gray-400">Camera 2</span>
        </div>
        <div class="stream-container aspect-video">
            <img id="streamInside" src="" alt="Camera Inside" class="w-full" onerror="this.style.display='none'">
            <div id="streamInPlaceholder" class="absolute inset-0 flex items-center justify-center bg-gray-800">
                <div class="text-center text-gray-500">
                    <i class="fas fa-video-slash text-3xl mb-2"></i>
                    <p class="text-sm">กล้องไม่พร้อมใช้งาน</p>
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
            <div class="w-24 h-24 mx-auto rounded-full flex items-center justify-center mb-4 transition-all" id="doorIcon">
                <i class="fas fa-lock text-4xl" id="doorIconInner"></i>
            </div>
            <p class="text-xl font-bold" id="doorStatusText">กำลังตรวจสอบ...</p>
            <p class="text-sm text-gray-400 mt-1" id="doorStatusSub"></p>
        </div>
        <div class="space-y-3 mt-4">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-400">เซ็นเซอร์นอก</span>
                <span class="flex items-center gap-1" id="sensorOutStatus">
                    <span class="w-2 h-2 bg-gray-500 rounded-full"></span> ไม่มีสัญญาณ
                </span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-400">เซ็นเซอร์ใน</span>
                <span class="flex items-center gap-1" id="sensorInStatus">
                    <span class="w-2 h-2 bg-gray-500 rounded-full"></span> ไม่มีสัญญาณ
                </span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-400">ESP32</span>
                <span class="flex items-center gap-1" id="esp32Status">
                    <span class="w-2 h-2 bg-gray-500 rounded-full"></span> Offline
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
                <tr><td colspan="5" class="text-center text-gray-500 py-8">กำลังโหลด...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
// ============================================================
// Dashboard Logic
// ============================================================

// Load stats
async function loadStats() {
    const data = await fetchAPI(FACE_SERVER + '/api/stats');
    if (data) {
        document.getElementById('statIn').textContent = data.today_in || 0;
        document.getElementById('statOut').textContent = data.today_out || 0;
        document.getElementById('statInside').textContent = data.currently_inside || 0;
        document.getElementById('statAlerts').textContent = data.unresolved_alerts || 0;
    }
}

// Load status
async function loadStatus() {
    const data = await fetchAPI(FACE_SERVER + '/api/status');
    if (data) {
        // Door
        const doorIcon = document.getElementById('doorIcon');
        const doorInner = document.getElementById('doorIconInner');
        const doorText = document.getElementById('doorStatusText');
        if (data.door === 'unlocked') {
            doorIcon.className = 'w-24 h-24 mx-auto rounded-full flex items-center justify-center mb-4 bg-green-500/20';
            doorInner.className = 'fas fa-lock-open text-4xl text-green-400';
            doorText.textContent = 'ประตูเปิด';
            doorText.className = 'text-xl font-bold text-green-400';
        } else {
            doorIcon.className = 'w-24 h-24 mx-auto rounded-full flex items-center justify-center mb-4 bg-red-500/20';
            doorInner.className = 'fas fa-lock text-4xl text-red-400';
            doorText.textContent = 'ประตูล็อก';
            doorText.className = 'text-xl font-bold text-red-400';
        }
    }
}

// Load recent logs
async function loadRecentLogs() {
    const data = await fetchAPI(FACE_SERVER + '/api/logs/recent?limit=10');
    const tbody = document.getElementById('recentLogs');
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-gray-500 py-8">ไม่มีข้อมูล</td></tr>';
        return;
    }
    tbody.innerHTML = data.map(log => `
        <tr class="border-b border-white/5 hover:bg-white/5">
            <td class="py-3 px-2 text-gray-300">${formatTime(log.created_at)}</td>
            <td class="py-3 px-2">${log.first_name ? log.first_name + ' ' + log.last_name : '<span class="text-red-400">ไม่รู้จัก</span>'}</td>
            <td class="py-3 px-2">
                ${log.direction === 'IN'
                    ? '<span class="bg-green-500/20 text-green-400 px-2 py-1 rounded text-xs">เข้า</span>'
                    : '<span class="bg-blue-500/20 text-blue-400 px-2 py-1 rounded text-xs">ออก</span>'}
            </td>
            <td class="py-3 px-2">
                <div class="flex items-center gap-2">
                    <div class="w-16 bg-gray-700 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full ${log.confidence > 70 ? 'bg-green-400' : log.confidence > 40 ? 'bg-yellow-400' : 'bg-red-400'}"
                             style="width: ${log.confidence || 0}%"></div>
                    </div>
                    <span class="text-xs text-gray-400">${log.confidence || 0}%</span>
                </div>
            </td>
            <td class="py-3 px-2">
                ${log.is_authorized
                    ? '<span class="text-green-400"><i class="fas fa-check-circle"></i></span>'
                    : '<span class="text-red-400"><i class="fas fa-times-circle"></i></span>'}
            </td>
        </tr>
    `).join('');
}

// Camera streams
function initStreams() {
    const outside = document.getElementById('streamOutside');
    const inside = document.getElementById('streamInside');
    outside.src = FACE_SERVER + '/api/stream/outside';
    inside.src = FACE_SERVER + '/api/stream/inside';

    outside.onload = () => document.getElementById('streamOutPlaceholder').style.display = 'none';
    inside.onload = () => document.getElementById('streamInPlaceholder').style.display = 'none';
}

// Door controls
async function unlockDoor() {
    const data = await postAPI(FACE_SERVER + '/api/door/unlock');
    if (data?.success) loadStatus();
}

async function lockDoor() {
    const data = await postAPI(FACE_SERVER + '/api/door/lock');
    if (data?.success) loadStatus();
}

// Clock
function updateClock() {
    document.getElementById('currentTime').textContent =
        new Date().toLocaleString('th-TH', { dateStyle: 'long', timeStyle: 'medium' });
}

// Init
loadStats();
loadStatus();
loadRecentLogs();
initStreams();
updateClock();

setInterval(loadStats, 15000);
setInterval(loadStatus, 5000);
setInterval(loadRecentLogs, 10000);
setInterval(updateClock, 1000);
</script>

<?php include 'includes/footer.php'; ?>
