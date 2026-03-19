<?php $pageTitle = 'ตั้งค่าระบบ - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<div class="mb-8">
    <h2 class="text-2xl font-bold">ตั้งค่าระบบ</h2>
    <p class="text-gray-400 text-sm mt-1">กำหนดค่าต่างๆ ของระบบ Bunny Door</p>
</div>

<?php
$db = getDB();
$settingsRaw = $db->query("SELECT * FROM settings ORDER BY id")->fetchAll();
$systemStatus = $db->query("SELECT * FROM system_status ORDER BY id")->fetchAll();

// Build settings map
$settings = [];
foreach ($settingsRaw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

// Thai names for system status components
$componentNames = [
    'camera_outside' => ['name' => 'กล้องด้านนอก', 'icon' => 'fa-video', 'color' => 'blue'],
    'camera_inside'  => ['name' => 'กล้องด้านใน', 'icon' => 'fa-video', 'color' => 'purple'],
    'sensor_outside' => ['name' => 'เซ็นเซอร์ด้านนอก', 'icon' => 'fa-satellite-dish', 'color' => 'yellow'],
    'sensor_inside'  => ['name' => 'เซ็นเซอร์ด้านใน', 'icon' => 'fa-satellite-dish', 'color' => 'orange'],
    'esp32'          => ['name' => 'ESP32 ตัวควบคุม', 'icon' => 'fa-microchip', 'color' => 'green'],
    'raspberry_pi'   => ['name' => 'Raspberry Pi', 'icon' => 'fa-server', 'color' => 'red'],
    'face_server'    => ['name' => 'Face Recognition Server', 'icon' => 'fa-face-smile', 'color' => 'pink'],
];
?>

<!-- System Status (Live Detection) -->
<div class="glass rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold flex items-center gap-2">
            <i class="fas fa-server text-blue-400"></i> สถานะอุปกรณ์ (ตรวจสอบจริง)
        </h3>
        <button type="button" onclick="recheckDevices()" class="text-xs text-blue-400 hover:text-blue-300 transition flex items-center gap-1" id="btnRecheck">
            <i class="fas fa-rotate"></i> ตรวจสอบอีกครั้ง
        </button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Web Server (always online) -->
        <div class="bg-white/5 rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-server text-blue-400"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium">เว็บเซิร์ฟเวอร์</p>
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-green-400 pulse-dot"></span>
                    <span class="text-xs text-green-400">ออนไลน์</span>
                </div>
                <p class="text-xs text-gray-600 mt-0.5" id="webIPInfo">-</p>
            </div>
        </div>

        <!-- Database -->
        <div class="bg-white/5 rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-database text-purple-400"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium">ฐานข้อมูล MySQL</p>
                <div class="flex items-center gap-1.5" id="dbStatus">
                    <?php
                    $dbOnline = false;
                    try { getDB(); $dbOnline = true; } catch (Exception $e) {}
                    ?>
                    <span class="w-2 h-2 rounded-full <?= $dbOnline ? 'bg-green-400 pulse-dot' : 'bg-red-400' ?>"></span>
                    <span class="text-xs <?= $dbOnline ? 'text-green-400' : 'text-red-400' ?>"><?= $dbOnline ? 'ออนไลน์' : 'ออฟไลน์' ?></span>
                </div>
                <p class="text-xs text-gray-600 mt-0.5"><?= DB_HOST ?>:<?= DB_PORT ?></p>
            </div>
        </div>

        <!-- Raspberry Pi -->
        <div class="bg-white/5 rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-microchip text-green-400"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium">Raspberry Pi</p>
                <div class="flex items-center gap-1.5" id="piLiveStatus">
                    <span class="w-2 h-2 rounded-full bg-gray-500"></span>
                    <span class="text-xs text-gray-500">กำลังตรวจสอบ...</span>
                </div>
                <p class="text-xs text-gray-600 mt-0.5" id="piIPInfo"><?= FACE_SERVER_URL ?></p>
            </div>
        </div>

        <!-- ESP32 -->
        <div class="bg-white/5 rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-yellow-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-bolt text-yellow-400"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium">ESP32</p>
                <div class="flex items-center gap-1.5" id="espLiveStatus">
                    <span class="w-2 h-2 rounded-full bg-gray-500"></span>
                    <span class="text-xs text-gray-500">กำลังตรวจสอบ...</span>
                </div>
                <p class="text-xs text-gray-600 mt-0.5" id="espIPInfo">-</p>
            </div>
        </div>

        <!-- Camera Outside -->
        <div class="bg-white/5 rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-cyan-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-video text-cyan-400"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium">กล้องด้านนอก</p>
                <div class="flex items-center gap-1.5" id="camOutLiveStatus">
                    <span class="w-2 h-2 rounded-full bg-gray-500"></span>
                    <span class="text-xs text-gray-500">กำลังตรวจสอบ...</span>
                </div>
            </div>
        </div>

        <!-- Camera Inside -->
        <div class="bg-white/5 rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-pink-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-video text-pink-400"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium">กล้องด้านใน</p>
                <div class="flex items-center gap-1.5" id="camInLiveStatus">
                    <span class="w-2 h-2 rounded-full bg-gray-500"></span>
                    <span class="text-xs text-gray-500">กำลังตรวจสอบ...</span>
                </div>
            </div>
        </div>

        <!-- Face Recognition -->
        <div class="bg-white/5 rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-face-smile text-indigo-400"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium">Face Recognition</p>
                <div class="flex items-center gap-1.5" id="faceLiveStatus">
                    <span class="w-2 h-2 rounded-full bg-gray-500"></span>
                    <span class="text-xs text-gray-500">กำลังตรวจสอบ...</span>
                </div>
                <p class="text-xs text-gray-600 mt-0.5" id="faceInfo">-</p>
            </div>
        </div>

        <!-- PIR Sensors -->
        <div class="bg-white/5 rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-orange-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-satellite-dish text-orange-400"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium">เซ็นเซอร์ PIR</p>
                <div class="flex items-center gap-1.5" id="pirLiveStatus">
                    <span class="w-2 h-2 rounded-full bg-gray-500"></span>
                    <span class="text-xs text-gray-500">กำลังตรวจสอบ...</span>
                </div>
                <p class="text-xs text-gray-600 mt-0.5" id="pirInfo">-</p>
            </div>
        </div>
    </div>
</div>

<!-- Tab Navigation -->
<div class="flex gap-1 mb-6 bg-white/5 rounded-xl p-1">
    <button onclick="switchSettingsTab('door')" id="stab-door" class="stab-btn flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium transition bg-blue-600 text-white">
        <i class="fas fa-door-closed"></i> ประตู & ตรวจจับ
    </button>
    <button onclick="switchSettingsTab('esp32')" id="stab-esp32" class="stab-btn flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium transition text-gray-400 hover:text-white">
        <i class="fas fa-microchip"></i> ESP32 & WiFi
    </button>
    <button onclick="switchSettingsTab('camera')" id="stab-camera" class="stab-btn flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium transition text-gray-400 hover:text-white">
        <i class="fas fa-video"></i> กล้อง & ประมวลผล
    </button>
    <button onclick="switchSettingsTab('logs')" id="stab-logs" class="stab-btn flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium transition text-gray-400 hover:text-white">
        <i class="fas fa-scroll"></i> Server Log
    </button>
    <button onclick="switchSettingsTab('terminal')" id="stab-terminal" class="stab-btn flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium transition text-gray-400 hover:text-white">
        <i class="fas fa-terminal"></i> Terminal
    </button>
</div>

<form id="settingsForm" onsubmit="saveSettings(event)">

<!-- Tab 1: Door & Detection -->
<div id="spanel-door" class="settings-panel">
<div class="glass rounded-2xl p-6">
    <h3 class="font-bold mb-6 flex items-center gap-2">
        <i class="fas fa-door-closed text-green-400"></i> ตั้งค่าประตู & การตรวจจับ
    </h3>
    <div class="space-y-4">
        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div>
                <p class="text-sm font-medium">ระยะเวลาปลดล็อกประตู</p>
                <p class="text-xs text-gray-500">ประตูจะล็อกอัตโนมัติหลังจากเวลาที่กำหนด</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="number" name="door_unlock_seconds" value="<?= htmlspecialchars($settings['door_unlock_seconds'] ?? '7') ?>"
                       class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white w-24 text-right focus:outline-none focus:border-blue-500" min="1" max="60">
                <span class="text-xs text-gray-500 w-12">วินาที</span>
            </div>
        </div>

        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div>
                <p class="text-sm font-medium">ค่าความมั่นใจขั้นต่ำ (Confidence)</p>
                <p class="text-xs text-gray-500">% ขั้นต่ำที่ระบบจะอนุญาตให้เข้า</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="number" name="face_confidence_threshold" value="<?= htmlspecialchars($settings['face_confidence_threshold'] ?? '60') ?>"
                       class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white w-24 text-right focus:outline-none focus:border-blue-500" min="1" max="100">
                <span class="text-xs text-gray-500 w-12">%</span>
            </div>
        </div>

        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div>
                <p class="text-sm font-medium">ตรวจจับ Tailgating</p>
                <p class="text-xs text-gray-500">แจ้งเตือนเมื่อมีคนเดินตามเข้าพร้อมกัน</p>
            </div>
            <select name="tailgate_detection" class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                <option value="1" <?= ($settings['tailgate_detection'] ?? '1') === '1' ? 'selected' : '' ?>>เปิดใช้งาน</option>
                <option value="0" <?= ($settings['tailgate_detection'] ?? '1') === '0' ? 'selected' : '' ?>>ปิดใช้งาน</option>
            </select>
        </div>

        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div>
                <p class="text-sm font-medium">แจ้งเตือนใบหน้าไม่รู้จัก</p>
                <p class="text-xs text-gray-500">สร้าง Alert เมื่อพบคนที่ไม่ได้ลงทะเบียน</p>
            </div>
            <select name="alert_unknown_face" class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                <option value="1" <?= ($settings['alert_unknown_face'] ?? '1') === '1' ? 'selected' : '' ?>>เปิดใช้งาน</option>
                <option value="0" <?= ($settings['alert_unknown_face'] ?? '1') === '0' ? 'selected' : '' ?>>ปิดใช้งาน</option>
            </select>
        </div>

        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div>
                <p class="text-sm font-medium">จำนวนคนสูงสุดต่อการเข้า 1 ครั้ง</p>
                <p class="text-xs text-gray-500">ถ้ามากกว่านี้จะแจ้งเตือน MULTI_PERSON</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="number" name="max_persons_per_entry" value="<?= htmlspecialchars($settings['max_persons_per_entry'] ?? '1') ?>"
                       class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white w-24 text-right focus:outline-none focus:border-blue-500" min="1" max="10">
                <span class="text-xs text-gray-500 w-12">คน</span>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Tab 2: ESP32 & WiFi -->
<div id="spanel-esp32" class="settings-panel hidden">
<div class="glass rounded-2xl p-6 mb-6">
    <h3 class="font-bold mb-6 flex items-center gap-2">
        <i class="fas fa-wifi text-green-400"></i> ตั้งค่า WiFi & เครือข่าย
    </h3>
    <div class="space-y-4">
        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div>
                <p class="text-sm font-medium">ชื่อ WiFi (SSID)</p>
                <p class="text-xs text-gray-500">WiFi ที่ ESP32 จะเชื่อมต่อ</p>
            </div>
            <input type="text" name="wifi_ssid" value="<?= htmlspecialchars($settings['wifi_ssid'] ?? '') ?>"
                   class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white w-56 focus:outline-none focus:border-blue-500"
                   placeholder="ชื่อ WiFi">
        </div>

        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div>
                <p class="text-sm font-medium">รหัสผ่าน WiFi</p>
                <p class="text-xs text-gray-500">รหัสผ่านของ WiFi</p>
            </div>
            <div class="relative">
                <input type="password" name="wifi_password" id="wifiPassInput" value="<?= htmlspecialchars($settings['wifi_password'] ?? '') ?>"
                       class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 pr-10 text-white w-56 focus:outline-none focus:border-blue-500"
                       placeholder="รหัสผ่าน WiFi">
                <button type="button" onclick="toggleWifiPass()" class="absolute right-3 top-2.5 text-gray-500 hover:text-white">
                    <i class="fas fa-eye" id="wifiPassIcon"></i>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div>
                <p class="text-sm font-medium">IP ของ ESP32</p>
                <p class="text-xs text-gray-500">IP ที่ ESP32 ได้รับจาก Router (ใช้ส่งคำสั่ง)</p>
            </div>
            <input type="text" name="esp32_ip" value="<?= htmlspecialchars($settings['esp32_ip'] ?? '') ?>"
                   class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white w-56 focus:outline-none focus:border-blue-500"
                   placeholder="192.168.1.100">
        </div>

        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div>
                <p class="text-sm font-medium">URL ของ Raspberry Pi Server</p>
                <p class="text-xs text-gray-500">ESP32 จะส่งข้อมูล motion/heartbeat ไปที่นี่</p>
            </div>
            <input type="text" name="server_url" value="<?= htmlspecialchars($settings['server_url'] ?? 'http://192.168.1.50:5000') ?>"
                   class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white w-56 focus:outline-none focus:border-blue-500"
                   placeholder="http://192.168.1.50:5000">
        </div>
    </div>
</div>

<div class="glass rounded-2xl p-6 mb-6">
    <h3 class="font-bold mb-6 flex items-center gap-2">
        <i class="fas fa-microchip text-purple-400"></i> ตั้งค่า ESP32 Hardware
    </h3>
    <div class="space-y-4">
        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div>
                <p class="text-sm font-medium">ประเภท Relay</p>
                <p class="text-xs text-gray-500">NO = ปกติเปิด (ล็อกเมื่อ HIGH), NC = ปกติปิด</p>
            </div>
            <select name="door_lock_type" class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                <option value="NO" <?= ($settings['door_lock_type'] ?? 'NO') === 'NO' ? 'selected' : '' ?>>Normally Open (NO)</option>
                <option value="NC" <?= ($settings['door_lock_type'] ?? 'NO') === 'NC' ? 'selected' : '' ?>>Normally Closed (NC)</option>
            </select>
        </div>

        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div>
                <p class="text-sm font-medium">PIR Cooldown</p>
                <p class="text-xs text-gray-500">ระยะเวลาพักเซ็นเซอร์หลังตรวจจับ</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="number" name="pir_cooldown_ms" value="<?= htmlspecialchars($settings['pir_cooldown_ms'] ?? '3000') ?>"
                       class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white w-28 text-right focus:outline-none focus:border-blue-500" min="500" max="30000" step="500">
                <span class="text-xs text-gray-500 w-8">ms</span>
            </div>
        </div>

        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div>
                <p class="text-sm font-medium">Heartbeat Interval</p>
                <p class="text-xs text-gray-500">ESP32 ส่งสถานะไปยัง Server ทุกกี่มิลลิวินาที</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="number" name="heartbeat_interval_ms" value="<?= htmlspecialchars($settings['heartbeat_interval_ms'] ?? '10000') ?>"
                       class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white w-28 text-right focus:outline-none focus:border-blue-500" min="1000" max="60000" step="1000">
                <span class="text-xs text-gray-500 w-8">ms</span>
            </div>
        </div>
    </div>
</div>

<!-- Generate Arduino Code -->
<div class="glass rounded-2xl p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold flex items-center gap-2">
            <i class="fas fa-code text-blue-400"></i> สร้างโค้ด Arduino จากค่าตั้งด้านบน
        </h3>
        <button type="button" onclick="generateAndCopyCode()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition flex items-center gap-2">
            <i class="fas fa-wand-magic-sparkles"></i> สร้าง & คัดลอกโค้ด
        </button>
    </div>
    <p class="text-gray-400 text-sm mb-4">กดปุ่ม "บันทึกการตั้งค่า" ก่อน แล้วกด "สร้าง & คัดลอกโค้ด" เพื่อ generate โค้ด Arduino ที่ใช้ค่าตั้งจากหน้านี้โดยอัตโนมัติ</p>
    <div class="bg-black/40 rounded-xl p-4 overflow-x-auto max-h-96 overflow-y-auto hidden" id="generatedCodeBox">
        <pre id="generatedCode" class="text-sm font-mono text-green-400 whitespace-pre leading-relaxed"></pre>
    </div>
</div>
</div>

<!-- Tab 3: Camera & Processing -->
<div id="spanel-camera" class="settings-panel hidden">

<!-- Camera Detection -->
<div class="glass rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold flex items-center gap-2">
            <i class="fas fa-video text-pink-400"></i> กล้อง USB ที่ตรวจพบ
        </h3>
        <button type="button" onclick="detectCameras()" class="text-xs text-blue-400 hover:text-blue-300 transition flex items-center gap-1" id="btnDetectCam">
            <i class="fas fa-rotate"></i> สแกนกล้องใหม่
        </button>
    </div>

    <div id="cameraDetectResult" class="text-sm text-gray-500 mb-4">
        <div class="flex items-center gap-2">
            <i class="fas fa-spinner fa-spin"></i> กำลังสแกนกล้อง...
        </div>
    </div>

    <div id="cameraGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>

    <div id="cameraAssignBtn" class="mt-4 hidden">
        <button type="button" onclick="assignCameras()" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-xl transition text-sm flex items-center gap-2">
            <i class="fas fa-check"></i> บันทึกการกำหนดกล้อง
        </button>
        <p class="text-xs text-yellow-400 mt-2"><i class="fas fa-exclamation-triangle mr-1"></i> หลังบันทึก ต้อง restart face_server บน Pi เพื่อใช้กล้องใหม่</p>
    </div>
</div>

<!-- Camera Processing Settings -->
<div class="glass rounded-2xl p-6">
    <h3 class="font-bold mb-6 flex items-center gap-2">
        <i class="fas fa-cogs text-purple-400"></i> ตั้งค่าการประมวลผล
    </h3>
    <div class="space-y-4">
        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div>
                <p class="text-sm font-medium">ประมวลผลทุกกี่เฟรม</p>
                <p class="text-xs text-gray-500">ค่ามาก = เร็วขึ้นแต่ตรวจจับช้าลง / ค่าน้อย = ละเอียดแต่หนักกว่า</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="number" name="process_every_x_frames" value="<?= htmlspecialchars($settings['process_every_x_frames'] ?? '5') ?>"
                       class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white w-24 text-right focus:outline-none focus:border-blue-500" min="1" max="30">
                <span class="text-xs text-gray-500 w-12">เฟรม</span>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Tab 4: Server Log -->
<div id="spanel-logs" class="settings-panel hidden">
<div class="glass rounded-2xl p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold flex items-center gap-2">
            <i class="fas fa-terminal text-green-400"></i> Face Server Log
        </h3>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500" id="logFileInfo">-</span>
            <button type="button" onclick="fetchServerLogs()" class="text-xs text-blue-400 hover:text-blue-300 transition flex items-center gap-1">
                <i class="fas fa-rotate"></i> รีเฟรช
            </button>
            <button type="button" onclick="clearServerLogs()" class="text-xs text-red-400 hover:text-red-300 transition flex items-center gap-1">
                <i class="fas fa-trash"></i> ลบ Log
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-2 mb-4">
        <select id="logLevel" onchange="fetchServerLogs()" class="bg-white/10 border border-white/10 rounded-lg px-3 py-1.5 text-white text-xs focus:outline-none focus:border-blue-500">
            <option value="">ทุก Level</option>
            <option value="INFO">INFO</option>
            <option value="WARNING">WARNING</option>
            <option value="ERROR">ERROR</option>
        </select>
        <input type="text" id="logSearch" placeholder="ค้นหา..." onkeyup="if(event.key==='Enter')fetchServerLogs()"
               class="bg-white/10 border border-white/10 rounded-lg px-3 py-1.5 text-white text-xs focus:outline-none focus:border-blue-500 w-48">
        <select id="logLines" onchange="fetchServerLogs()" class="bg-white/10 border border-white/10 rounded-lg px-3 py-1.5 text-white text-xs focus:outline-none focus:border-blue-500">
            <option value="100">100 บรรทัด</option>
            <option value="200" selected>200 บรรทัด</option>
            <option value="500">500 บรรทัด</option>
            <option value="1000">1000 บรรทัด</option>
        </select>
        <label class="flex items-center gap-1 text-xs text-gray-400">
            <input type="checkbox" id="logAutoScroll" checked class="rounded"> Auto-scroll
        </label>
    </div>

    <!-- Log Output -->
    <div class="bg-black/60 rounded-xl p-4 overflow-auto font-mono text-xs leading-5" id="logOutput" style="max-height: 500px; min-height: 200px;">
        <p class="text-gray-600">กดแท็บ "Server Log" เพื่อโหลด...</p>
    </div>

    <div class="flex items-center justify-between mt-3">
        <p class="text-xs text-gray-600" id="logStats">-</p>
        <label class="flex items-center gap-1 text-xs text-gray-400">
            <input type="checkbox" id="logAutoRefresh" onchange="toggleLogAutoRefresh()"> Auto-refresh ทุก 3 วินาที
        </label>
    </div>
</div>
</div>

<!-- Tab 5: Terminal -->
<div id="spanel-terminal" class="settings-panel hidden">
<div class="glass rounded-2xl p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold flex items-center gap-2">
            <i class="fas fa-terminal text-green-400"></i> Raspberry Pi Terminal
        </h3>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500">cwd: ~/bunny-door</span>
            <button type="button" onclick="clearTerminal()" class="text-xs text-red-400 hover:text-red-300 transition flex items-center gap-1">
                <i class="fas fa-trash"></i> ล้างจอ
            </button>
        </div>
    </div>

    <!-- Quick Commands -->
    <div class="flex flex-wrap gap-2 mb-4">
        <button type="button" onclick="runQuickCmd('git status')" class="bg-white/10 hover:bg-white/20 text-xs px-3 py-1.5 rounded-lg transition">git status</button>
        <button type="button" onclick="runQuickCmd('git pull')" class="bg-white/10 hover:bg-white/20 text-xs px-3 py-1.5 rounded-lg transition">git pull</button>
        <button type="button" onclick="runQuickCmd('ps aux | grep face_server')" class="bg-white/10 hover:bg-white/20 text-xs px-3 py-1.5 rounded-lg transition">ps face_server</button>
        <button type="button" onclick="runQuickCmd('free -h')" class="bg-white/10 hover:bg-white/20 text-xs px-3 py-1.5 rounded-lg transition">free -h</button>
        <button type="button" onclick="runQuickCmd('df -h')" class="bg-white/10 hover:bg-white/20 text-xs px-3 py-1.5 rounded-lg transition">df -h</button>
        <button type="button" onclick="runQuickCmd('uptime')" class="bg-white/10 hover:bg-white/20 text-xs px-3 py-1.5 rounded-lg transition">uptime</button>
        <button type="button" onclick="runQuickCmd('vcgencmd measure_temp')" class="bg-white/10 hover:bg-white/20 text-xs px-3 py-1.5 rounded-lg transition">CPU Temp</button>
        <button type="button" onclick="runQuickCmd('v4l2-ctl --list-devices')" class="bg-white/10 hover:bg-white/20 text-xs px-3 py-1.5 rounded-lg transition">กล้อง USB</button>
        <button type="button" onclick="runQuickCmd('lsusb')" class="bg-white/10 hover:bg-white/20 text-xs px-3 py-1.5 rounded-lg transition">lsusb</button>
        <button type="button" onclick="runQuickCmd('ip addr show')" class="bg-white/10 hover:bg-white/20 text-xs px-3 py-1.5 rounded-lg transition">IP Address</button>
    </div>

    <!-- Terminal Output -->
    <div class="bg-black/80 rounded-xl p-4 overflow-auto font-mono text-xs leading-5" id="terminalOutput" style="max-height: 400px; min-height: 200px;">
        <p class="text-green-400">root1@raspberrypi:~/bunny-door$ <span class="text-gray-500">พร้อมใช้งาน — พิมพ์คำสั่งด้านล่าง</span></p>
    </div>

    <!-- Command Input -->
    <div class="flex gap-2 mt-3">
        <span class="text-green-400 text-sm font-mono flex items-center">$</span>
        <input type="text" id="terminalInput" placeholder="พิมพ์คำสั่ง..."
               class="flex-1 bg-black/60 border border-white/10 rounded-lg px-3 py-2 text-white font-mono text-sm focus:outline-none focus:border-green-500"
               onkeydown="if(event.key==='Enter'){runTerminalCmd(); event.preventDefault();}">
        <button type="button" onclick="runTerminalCmd()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition">
            <i class="fas fa-play"></i> รัน
        </button>
    </div>
    <p class="text-xs text-gray-600 mt-2"><i class="fas fa-shield-halved mr-1"></i> จำกัดเฉพาะคำสั่งที่ปลอดภัย — ห้าม rm, dd, shutdown</p>
</div>
</div>

<!-- Save Button (Always visible) -->
<div class="mt-6 flex justify-end">
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl transition font-medium flex items-center gap-2">
        <i class="fas fa-save"></i> บันทึกการตั้งค่าทั้งหมด
    </button>
</div>

</form>

<!-- System Update Section -->
<div class="glass rounded-2xl p-6 mt-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold flex items-center gap-2">
            <i class="fas fa-cloud-arrow-down text-cyan-400"></i> อัพเดทระบบ
        </h3>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500">เวอร์ชันปัจจุบัน:</span>
            <span class="bg-blue-500/20 text-blue-400 px-3 py-1 rounded-full text-sm font-mono font-bold" id="currentVersion">v<?= APP_VERSION ?></span>
            <span class="text-xs text-gray-500">(build <?= APP_BUILD ?>)</span>
        </div>
    </div>

    <!-- Update Status -->
    <div id="updateStatus" class="bg-white/5 rounded-xl p-4 mb-4">
        <div class="flex items-center gap-3">
            <i class="fas fa-circle-info text-gray-400"></i>
            <span class="text-sm text-gray-400">กดปุ่ม "ตรวจสอบ" เพื่อเช็คเวอร์ชันล่าสุดจาก GitHub</span>
        </div>
    </div>

    <!-- Update Log -->
    <div id="updateLog" class="bg-black/40 rounded-xl p-4 mb-4 hidden max-h-40 overflow-y-auto">
        <pre id="updateLogText" class="text-xs font-mono text-green-400 whitespace-pre-wrap"></pre>
    </div>

    <!-- Buttons -->
    <div class="flex gap-3">
        <button type="button" onclick="checkUpdate()" id="btnCheck" class="bg-cyan-600 hover:bg-cyan-700 text-white px-5 py-2.5 rounded-xl transition text-sm flex items-center gap-2">
            <i class="fas fa-magnifying-glass"></i> ตรวจสอบเวอร์ชันล่าสุด
        </button>
        <button type="button" onclick="pullUpdate()" id="btnUpdate" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-xl transition text-sm flex items-center gap-2 hidden">
            <i class="fas fa-download"></i> อัพเดทเลย
        </button>
    </div>
</div>

<script>
// ============================================================
// Tab Switching
// ============================================================
function switchSettingsTab(tab) {
    document.querySelectorAll('.settings-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.stab-btn').forEach(b => {
        b.classList.remove('bg-blue-600', 'text-white');
        b.classList.add('text-gray-400');
    });
    document.getElementById('spanel-' + tab).classList.remove('hidden');
    const btn = document.getElementById('stab-' + tab);
    btn.classList.add('bg-blue-600', 'text-white');
    btn.classList.remove('text-gray-400');
}

// ============================================================
// Toggle WiFi Password Visibility
// ============================================================
function toggleWifiPass() {
    const inp = document.getElementById('wifiPassInput');
    const icon = document.getElementById('wifiPassIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// ============================================================
// Save Settings
// ============================================================
async function saveSettings(e) {
    e.preventDefault();
    const form = new FormData(e.target);
    const data = Object.fromEntries(form);
    const res = await postAPI('api/settings.php', data);
    if (res?.success) {
        showToast('บันทึกการตั้งค่าสำเร็จ (' + (res.updated || 0) + ' รายการ)', 'success');
    } else {
        showToast(res?.error || 'เกิดข้อผิดพลาด', 'error');
    }
}

// ============================================================
// Generate Arduino Code from Current Settings
// ============================================================
function generateAndCopyCode() {
    const form = document.getElementById('settingsForm');
    const fd = new FormData(form);
    const s = Object.fromEntries(fd);

    const code = `/*
 * ============================================================
 * Bunny Door System - ESP32 Door Controller
 * ============================================================
 * โค้ดนี้ถูกสร้างอัตโนมัติจากหน้าตั้งค่าระบบ
 * วันที่สร้าง: ${new Date().toLocaleString('th-TH')}
 * ============================================================
 * การต่อสาย (ตรงกับบอร์ดเดิม):
 *   Relay        -> GPIO 4  (เดิม)
 *   LED Status   -> GPIO 2  (เดิม)
 *   PIR Outside  -> GPIO 27
 *   PIR Inside   -> GPIO 26
 *   Buzzer       -> GPIO 33
 *   Emergency Btn -> GPIO 13 (Pull-up)
 * ============================================================
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ArduinoJson.h>

// ============================================================
// Configuration (จากหน้าตั้งค่าระบบ)
// ============================================================
const char* WIFI_SSID     = "${esc(s.wifi_ssid || 'YOUR_WIFI_SSID')}";
const char* WIFI_PASSWORD = "${esc(s.wifi_password || 'YOUR_WIFI_PASSWORD')}";
const char* SERVER_URL    = "${esc(s.server_url || 'http://192.168.1.50:5000')}";

// ============================================================
// Pin Definitions
// ============================================================
#define PIN_PIR_OUTSIDE    27
#define PIN_PIR_INSIDE     26
#define PIN_RELAY          4      // เดิม GPIO 4
#define PIN_BUZZER         33
#define PIN_LED_STATUS     2      // เดิม GPIO 2
#define PIN_EMERGENCY_BTN  13

// ============================================================
// Constants (จากหน้าตั้งค่าระบบ)
// ============================================================
#define DOOR_UNLOCK_MS         ${parseInt(s.door_unlock_seconds || 7) * 1000}   // ${s.door_unlock_seconds || 7} วินาที
#define PIR_COOLDOWN_MS        ${s.pir_cooldown_ms || 3000}   // cooldown เซ็นเซอร์
#define HEARTBEAT_INTERVAL_MS  ${s.heartbeat_interval_ms || 10000}  // heartbeat interval
#define DEBOUNCE_MS            200

// Relay type: ${s.door_lock_type === 'NC' ? 'Normally Closed' : 'Normally Open'}
#define RELAY_UNLOCK  ${s.door_lock_type === 'NC' ? 'LOW' : 'HIGH'}
#define RELAY_LOCK    ${s.door_lock_type === 'NC' ? 'HIGH' : 'LOW'}

// ============================================================
// State Variables
// ============================================================
WebServer server(80);

bool doorLocked = true;
unsigned long doorUnlockTime = 0;
unsigned long lastPirOutside = 0;
unsigned long lastPirInside = 0;
unsigned long lastHeartbeat = 0;
unsigned long lastBtnPress = 0;

bool pirOutsideState = false;
bool pirInsideState = false;

// ============================================================
// Setup
// ============================================================
void setup() {
    Serial.begin(115200);
    Serial.println("\\n=== Bunny Door System - ESP32 ===");

    pinMode(PIN_PIR_OUTSIDE, INPUT);
    pinMode(PIN_PIR_INSIDE, INPUT);
    pinMode(PIN_RELAY, OUTPUT);
    pinMode(PIN_BUZZER, OUTPUT);
    pinMode(PIN_LED_STATUS, OUTPUT);
    pinMode(PIN_EMERGENCY_BTN, INPUT_PULLUP);

    lockDoor();
    connectWiFi();
    setupWebServer();

    Serial.println("System Ready!");
    beep(2, 100);
}

// ============================================================
// Main Loop
// ============================================================
void loop() {
    server.handleClient();
    unsigned long now = millis();

    if (digitalRead(PIN_PIR_OUTSIDE) == HIGH && (now - lastPirOutside > PIR_COOLDOWN_MS)) {
        lastPirOutside = now;
        pirOutsideState = true;
        Serial.println("[PIR] Motion detected OUTSIDE");
        notifyServer("outside");
    }

    if (digitalRead(PIN_PIR_INSIDE) == HIGH && (now - lastPirInside > PIR_COOLDOWN_MS)) {
        lastPirInside = now;
        pirInsideState = true;
        Serial.println("[PIR] Motion detected INSIDE");
        notifyServer("inside");
    }

    if (digitalRead(PIN_PIR_OUTSIDE) == LOW) pirOutsideState = false;
    if (digitalRead(PIN_PIR_INSIDE) == LOW) pirInsideState = false;

    if (digitalRead(PIN_EMERGENCY_BTN) == LOW && (now - lastBtnPress > DEBOUNCE_MS)) {
        lastBtnPress = now;
        Serial.println("[EMERGENCY] Button pressed!");
        unlockDoor();
        notifyEmergency();
    }

    if (!doorLocked && (now - doorUnlockTime > DOOR_UNLOCK_MS)) {
        lockDoor();
        Serial.println("[DOOR] Auto-locked");
    }

    if (now - lastHeartbeat > HEARTBEAT_INTERVAL_MS) {
        lastHeartbeat = now;
        sendHeartbeat();
    }

    delay(50);
}

// ============================================================
// Door Control
// ============================================================
void unlockDoor() {
    doorLocked = false;
    doorUnlockTime = millis();
    digitalWrite(PIN_RELAY, RELAY_UNLOCK);
    digitalWrite(PIN_LED_STATUS, HIGH);
    beep(1, 200);
    Serial.println("[DOOR] Unlocked");
}

void lockDoor() {
    doorLocked = true;
    digitalWrite(PIN_RELAY, RELAY_LOCK);
    digitalWrite(PIN_LED_STATUS, LOW);
    Serial.println("[DOOR] Locked");
}

// ============================================================
// Wi-Fi
// ============================================================
void connectWiFi() {
    Serial.printf("Connecting to %s", WIFI_SSID);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 30) {
        delay(500);
        Serial.print(".");
        attempts++;
    }
    if (WiFi.status() == WL_CONNECTED) {
        Serial.printf("\\nConnected! IP: %s\\n", WiFi.localIP().toString().c_str());
    } else {
        Serial.println("\\nWi-Fi connection failed! Running in offline mode.");
    }
}

// ============================================================
// Web Server
// ============================================================
void setupWebServer() {
    server.on("/api/door/unlock", HTTP_POST, []() {
        unlockDoor();
        server.send(200, "application/json", "{\\"status\\":\\"unlocked\\"}");
    });

    server.on("/api/door/lock", HTTP_POST, []() {
        lockDoor();
        server.send(200, "application/json", "{\\"status\\":\\"locked\\"}");
    });

    server.on("/api/status", HTTP_GET, []() {
        StaticJsonDocument<256> doc;
        doc["door"] = doorLocked ? "locked" : "unlocked";
        doc["pir_outside"] = pirOutsideState;
        doc["pir_inside"] = pirInsideState;
        doc["uptime_sec"] = millis() / 1000;
        doc["ip"] = WiFi.localIP().toString();
        doc["rssi"] = WiFi.RSSI();
        String output;
        serializeJson(doc, output);
        server.send(200, "application/json", output);
    });

    server.on("/ping", HTTP_GET, []() {
        server.send(200, "text/plain", "pong");
    });

    server.begin();
    Serial.println("HTTP Server started on port 80");
}

// ============================================================
// Communication with Raspberry Pi
// ============================================================
void notifyServer(const char* side) {
    if (WiFi.status() != WL_CONNECTED) return;
    HTTPClient http;
    String url = String(SERVER_URL) + "/api/motion";
    http.begin(url);
    http.addHeader("Content-Type", "application/json");
    StaticJsonDocument<128> doc;
    doc["sensor"] = side;
    doc["timestamp"] = millis();
    String body;
    serializeJson(doc, body);
    int code = http.POST(body);
    if (code > 0) {
        Serial.printf("[HTTP] Motion notify -> %d\\n", code);
    } else {
        Serial.printf("[HTTP] Error: %s\\n", http.errorToString(code).c_str());
    }
    http.end();
}

void notifyEmergency() {
    if (WiFi.status() != WL_CONNECTED) return;
    HTTPClient http;
    String url = String(SERVER_URL) + "/api/emergency";
    http.begin(url);
    http.addHeader("Content-Type", "application/json");
    http.POST("{\\"type\\":\\"emergency_button\\"}");
    http.end();
}

void sendHeartbeat() {
    if (WiFi.status() != WL_CONNECTED) return;
    HTTPClient http;
    String url = String(SERVER_URL) + "/api/heartbeat";
    http.begin(url);
    http.addHeader("Content-Type", "application/json");
    StaticJsonDocument<200> doc;
    doc["device"] = "esp32";
    doc["door"] = doorLocked ? "locked" : "unlocked";
    doc["pir_outside"] = pirOutsideState;
    doc["pir_inside"] = pirInsideState;
    doc["rssi"] = WiFi.RSSI();
    doc["ip"] = WiFi.localIP().toString();
    String body;
    serializeJson(doc, body);
    http.POST(body);
    http.end();
}

// ============================================================
// Buzzer
// ============================================================
void beep(int times, int duration) {
    for (int i = 0; i < times; i++) {
        digitalWrite(PIN_BUZZER, HIGH);
        delay(duration);
        digitalWrite(PIN_BUZZER, LOW);
        if (i < times - 1) delay(duration);
    }
}`;

    // Show code box
    document.getElementById('generatedCodeBox').classList.remove('hidden');
    document.getElementById('generatedCode').textContent = code;

    // Copy to clipboard
    navigator.clipboard.writeText(code).then(() => {
        showToast('สร้างโค้ดสำเร็จ & คัดลอกไปยัง Clipboard แล้ว!', 'success', 4000);
    }).catch(() => {
        const ta = document.createElement('textarea');
        ta.value = code;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        showToast('สร้างโค้ดสำเร็จ & คัดลอกไปยัง Clipboard แล้ว!', 'success', 4000);
    });
}

// ============================================================
// System Update
// ============================================================
async function checkUpdate() {
    const btn = document.getElementById('btnCheck');
    const status = document.getElementById('updateStatus');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังตรวจสอบ...';

    try {
        const res = await fetch('api/update.php?action=check');
        const data = await res.json();

        if (data.success) {
            const local = data.local;
            const remote = data.remote;

            if (data.has_update && remote) {
                status.innerHTML = `
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-arrow-up text-green-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-green-400">มีเวอร์ชันใหม่!</p>
                            <p class="text-xs text-gray-400">
                                <span class="text-gray-500">ปัจจุบัน:</span> v${local?.version || '?'} (build ${local?.build || '?'})
                                &rarr;
                                <span class="text-green-400 font-bold">v${remote.version} (build ${remote.build})</span>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">${esc(remote.changelog || '')}</p>
                        </div>
                    </div>`;
                document.getElementById('btnUpdate').classList.remove('hidden');
            } else {
                status.innerHTML = `
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check text-blue-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-blue-400">ระบบเป็นเวอร์ชันล่าสุดแล้ว</p>
                            <p class="text-xs text-gray-400">v${local?.version || '?'} (build ${local?.build || '?'}) &mdash; ${local?.date || ''}</p>
                        </div>
                    </div>`;
                document.getElementById('btnUpdate').classList.add('hidden');
            }
        } else {
            status.innerHTML = `<div class="flex items-center gap-3"><i class="fas fa-exclamation-triangle text-yellow-400"></i><span class="text-sm text-yellow-400">ไม่สามารถตรวจสอบได้ ลองใหม่อีกครั้ง</span></div>`;
        }
    } catch (e) {
        status.innerHTML = `<div class="flex items-center gap-3"><i class="fas fa-times-circle text-red-400"></i><span class="text-sm text-red-400">เกิดข้อผิดพลาด: ${esc(e.message)}</span></div>`;
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-magnifying-glass"></i> ตรวจสอบเวอร์ชันล่าสุด';
}

// ============================================================
// Live Device Detection
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    checkDevicesLive();
});

async function checkDevicesLive() {
    // Detect network info
    try {
        const net = await fetchAPI('api/network.php?action=detect');
        if (net.success) {
            document.getElementById('webIPInfo').textContent = net.web_server.primary_ip;

            // Pi status
            setDeviceStatus('piLiveStatus', net.raspberry_pi.online);
            if (net.raspberry_pi.online) {
                document.getElementById('piIPInfo').textContent = net.raspberry_pi.url + ' (เชื่อมต่อได้)';
            }

            // If Pi online, check cameras, face recognition, and ESP32
            if (net.raspberry_pi.online) {
                checkCameras();
                checkFaceRecognition();
                checkESP32();
            } else {
                setDeviceStatus('camOutLiveStatus', false);
                setDeviceStatus('camInLiveStatus', false);
                setDeviceStatus('faceLiveStatus', false);
                setDeviceStatus('espLiveStatus', false);
                setDeviceStatus('pirLiveStatus', false);
                document.getElementById('faceInfo').textContent = 'Pi ออฟไลน์';
                document.getElementById('espIPInfo').textContent = '-';
                document.getElementById('pirInfo').textContent = '-';
            }
        }
    } catch (e) {
        console.error('Device check failed:', e);
    }
}

async function checkESP32() {
    try {
        const data = await fetchAPI(FACE_SERVER + '/api/esp32/health');
        if (data && data.online) {
            setDeviceStatus('espLiveStatus', true);
            document.getElementById('espIPInfo').textContent = (data.ip || '-') + ' | RSSI: ' + (data.rssi || '-') + ' dBm';

            // PIR sensors — ผ่าน ESP32
            const pirOut = data.pir_outside;
            const pirIn = data.pir_inside;
            setDeviceStatus('pirLiveStatus', true);
            let pirText = 'นอก: ' + (pirOut ? 'ตรวจจับ' : 'ปกติ') + ' | ใน: ' + (pirIn ? 'ตรวจจับ' : 'ปกติ');
            document.getElementById('pirInfo').textContent = pirText;
        } else {
            setDeviceStatus('espLiveStatus', false);
            document.getElementById('espIPInfo').textContent = data?.ip || 'ไม่ตอบสนอง';
            setDeviceStatus('pirLiveStatus', false);
            document.getElementById('pirInfo').textContent = 'ESP32 ออฟไลน์';
        }
    } catch {
        setDeviceStatus('espLiveStatus', false);
        document.getElementById('espIPInfo').textContent = 'ไม่สามารถเชื่อมต่อ';
        setDeviceStatus('pirLiveStatus', false);
        document.getElementById('pirInfo').textContent = 'ไม่สามารถตรวจสอบ';
    }
}

function setDeviceStatus(elementId, online) {
    const el = document.getElementById(elementId);
    if (!el) return;
    if (online) {
        el.innerHTML = '<span class="w-2 h-2 rounded-full bg-green-400 pulse-dot"></span><span class="text-xs text-green-400">ออนไลน์</span>';
    } else {
        el.innerHTML = '<span class="w-2 h-2 rounded-full bg-red-400"></span><span class="text-xs text-red-400">ออฟไลน์</span>';
    }
}

function checkCameras() {
    // Camera outside
    const imgOut = new Image();
    imgOut.onload = function() { setDeviceStatus('camOutLiveStatus', true); };
    imgOut.onerror = function() { setDeviceStatus('camOutLiveStatus', false); };
    setTimeout(() => { if (!imgOut.complete) setDeviceStatus('camOutLiveStatus', false); }, 5000);
    imgOut.src = FACE_SERVER + '/api/snapshot/outside?t=' + Date.now();

    // Camera inside
    const imgIn = new Image();
    imgIn.onload = function() { setDeviceStatus('camInLiveStatus', true); };
    imgIn.onerror = function() {
        // Inside camera might be disabled
        document.getElementById('camInLiveStatus').innerHTML =
            '<span class="w-2 h-2 rounded-full bg-gray-500"></span><span class="text-xs text-gray-500">ปิดใช้งาน</span>';
    };
    setTimeout(() => {
        if (!imgIn.complete) {
            document.getElementById('camInLiveStatus').innerHTML =
                '<span class="w-2 h-2 rounded-full bg-gray-500"></span><span class="text-xs text-gray-500">ปิดใช้งาน</span>';
        }
    }, 5000);
    imgIn.src = FACE_SERVER + '/api/snapshot/inside?t=' + Date.now();
}

async function checkFaceRecognition() {
    try {
        const res = await fetchAPI('api/pi_health.php');
        if (res && res.cpu_percent !== undefined) {
            setDeviceStatus('faceLiveStatus', true);
            document.getElementById('faceInfo').textContent = 'CPU: ' + res.cpu_percent + '% | RAM: ' + res.ram_percent + '%';
        } else {
            setDeviceStatus('faceLiveStatus', false);
        }
    } catch (e) {
        setDeviceStatus('faceLiveStatus', false);
    }
}

function recheckDevices() {
    const btn = document.getElementById('btnRecheck');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังตรวจสอบ...';
    checkDevicesLive().then(() => {
        btn.innerHTML = '<i class="fas fa-rotate"></i> ตรวจสอบอีกครั้ง';
        showToast('ตรวจสอบอุปกรณ์เสร็จแล้ว', 'info');
    });
}

// ============================================================
// System Update
// ============================================================
async function pullUpdate() {
    const btn = document.getElementById('btnUpdate');
    const log = document.getElementById('updateLog');
    const logText = document.getElementById('updateLogText');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังอัพเดท...';
    log.classList.remove('hidden');
    logText.textContent = 'Running git pull origin main...\n';

    try {
        const res = await fetch('api/update.php?action=pull', { method: 'POST' });
        const data = await res.json();

        logText.textContent += (data.output || '') + '\n';

        if (data.success) {
            const nv = data.new_version;
            logText.textContent += `\nUpdate successful! v${nv?.version || '?'} (build ${nv?.build || '?'})\n`;
            showToast('อัพเดทสำเร็จ! v' + (nv?.version || '?') + ' — กำลัง reload...', 'success', 3000);
            document.getElementById('currentVersion').textContent = 'v' + (nv?.version || '?');
            setTimeout(() => location.reload(), 2000);
        } else {
            logText.textContent += '\nError: ' + (data.error || 'Unknown') + '\n';
            showToast('อัพเดทล้มเหลว: ' + (data.error || ''), 'error');
        }
    } catch (e) {
        logText.textContent += '\nException: ' + e.message + '\n';
        showToast('เกิดข้อผิดพลาด', 'error');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-download"></i> อัพเดทเลย';
}

// ============================================================
// Camera Detection & Assignment
// ============================================================
let _detectedCameras = [];
let _prevCameraCount = -1;

async function detectCameras() {
    const grid = document.getElementById('cameraGrid');
    const result = document.getElementById('cameraDetectResult');
    const assignBtn = document.getElementById('cameraAssignBtn');

    result.innerHTML = '<div class="flex items-center gap-2"><i class="fas fa-spinner fa-spin text-blue-400"></i> กำลังสแกนกล้อง USB ที่เชื่อมต่อ...</div>';
    grid.innerHTML = '';
    assignBtn.classList.add('hidden');

    try {
        const resp = await fetch(FACE_SERVER + '/api/cameras/detect', {signal: AbortSignal.timeout(15000)});
        if (!resp.ok) throw new Error('Pi ไม่ตอบสนอง');
        const data = await resp.json();
        _detectedCameras = data.cameras || [];

        // ตรวจว่าจำนวนกล้องเปลี่ยนไหม (สำหรับ auto-poll)
        if (_prevCameraCount >= 0 && _prevCameraCount !== _detectedCameras.length) {
            showToast(`ตรวจพบการเปลี่ยนแปลง: ${_detectedCameras.length} กล้อง (ก่อนหน้า ${_prevCameraCount})`, 'info');
        }
        _prevCameraCount = _detectedCameras.length;

        if (_detectedCameras.length === 0) {
            result.innerHTML = '<div class="flex items-center gap-2 text-yellow-400"><i class="fas fa-exclamation-triangle"></i> ไม่พบกล้อง USB — ลองเสียบกล้องแล้วกดสแกนอีกครั้ง</div>';
            return;
        }

        result.innerHTML = `<div class="flex items-center gap-2 text-green-400"><i class="fas fa-check-circle"></i> พบ ${_detectedCameras.length} กล้อง — เลือกกำหนดตำแหน่ง (ด้านนอก / ด้านใน)</div>`;

        _detectedCameras.forEach(cam => {
            const card = document.createElement('div');
            card.className = 'bg-white/5 rounded-xl p-4 border border-white/10';
            card.innerHTML = `
                <div class="mb-3">
                    <img src="data:image/jpeg;base64,${cam.thumbnail}" class="w-full rounded-lg aspect-video object-cover" alt="Camera ${cam.id}">
                </div>
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <p class="text-sm font-medium">${cam.device}</p>
                        <p class="text-xs text-gray-500">${cam.name}</p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full ${cam.can_read ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'}">
                        ${cam.can_read ? 'ใช้งานได้' : 'ใช้ไม่ได้'}
                    </span>
                </div>
                <select id="camAssign_${cam.id}" class="w-full bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500 mt-2">
                    <option value="">ไม่ใช้งาน</option>
                    <option value="outside" ${cam.assigned === 'outside' ? 'selected' : ''}>กล้องด้านนอก</option>
                    <option value="inside" ${cam.assigned === 'inside' ? 'selected' : ''}>กล้องด้านใน</option>
                </select>
            `;
            grid.appendChild(card);
        });

        assignBtn.classList.remove('hidden');
    } catch (e) {
        result.innerHTML = `<div class="flex items-center gap-2 text-red-400"><i class="fas fa-times-circle"></i> สแกนไม่ได้: ${e.message}</div>`;
    }
}

async function assignCameras() {
    let outsideId = -1;
    let insideId = -1;

    _detectedCameras.forEach(cam => {
        const sel = document.getElementById(`camAssign_${cam.id}`);
        if (sel) {
            if (sel.value === 'outside') outsideId = cam.id;
            if (sel.value === 'inside') insideId = cam.id;
        }
    });

    try {
        const resp = await fetch(FACE_SERVER + '/api/cameras/assign', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ outside: outsideId, inside: insideId })
        });
        const data = await resp.json();
        if (data.success) {
            showToast('บันทึกการกำหนดกล้องสำเร็จ — กรุณา restart face_server บน Pi', 'success');
        } else {
            showToast(data.error || 'เกิดข้อผิดพลาด', 'error');
        }
    } catch (e) {
        showToast('ไม่สามารถบันทึก: ' + e.message, 'error');
    }
}

