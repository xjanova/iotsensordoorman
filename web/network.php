<?php $pageTitle = 'ตั้งค่าเครือข่าย - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<div class="mb-8">
    <h2 class="text-2xl font-bold">ตั้งค่าเครือข่าย</h2>
    <p class="text-gray-400 text-sm mt-1">จัดการ IP ของอุปกรณ์ทั้งหมดในระบบ — เมื่อย้าย WiFi ใหม่ แก้ที่นี่ที่เดียว</p>
</div>

<!-- Network Map -->
<div class="glass rounded-2xl p-6 mb-6">
    <h3 class="font-bold mb-4 flex items-center gap-2">
        <i class="fas fa-network-wired text-cyan-400"></i> แผนผังเครือข่าย
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="networkMap">
        <!-- Laragon/Web Server -->
        <div class="bg-white/5 rounded-xl p-5 text-center relative" id="cardWeb">
            <div class="w-14 h-14 bg-blue-500/20 rounded-2xl flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-server text-blue-400 text-2xl"></i>
            </div>
            <p class="font-bold text-white">Laragon (Web + DB)</p>
            <p class="text-xs text-gray-500 mt-1">เว็บเซิร์ฟเวอร์ + ฐานข้อมูล</p>
            <div class="mt-3 bg-black/30 rounded-lg px-3 py-2">
                <span class="text-xs text-gray-500">IP:</span>
                <span class="text-sm font-mono text-blue-400" id="webIP">กำลังตรวจจับ...</span>
            </div>
            <div class="mt-2 flex items-center justify-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-green-400 pulse-dot"></span>
                <span class="text-xs text-green-400">ออนไลน์ (เครื่องนี้)</span>
            </div>
        </div>

        <!-- Raspberry Pi -->
        <div class="bg-white/5 rounded-xl p-5 text-center relative" id="cardPi">
            <div class="w-14 h-14 bg-green-500/20 rounded-2xl flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-microchip text-green-400 text-2xl"></i>
            </div>
            <p class="font-bold text-white">Raspberry Pi</p>
            <p class="text-xs text-gray-500 mt-1">กล้อง + Face Recognition</p>
            <div class="mt-3 bg-black/30 rounded-lg px-3 py-2">
                <span class="text-xs text-gray-500">IP:</span>
                <span class="text-sm font-mono text-green-400" id="piIP">กำลังตรวจจับ...</span>
            </div>
            <div class="mt-2 flex items-center justify-center gap-1.5" id="piStatus">
                <span class="w-2 h-2 rounded-full bg-gray-500"></span>
                <span class="text-xs text-gray-500">กำลังตรวจสอบ...</span>
            </div>
        </div>

        <!-- ESP32 -->
        <div class="bg-white/5 rounded-xl p-5 text-center relative" id="cardESP">
            <div class="w-14 h-14 bg-yellow-500/20 rounded-2xl flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-bolt text-yellow-400 text-2xl"></i>
            </div>
            <p class="font-bold text-white">ESP32</p>
            <p class="text-xs text-gray-500 mt-1">เซ็นเซอร์ + ควบคุมประตู</p>
            <div class="mt-3 bg-black/30 rounded-lg px-3 py-2">
                <span class="text-xs text-gray-500">IP:</span>
                <span class="text-sm font-mono text-yellow-400" id="espIP">ไม่ทราบ</span>
            </div>
            <div class="mt-2 flex items-center justify-center gap-1.5" id="espStatus">
                <span class="w-2 h-2 rounded-full bg-gray-500"></span>
                <span class="text-xs text-gray-500">กำลังตรวจสอบ...</span>
            </div>
        </div>
    </div>

    <!-- Connection arrows (text-based) -->
    <div class="mt-4 bg-white/5 rounded-xl p-4">
        <p class="text-xs text-gray-500 text-center">
            <span class="text-blue-400">Laragon</span>
            <i class="fas fa-arrows-left-right text-gray-600 mx-2"></i>
            <span class="text-green-400">Raspberry Pi</span>
            <i class="fas fa-arrows-left-right text-gray-600 mx-2"></i>
            <span class="text-yellow-400">ESP32</span>
        </p>
        <p class="text-xs text-gray-600 text-center mt-1">อุปกรณ์ทั้งหมดต้องอยู่ใน WiFi เดียวกัน</p>
    </div>
</div>

<!-- IP Configuration Form -->
<div class="glass rounded-2xl p-6 mb-6">
    <h3 class="font-bold mb-2 flex items-center gap-2">
        <i class="fas fa-pen-to-square text-orange-400"></i> แก้ไข IP เครือข่าย
    </h3>
    <p class="text-xs text-gray-500 mb-6">เมื่อย้ายไปใช้ WiFi ใหม่ ให้แก้ IP ด้านล่างแล้วกดบันทึก ระบบจะอัพเดทค่าให้อัตโนมัติ</p>

    <div class="space-y-4">
        <!-- Laragon IP (auto-detected, read-only info) -->
        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-server text-blue-400"></i>
                </div>
                <div>
                    <p class="text-sm font-medium">IP ของ Laragon (เครื่องนี้)</p>
                    <p class="text-xs text-gray-500">ตรวจจับอัตโนมัติ — Pi และ ESP32 จะเชื่อมมาที่ IP นี้</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="text" id="inputWebIP" readonly
                       class="bg-blue-500/10 border border-blue-500/30 rounded-lg px-3 py-2 text-blue-400 w-48 text-right focus:outline-none font-mono cursor-not-allowed">
                <span class="text-xs text-blue-500"><i class="fas fa-circle-check"></i> auto</span>
            </div>
        </div>

        <!-- Pi IP -->
        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-microchip text-green-400"></i>
                </div>
                <div>
                    <p class="text-sm font-medium">IP ของ Raspberry Pi</p>
                    <p class="text-xs text-gray-500">Face Recognition Server — อัพเดทในไฟล์ <code class="text-green-400">web/.env</code></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="text" id="inputPiIP" placeholder="192.168.1.121"
                       class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white w-48 text-right focus:outline-none focus:border-green-500 font-mono">
                <button type="button" onclick="testPi()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-xs transition" title="ทดสอบการเชื่อมต่อ">
                    <i class="fas fa-plug" id="testPiIcon"></i>
                </button>
            </div>
        </div>

        <!-- ESP32 IP -->
        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-bolt text-yellow-400"></i>
                </div>
                <div>
                    <p class="text-sm font-medium">IP ของ ESP32</p>
                    <p class="text-xs text-gray-500">ดูได้จาก Serial Monitor ตอน ESP32 เชื่อมต่อ WiFi</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="text" id="inputEspIP" placeholder="192.168.1.100"
                       class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white w-48 text-right focus:outline-none focus:border-yellow-500 font-mono">
                <button type="button" onclick="testEsp()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-2 rounded-lg text-xs transition" title="ทดสอบการเชื่อมต่อ">
                    <i class="fas fa-plug" id="testEspIcon"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Save All Button -->
    <div class="mt-6 flex items-center gap-3">
        <button type="button" onclick="saveAllIPs()" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl transition font-medium flex items-center gap-2">
            <i class="fas fa-save"></i> บันทึก IP ทั้งหมด
        </button>
        <span class="text-xs text-gray-500" id="saveStatus"></span>
    </div>
</div>