// ============================================================
// Pi Web Terminal
// ============================================================
let _cmdHistory = [];
let _cmdIndex = -1;

async function runTerminalCmd() {
    const input = document.getElementById('terminalInput');
    const cmd = input.value.trim();
    if (!cmd) return;
    input.value = '';
    _cmdHistory.unshift(cmd);
    _cmdIndex = -1;
    runQuickCmd(cmd);
}

async function runQuickCmd(cmd) {
    const output = document.getElementById('terminalOutput');
    output.innerHTML += `\n<span class="text-green-400">root1@raspberrypi:~/bunny-door$</span> <span class="text-white">${escapeHtml(cmd)}</span>\n`;
    output.innerHTML += '<span class="text-gray-500">กำลังรัน...</span>\n';
    output.scrollTop = output.scrollHeight;

    try {
        const resp = await fetch(FACE_SERVER + '/api/terminal', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ command: cmd }),
            signal: AbortSignal.timeout(35000)
        });
        const data = await resp.json();

        // ลบ "กำลังรัน..."
        const lines = output.innerHTML.split('\n');
        lines.pop(); // remove trailing
        lines.pop(); // remove "กำลังรัน..."
        output.innerHTML = lines.join('\n');

        if (data.error) {
            output.innerHTML += `\n<span class="text-red-400">${escapeHtml(data.error)}</span>\n`;
        } else {
            if (data.stdout) output.innerHTML += `\n<span class="text-gray-200">${escapeHtml(data.stdout)}</span>`;
            if (data.stderr) output.innerHTML += `<span class="text-yellow-400">${escapeHtml(data.stderr)}</span>`;
            if (data.returncode !== 0) {
                output.innerHTML += `<span class="text-red-400">[exit code: ${data.returncode}]</span>\n`;
            }
        }
    } catch (e) {
        output.innerHTML += `\n<span class="text-red-400">Error: ${escapeHtml(e.message)}</span>\n`;
    }

    output.scrollTop = output.scrollHeight;
}