<!-- What Gets Updated -->
<div class="glass rounded-2xl p-6 mb-6">
    <h3 class="font-bold mb-4 flex items-center gap-2">
        <i class="fas fa-list-check text-purple-400"></i> รายการที่ต้องอัพเดทเมื่อเปลี่ยน WiFi
    </h3>
    <div class="space-y-3" id="updateChecklist">
        <!-- Item 1 -->
        <div class="flex items-start gap-3 p-3 bg-white/5 rounded-xl" id="check1">
            <div class="w-6 h-6 rounded-full bg-blue-500/20 flex items-center justify-center mt-0.5 flex-shrink-0">
                <i class="fas fa-file-code text-blue-400 text-xs"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium">web/.env — <code class="text-blue-400">FACE_SERVER_URL</code></p>
                <p class="text-xs text-gray-500">เว็บจะใช้ IP นี้เรียก API ของ Pi (ถ่ายรูป, สุขภาพระบบ)</p>
                <p class="text-xs mt-1"><span class="text-gray-600">อัพเดทเป็น:</span> <code class="text-green-400" id="preview1">-</code></p>
                <span class="text-xs text-green-500 hidden" id="check1ok"><i class="fas fa-check-circle"></i> กดบันทึกจะอัพเดทอัตโนมัติ</span>
            </div>
        </div>

        <!-- Item 2 -->
        <div class="flex items-start gap-3 p-3 bg-white/5 rounded-xl" id="check2">
            <div class="w-6 h-6 rounded-full bg-green-500/20 flex items-center justify-center mt-0.5 flex-shrink-0">
                <i class="fas fa-file-code text-green-400 text-xs"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium">python/.env (บน Pi) — <code class="text-green-400">DB_HOST</code></p>
                <p class="text-xs text-gray-500">Pi จะใช้ IP นี้เชื่อมต่อ MySQL บน Laragon</p>
                <p class="text-xs mt-1"><span class="text-gray-600">อัพเดทเป็น:</span> <code class="text-green-400" id="preview2">-</code></p>
                <span class="text-xs text-yellow-500" id="check2warn"><i class="fas fa-exclamation-triangle"></i> ต้อง SSH เข้า Pi แก้เอง (ดูคำสั่งด้านล่าง)</span>
            </div>
        </div>

        <!-- Item 3 -->
        <div class="flex items-start gap-3 p-3 bg-white/5 rounded-xl" id="check3">
            <div class="w-6 h-6 rounded-full bg-yellow-500/20 flex items-center justify-center mt-0.5 flex-shrink-0">
                <i class="fas fa-microchip text-yellow-400 text-xs"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium">ESP32 — <code class="text-yellow-400">SERVER_URL</code> + <code class="text-yellow-400">WiFi</code></p>
                <p class="text-xs text-gray-500">ESP32 ต้องรู้ IP ของ Pi เพื่อส่ง motion/heartbeat</p>
                <p class="text-xs mt-1"><span class="text-gray-600">อัพเดทเป็น:</span> <code class="text-green-400" id="preview3">-</code></p>
                <span class="text-xs text-yellow-500"><i class="fas fa-exclamation-triangle"></i> ต้อง flash โค้ดใหม่ผ่าน Arduino IDE (สร้างโค้ดด้านล่าง)</span>
            </div>
        </div>
    </div>
</div>

<!-- Pi SSH Command -->
<div class="glass rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold flex items-center gap-2">
            <i class="fas fa-terminal text-green-400"></i> คำสั่งแก้ไขบน Raspberry Pi
        </h3>
        <button type="button" onclick="copyPiCommand()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition flex items-center gap-2">
            <i class="fas fa-copy"></i> คัดลอกคำสั่ง
        </button>
    </div>
    <p class="text-xs text-gray-500 mb-3">SSH เข้า Pi แล้ววางคำสั่งนี้ — จะแก้ .env + รีสตาร์ท face_server อัตโนมัติ</p>
    <div class="bg-black/40 rounded-xl p-4 overflow-x-auto">
        <pre id="piCommand" class="text-sm font-mono text-green-400 whitespace-pre leading-relaxed">กำลังสร้างคำสั่ง...</pre>
    </div>
    <p class="text-xs text-gray-600 mt-3"><i class="fas fa-info-circle"></i> SSH เข้า Pi: <code class="text-gray-400" id="sshCommand">ssh root1@...</code></p>
</div>