function clearTerminal() {
    document.getElementById('terminalOutput').innerHTML = '<span class="text-green-400">root1@raspberrypi:~/bunny-door$</span> <span class="text-gray-500">Terminal ถูกล้าง</span>';
}

// Keyboard: arrow up/down for history
document.addEventListener('keydown', (e) => {
    const input = document.getElementById('terminalInput');
    if (document.activeElement !== input || _cmdHistory.length === 0) return;
    if (e.key === 'ArrowUp') {
        e.preventDefault();
        _cmdIndex = Math.min(_cmdIndex + 1, _cmdHistory.length - 1);
        input.value = _cmdHistory[_cmdIndex] || '';
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        _cmdIndex = Math.max(_cmdIndex - 1, -1);
        input.value = _cmdIndex >= 0 ? _cmdHistory[_cmdIndex] : '';
    }
});

// ============================================================
// Server Log Viewer
// ============================================================
let _logInterval = null;

async function fetchServerLogs() {
    const level = document.getElementById('logLevel')?.value || '';
    const search = document.getElementById('logSearch')?.value || '';
    const lines = document.getElementById('logLines')?.value || 200;
    const output = document.getElementById('logOutput');

    try {
        const params = new URLSearchParams({ lines, level, search });
        const resp = await fetch(FACE_SERVER + '/api/logs/server?' + params, {signal: AbortSignal.timeout(5000)});
        if (!resp.ok) throw new Error('ไม่สามารถดึง log ได้');
        const data = await resp.json();

        // สีตาม level
        const colored = data.logs.map(line => {
            if (line.includes('[ERROR]')) return `<span class="text-red-400">${escapeHtml(line)}</span>`;
            if (line.includes('[WARNING]')) return `<span class="text-yellow-400">${escapeHtml(line)}</span>`;
            if (line.includes('[Camera]')) return `<span class="text-cyan-400">${escapeHtml(line)}</span>`;
            if (line.includes('[PIR]') || line.includes('[DOOR]')) return `<span class="text-green-400">${escapeHtml(line)}</span>`;
            if (line.includes('[ESP32]')) return `<span class="text-yellow-300">${escapeHtml(line)}</span>`;
            return `<span class="text-gray-300">${escapeHtml(line)}</span>`;
        });

        output.innerHTML = colored.join('\n') || '<span class="text-gray-600">ไม่มี log</span>';

        // Auto-scroll
        if (document.getElementById('logAutoScroll')?.checked) {
            output.scrollTop = output.scrollHeight;
        }

        // Stats
        document.getElementById('logFileInfo').textContent = `${data.file_size_mb} MB`;
        document.getElementById('logStats').textContent = `แสดง ${data.logs.length} / ${data.filtered_lines} บรรทัด (ทั้งหมด ${data.total_lines})`;
    } catch (e) {
        output.innerHTML = `<span class="text-red-400">ไม่สามารถดึง log: ${escapeHtml(e.message)}</span>`;
    }
}