<!-- ESP32 Code Generator -->
<div class="glass rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold flex items-center gap-2">
            <i class="fas fa-code text-yellow-400"></i> สร้างโค้ด ESP32 (ใช้ IP ปัจจุบัน)
        </h3>
        <button type="button" onclick="generateESP32Code()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg text-sm transition flex items-center gap-2">
            <i class="fas fa-wand-magic-sparkles"></i> สร้าง & คัดลอกโค้ด
        </button>
    </div>
    <p class="text-xs text-gray-500 mb-3">สร้างโค้ด Arduino สำหรับ ESP32 โดยใส่ IP และ WiFi ที่ถูกต้องให้อัตโนมัติ</p>

    <!-- WiFi Config for ESP32 -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
            <div>
                <p class="text-sm font-medium">WiFi SSID</p>
                <p class="text-xs text-gray-500">ชื่อ WiFi ที่ใช้</p>
            </div>
            <input type="text" id="inputWifiSSID" placeholder="ชื่อ WiFi"
                   class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white w-44 text-right focus:outline-none focus:border-yellow-500">
        </div>
        <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
            <div>
                <p class="text-sm font-medium">WiFi Password</p>
                <p class="text-xs text-gray-500">รหัสผ่าน WiFi</p>
            </div>
            <div class="relative">
                <input type="password" id="inputWifiPass" placeholder="รหัสผ่าน"
                       class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 pr-10 text-white w-44 text-right focus:outline-none focus:border-yellow-500">
                <button type="button" onclick="togglePass()" class="absolute right-3 top-2.5 text-gray-500 hover:text-white">
                    <i class="fas fa-eye" id="passIcon"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="bg-black/40 rounded-xl p-4 overflow-x-auto max-h-96 overflow-y-auto hidden" id="esp32CodeBox">
        <pre id="esp32Code" class="text-sm font-mono text-green-400 whitespace-pre leading-relaxed"></pre>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    detectNetwork();
});

// ============================================================
// Detect current network
// ============================================================
async function detectNetwork() {
    try {
        const res = await fetchAPI('api/network.php?action=detect');
        if (!res.success) return;

        // Web server
        const webIP = res.web_server.primary_ip;
        document.getElementById('webIP').textContent = webIP;
        document.getElementById('inputWebIP').value = webIP;

        // Pi
        const piIP = res.raspberry_pi.ip || '';
        document.getElementById('piIP').textContent = piIP || 'ไม่ได้ตั้งค่า';
        document.getElementById('inputPiIP').value = piIP;
        updatePiStatus(res.raspberry_pi.online);

        // ESP32
        const espIP = res.esp32.ip || '';
        document.getElementById('espIP').textContent = espIP || 'ไม่ทราบ';
        document.getElementById('inputEspIP').value = espIP;

        // Test ESP32 connection if IP is known
        if (espIP) {
            try {
                const espRes = await fetchAPI(`api/network.php?action=test&ip=${espIP}&port=80&type=esp32`);
                updateEspStatus(espRes.online);
            } catch (e) {
                updateEspStatus(false);
            }
        } else {
            updateEspStatus(false);
        }

        // WiFi
        document.getElementById('inputWifiSSID').value = res.esp32.wifi_ssid || '';

        // Update previews
        updatePreviews();

    } catch (e) {
        console.error('Detect failed:', e);
    }
}

// ============================================================
// Update preview values
// ============================================================
function updatePreviews() {
    const piIP = document.getElementById('inputPiIP').value;
    const webIP = document.getElementById('inputWebIP').value;

    document.getElementById('preview1').textContent = piIP ? `http://${piIP}:5000` : '-';
    document.getElementById('preview2').textContent = webIP || '-';
    document.getElementById('preview3').textContent = piIP ? `http://${piIP}:5000` : '-';

    // Update Pi command
    updatePiCommand();
    // Update SSH command
    document.getElementById('sshCommand').textContent = piIP ? `ssh root1@${piIP}` : 'ssh root1@<Pi IP>';
}

// Bind input changes
document.getElementById('inputPiIP').addEventListener('input', updatePreviews);
document.getElementById('inputWebIP').addEventListener('input', updatePreviews);

// ============================================================
// Update Pi command
// ============================================================
function updatePiCommand() {
    const webIP = document.getElementById('inputWebIP').value;
    const piIP = document.getElementById('inputPiIP').value;

    if (!webIP || !piIP) {
        document.getElementById('piCommand').textContent = '# กรุณาระบุ IP ก่อน';
        return;
    }

    document.getElementById('piCommand').textContent =
`# 1. แก้ไฟล์ .env บน Pi
cat > ~/bunny-door/python/.env << 'EOF'
DB_HOST=${webIP}
DB_PORT=3306
DB_USER=root
DB_PASSWORD=Theking222
DB_NAME=bunny_door
CAMERA_OUTSIDE_ID=0
CAMERA_INSIDE_ID=-1
EOF

# 2. รีสตาร์ท face_server
stopbunny && sleep 1 && startbunny

# 3. เช็คว่าทำงานมั้ย (รอ 3 วินาที)
sleep 3 && curl http://localhost:5000/api/system/health`;
}