function escapeHtml(str) {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

async function clearServerLogs() {
    if (!confirm('ต้องการลบ log ทั้งหมด?')) return;
    try {
        const resp = await fetch(FACE_SERVER + '/api/logs/server/clear', { method: 'POST' });
        const data = await resp.json();
        if (data.success) {
            showToast('ลบ log เรียบร้อย', 'success');
            fetchServerLogs();
        }
    } catch (e) {
        showToast('ลบไม่ได้: ' + e.message, 'error');
    }
}

function toggleLogAutoRefresh() {
    if (document.getElementById('logAutoRefresh')?.checked) {
        _logInterval = setInterval(fetchServerLogs, 3000);
    } else {
        clearInterval(_logInterval);
        _logInterval = null;
    }
}

// Auto-poll ทุก 5 วินาทีเพื่อตรวจจับกล้องใหม่เมื่ออยู่ tab กล้อง
let _cameraPollInterval = null;
const origSwitchTab = switchSettingsTab;
switchSettingsTab = function(tab) {
    origSwitchTab(tab);
    if (tab === 'camera') {
        detectCameras();
        if (!_cameraPollInterval) {
            _cameraPollInterval = setInterval(detectCameras, 8000);
        }
    } else {
        if (_cameraPollInterval) {
            clearInterval(_cameraPollInterval);
            _cameraPollInterval = null;
        }
    }
    if (tab === 'logs') {
        fetchServerLogs();
    } else {
        if (_logInterval) {
            clearInterval(_logInterval);
            _logInterval = null;
            const cb = document.getElementById('logAutoRefresh');
            if (cb) cb.checked = false;
        }
    }
};
</script>

<?php include 'includes/footer.php'; ?>