// ============================================================
// Test connections
// ============================================================
async function testPi() {
    const ip = document.getElementById('inputPiIP').value;
    if (!ip) { showToast('กรุณาระบุ IP ของ Pi', 'warning'); return; }

    const icon = document.getElementById('testPiIcon');
    icon.className = 'fas fa-spinner fa-spin';

    try {
        const res = await fetchAPI(`api/network.php?action=test&ip=${ip}&port=5000&type=pi`);
        updatePiStatus(res.online);
        showToast(res.message, res.online ? 'success' : 'error');
    } catch (e) {
        updatePiStatus(false);
        showToast('ไม่สามารถเชื่อมต่อ Pi ได้', 'error');
    }

    icon.className = 'fas fa-plug';
}

async function testEsp() {
    const ip = document.getElementById('inputEspIP').value;
    if (!ip) { showToast('กรุณาระบุ IP ของ ESP32', 'warning'); return; }

    const icon = document.getElementById('testEspIcon');
    icon.className = 'fas fa-spinner fa-spin';

    try {
        const res = await fetchAPI(`api/network.php?action=test&ip=${ip}&port=80&type=esp32`);
        updateEspStatus(res.online);
        showToast(res.message, res.online ? 'success' : 'error');
    } catch (e) {
        updateEspStatus(false);
        showToast('ไม่สามารถเชื่อมต่อ ESP32 ได้', 'error');
    }

    icon.className = 'fas fa-plug';
}

function updatePiStatus(online) {
    const el = document.getElementById('piStatus');
    if (online) {
        el.innerHTML = '<span class="w-2 h-2 rounded-full bg-green-400 pulse-dot"></span><span class="text-xs text-green-400">ออนไลน์</span>';
    } else {
        el.innerHTML = '<span class="w-2 h-2 rounded-full bg-red-400"></span><span class="text-xs text-red-400">ออฟไลน์</span>';
    }
}

function updateEspStatus(online) {
    const el = document.getElementById('espStatus');
    if (online) {
        el.innerHTML = '<span class="w-2 h-2 rounded-full bg-green-400 pulse-dot"></span><span class="text-xs text-green-400">ออนไลน์</span>';
    } else {
        el.innerHTML = '<span class="w-2 h-2 rounded-full bg-red-400"></span><span class="text-xs text-red-400">ออฟไลน์</span>';
    }
}

// ============================================================
// Save all IPs
// ============================================================
async function saveAllIPs() {
    const piIP = document.getElementById('inputPiIP').value;
    const espIP = document.getElementById('inputEspIP').value;
    const webIP = document.getElementById('inputWebIP').value;

    if (!piIP) {
        showToast('กรุณาระบุ IP ของ Raspberry Pi', 'warning');
        return;
    }

    const status = document.getElementById('saveStatus');
    status.innerHTML = '<i class="fas fa-spinner fa-spin text-blue-400"></i> กำลังบันทึก...';

    let success = true;
    let messages = [];

    // 1. Save web .env (FACE_SERVER_URL)
    try {
        const res = await postAPI('api/network.php?action=save_web_env', { pi_ip: piIP });
        if (res.success) {
            messages.push('web/.env');
        } else {
            success = false;
            messages.push('web/.env FAILED: ' + res.error);
        }
    } catch (e) {
        success = false;
        messages.push('web/.env FAILED');
    }

    // 2. Save ESP32 settings in DB + notify Pi
    try {
        const res = await postAPI('api/network.php?action=save_esp32', {
            server_url: `http://${piIP}:5000`,
            esp32_ip: espIP,
            wifi_ssid: document.getElementById('inputWifiSSID')?.value || ''
        });
        if (res.success) messages.push('ESP32 settings');
    } catch (e) {}

    if (success) {
        status.innerHTML = '<i class="fas fa-check-circle text-green-400"></i> <span class="text-green-400">บันทึกสำเร็จ!</span>';
        showToast('บันทึก IP สำเร็จ! อย่าลืมแก้บน Pi ด้วย (ดูคำสั่งด้านล่าง)', 'success', 5000);
    } else {
        status.innerHTML = '<i class="fas fa-times-circle text-red-400"></i> <span class="text-red-400">บันทึกไม่สำเร็จ</span>';
        showToast('เกิดข้อผิดพลาด: ' + messages.join(', '), 'error');
    }
}

// ============================================================
// Copy Pi command
// ============================================================
function copyPiCommand() {
    const text = document.getElementById('piCommand').textContent;
    navigator.clipboard.writeText(text).then(() => {
        showToast('คัดลอกคำสั่งแล้ว — วางบน Pi Terminal ได้เลย', 'success');
    }).catch(() => {
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        showToast('คัดลอกคำสั่งแล้ว', 'success');
    });
}

// ============================================================
// Toggle password visibility
// ============================================================
function togglePass() {
    const inp = document.getElementById('inputWifiPass');
    const icon = document.getElementById('passIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// ============================================================
// Generate ESP32 Code
// ============================================================
function generateESP32Code() {
    const piIP = document.getElementById('inputPiIP').value || '192.168.1.121';
    const wifiSSID = document.getElementById('inputWifiSSID').value || 'YOUR_WIFI_SSID';
    const wifiPass = document.getElementById('inputWifiPass').value || 'YOUR_WIFI_PASSWORD';

    const code = `/*
 * ============================================================
 * Bunny Door System - ESP32 Door Controller
 * ============================================================
 * โค้ดนี้ถูกสร้างอัตโนมัติจากหน้าตั้งค่าเครือข่าย
 * วันที่สร้าง: ${new Date().toLocaleString('th-TH')}
 * ============================================================
 * การต่อสาย:
 *   Relay        -> GPIO 4
 *   LED Status   -> GPIO 2
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
// Configuration
// ============================================================
const char* WIFI_SSID     = "${wifiSSID}";
const char* WIFI_PASSWORD = "${wifiPass}";
const char* SERVER_URL    = "http://${piIP}:5000";  // Raspberry Pi

// ============================================================
// Pin Definitions
// ============================================================
#define PIN_PIR_OUTSIDE    27
#define PIN_PIR_INSIDE     26
#define PIN_RELAY          4
#define PIN_BUZZER         33
#define PIN_LED_STATUS     2
#define PIN_EMERGENCY_BTN  13

// ============================================================
// Constants
// ============================================================
#define DOOR_UNLOCK_MS         7000
#define PIR_COOLDOWN_MS        3000
#define HEARTBEAT_INTERVAL_MS  10000
#define DEBOUNCE_MS            200

#define RELAY_UNLOCK  HIGH
#define RELAY_LOCK    LOW

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
    // Scan WiFi networks first
    Serial.println("\\n[WiFi] Scanning networks...");
    int n = WiFi.scanNetworks();
    if (n == 0) {
        Serial.println("[WiFi] No networks found!");
    } else {
        Serial.printf("[WiFi] Found %d networks:\\n", n);
        for (int i = 0; i < n; i++) {
            Serial.printf("  %d) %-20s  CH:%d  RSSI:%d dBm  %s\\n",
                i + 1,
                WiFi.SSID(i).c_str(),
                WiFi.channel(i),
                WiFi.RSSI(i),
                (WiFi.encryptionType(i) == WIFI_AUTH_OPEN) ? "Open" : "Encrypted");
        }
    }
    WiFi.scanDelete();

    Serial.printf("\\n[WiFi] Connecting to %s", WIFI_SSID);
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
    StaticJsonDocument<128> doc;
    doc["device"] = "esp32";
    doc["door"] = doorLocked ? "locked" : "unlocked";
    doc["pir_outside"] = pirOutsideState;
    doc["pir_inside"] = pirInsideState;
    doc["rssi"] = WiFi.RSSI();
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
    document.getElementById('esp32CodeBox').classList.remove('hidden');
    document.getElementById('esp32Code').textContent = code;

    // Copy to clipboard
    navigator.clipboard.writeText(code).then(() => {
        showToast('สร้างโค้ด ESP32 สำเร็จ & คัดลอกไปยัง Clipboard แล้ว!', 'success', 4000);
    }).catch(() => {
        const ta = document.createElement('textarea');
        ta.value = code;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        showToast('สร้างโค้ด ESP32 สำเร็จ!', 'success');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
