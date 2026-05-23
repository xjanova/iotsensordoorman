<?php $pageTitle = 'ตั้งค่าเครือข่าย - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<div class="page-head">
    <div>
        <h1>ตั้งค่าเครือข่าย</h1>
        <div class="sub">จัดการ IP ของอุปกรณ์ทั้งหมด — เมื่อย้าย WiFi ใหม่ แก้ที่นี่ที่เดียว</div>
    </div>
</div>

<!-- Network Topology -->
<div class="card flush section">
    <div class="card-head">
        <div class="title">แผนผังเครือข่าย</div>
        <div class="sub">Laragon ↔ Pi ↔ ESP32 — อุปกรณ์ทั้งหมดต้องอยู่ใน WiFi เดียวกัน</div>
    </div>
    <div class="card-body">
        <div class="net-topo">
            <div class="net-node" id="cardWeb" style="--node-color: #2563eb;">
                <div class="net-icon"><?= ico('server', 22) ?></div>
                <div class="net-label">Laragon</div>
                <div class="net-sub">Web + Database</div>
                <div class="net-ip" id="webIP">…</div>
                <div class="net-status ok"><span class="led"></span>ออนไลน์ (เครื่องนี้)</div>
            </div>
            <div class="net-edge"><div class="line"></div><div class="lbl">DB · API</div></div>
            <div class="net-node" id="cardPi" style="--node-color: #10b981;">
                <div class="net-icon"><?= ico('cpu', 22) ?></div>
                <div class="net-label">Raspberry Pi</div>
                <div class="net-sub">Face Recognition</div>
                <div class="net-ip" id="piIP">…</div>
                <div class="net-status" id="piStatus"><span class="led"></span><span>ตรวจสอบ...</span></div>
            </div>
            <div class="net-edge"><div class="line"></div><div class="lbl">HTTP · Heartbeat</div></div>
            <div class="net-node" id="cardESP" style="--node-color: #d97706;">
                <div class="net-icon"><?= ico('chip', 22) ?></div>
                <div class="net-label">ESP32</div>
                <div class="net-sub">Door Controller</div>
                <div class="net-ip" id="espIP">—</div>
                <div class="net-status" id="espStatus"><span class="led"></span><span>ตรวจสอบ...</span></div>
            </div>
        </div>
    </div>
</div>

<!-- Auto-Pair (Devices on same WiFi) -->
<div class="card section">
    <div class="card-head" style="padding: 0 0 14px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
        <div>
            <div class="title">Auto-Pair อุปกรณ์ <span class="badge accent" id="pairEnabledBadge">เปิด</span></div>
            <div class="sub">อุปกรณ์ที่มี pairing token ตรงและอยู่ใน WiFi เดียวกัน จะปรากฏที่นี่อัตโนมัติ</div>
        </div>
        <div class="actions">
            <button type="button" onclick="refreshPairedDevices()" class="btn sm ghost"><?= ico('refresh', 12) ?> รีเฟรช</button>
            <button type="button" onclick="togglePairing()" class="btn sm" id="btnTogglePairing">ปิดรับ pair</button>
        </div>
    </div>

    <!-- Pairing Token -->
    <div class="net-field">
        <div class="net-field-icon" style="background: rgba(217,119,6,0.14); color: #d97706;"><?= ico('lock', 18) ?></div>
        <div class="net-field-text">
            <strong>Pairing Token <span class="tiny muted">(ใส่ใน .env ของ Pi / Preferences ของ ESP32)</span></strong>
            <p>shared secret ระหว่าง web ↔ Pi ↔ ESP32 — กัน device แปลกปลอมบน WiFi เดียวกัน</p>
        </div>
        <div class="row gap-2">
            <input type="text" id="pairToken" readonly class="input mono" style="width: 280px; text-align: center; letter-spacing: 1px;">
            <button type="button" onclick="copyPairToken()" class="btn sm" title="คัดลอก"><?= ico('copy', 12) ?></button>
            <button type="button" onclick="regenerateToken()" class="btn sm danger" title="สร้างใหม่"><?= ico('refresh', 12) ?> ใหม่</button>
        </div>
    </div>

    <!-- Discovered devices list -->
    <div style="margin-top: 16px;">
        <div id="pairedList" class="paired-list">
            <div class="empty" style="padding: 16px; text-align: center; color: var(--text-2);">กำลังโหลด...</div>
        </div>
    </div>
</div>

<style>
.paired-list .pair-row { display: grid; grid-template-columns: auto 1fr auto auto; gap: 12px; align-items: center; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 8px; background: var(--bg-2); }
.paired-list .pair-row.pending { border-color: color-mix(in oklch, var(--warn) 35%, var(--border)); background: color-mix(in oklch, var(--warn) 5%, var(--bg-2)); }
.paired-list .pair-row.trusted { border-color: color-mix(in oklch, var(--ok) 25%, var(--border)); }
.paired-list .pair-row.revoked { opacity: 0.6; }
.paired-list .role-icon { width: 36px; height: 36px; border-radius: 8px; display: grid; place-items: center; }
.paired-list .role-icon.pi { background: rgba(16,185,129,0.16); color: #10b981; }
.paired-list .role-icon.esp32 { background: rgba(217,119,6,0.16); color: #d97706; }
.paired-list .role-icon.other { background: rgba(99,102,241,0.16); color: #6366f1; }
.paired-list .pair-meta { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.paired-list .pair-meta .name { font-size: 13px; font-weight: 600; }
.paired-list .pair-meta .info { font-size: 11.5px; color: var(--text-2); font-family: var(--font-mono); }
.paired-list .pair-actions { display: flex; gap: 6px; }
</style>

<!-- IP Configuration -->
<div class="card section">
    <div class="card-head" style="padding: 0 0 14px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
        <div>
            <div class="title">แก้ไข IP เครือข่าย</div>
            <div class="sub">เมื่อย้าย WiFi ใหม่ ให้แก้ IP ด้านล่างแล้วกดบันทึก</div>
        </div>
    </div>

    <!-- Laragon -->
    <div class="net-field">
        <div class="net-field-icon" style="background:rgba(37,99,235,0.14); color:#2563eb;"><?= ico('server', 18) ?></div>
        <div class="net-field-text">
            <strong>IP ของ Laragon (เครื่องนี้)</strong>
            <p>ตรวจจับอัตโนมัติ — Pi และ ESP32 จะเชื่อมมาที่ IP นี้</p>
        </div>
        <div class="row gap-2">
            <input type="text" id="inputWebIP" readonly class="input mono" style="width: 180px; text-align: right; color: #2563eb;">
            <span class="badge accent">auto</span>
        </div>
    </div>

    <!-- Pi -->
    <div class="net-field">
        <div class="net-field-icon" style="background:rgba(16,185,129,0.14); color:#10b981;"><?= ico('cpu', 18) ?></div>
        <div class="net-field-text">
            <strong>IP ของ Raspberry Pi</strong>
            <p>Face Recognition Server — อัพเดทในไฟล์ <code class="mono">web/.env</code></p>
        </div>
        <div class="row gap-2">
            <input type="text" id="inputPiIP" placeholder="192.168.1.121" class="input mono" style="width: 180px; text-align: right;">
            <button type="button" onclick="testPi()" class="btn sm" title="ทดสอบการเชื่อมต่อ"><?= ico('plug', 12) ?> <span id="testPiText">ทดสอบ</span></button>
        </div>
    </div>

    <!-- ESP32 -->
    <div class="net-field">
        <div class="net-field-icon" style="background:rgba(217,119,6,0.14); color:#d97706;"><?= ico('chip', 18) ?></div>
        <div class="net-field-text">
            <strong>IP ของ ESP32</strong>
            <p>ดูได้จาก Serial Monitor ตอน ESP32 เชื่อม WiFi</p>
        </div>
        <div class="row gap-2">
            <input type="text" id="inputEspIP" placeholder="192.168.1.100" class="input mono" style="width: 180px; text-align: right;">
            <button type="button" onclick="testEsp()" class="btn sm" title="ทดสอบการเชื่อมต่อ"><?= ico('plug', 12) ?> <span id="testEspText">ทดสอบ</span></button>
        </div>
    </div>

    <div class="row gap-3" style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border);">
        <button type="button" onclick="saveAllIPs()" class="btn primary"><?= ico('save', 14) ?> บันทึก IP ทั้งหมด</button>
        <span class="tiny muted" id="saveStatus"></span>
    </div>
</div>

<!-- Update Checklist -->
<div class="card section">
    <div class="card-head" style="padding: 0 0 14px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
        <div>
            <div class="title">รายการที่ต้องอัพเดทเมื่อเปลี่ยน WiFi</div>
            <div class="sub">3 ที่ที่ต้องแก้ — เครื่องนี้, Pi, ESP32</div>
        </div>
    </div>
    <div class="checklist-item">
        <div class="num">1</div>
        <div>
            <strong style="font-size:13px;">web/.env — <code class="mono text-accent">FACE_SERVER_URL</code></strong>
            <p class="tiny muted" style="margin: 3px 0;">เว็บจะใช้ IP นี้เรียก API ของ Pi</p>
            <p class="tiny"><span class="muted">อัพเดทเป็น:</span> <code class="mono text-ok" id="preview1">-</code></p>
            <span class="badge ok"><?= ico('check', 10) ?> อัตโนมัติ (auto)</span>
        </div>
    </div>
    <div class="checklist-item">
        <div class="num">2</div>
        <div>
            <strong style="font-size:13px;">python/.env (บน Pi) — <code class="mono text-accent">DB_HOST</code></strong>
            <p class="tiny muted" style="margin: 3px 0;">Pi จะใช้ IP นี้เชื่อม MySQL บน Laragon</p>
            <p class="tiny"><span class="muted">อัพเดทเป็น:</span> <code class="mono text-ok" id="preview2">-</code></p>
            <span class="badge warn">SSH (ดูคำสั่งด้านล่าง)</span>
        </div>
    </div>
    <div class="checklist-item">
        <div class="num">3</div>
        <div>
            <strong style="font-size:13px;">ESP32 — <code class="mono text-accent">SERVER_URL</code> + WiFi</strong>
            <p class="tiny muted" style="margin: 3px 0;">ESP32 ต้องรู้ IP ของ Pi เพื่อส่ง motion / heartbeat</p>
            <p class="tiny"><span class="muted">อัพเดทเป็น:</span> <code class="mono text-ok" id="preview3">-</code></p>
            <span class="badge warn">flash code ใหม่ (สร้างด้านล่าง)</span>
        </div>
    </div>
</div>

<!-- Pi SSH Command -->
<div class="card section">
    <div class="card-head" style="padding: 0 0 14px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
        <div>
            <div class="title">คำสั่งแก้ไขบน Raspberry Pi</div>
            <div class="sub">SSH เข้า Pi แล้ววางคำสั่งนี้</div>
        </div>
        <div class="actions">
            <button class="btn primary sm" onclick="copyPiCommand()"><?= ico('copy', 12) ?> คัดลอกคำสั่ง</button>
        </div>
    </div>
    <pre class="code-block term" id="piCommand">กำลังสร้างคำสั่ง...</pre>
    <p class="tiny muted" style="margin-top: 12px;">SSH เข้า Pi: <code class="mono" id="sshCommand">ssh root1@...</code></p>
</div>

<!-- ESP32 Code Generator -->
<div class="card section">
    <div class="card-head" style="padding: 0 0 14px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
        <div>
            <div class="title">สร้างโค้ด ESP32 (ใช้ IP ปัจจุบัน)</div>
            <div class="sub">สร้างโค้ด Arduino โดยใส่ IP/WiFi ที่ถูกต้องอัตโนมัติ</div>
        </div>
        <div class="actions">
            <button class="btn primary sm" onclick="generateESP32Code()"><?= ico('code', 12) ?> สร้าง & คัดลอก</button>
        </div>
    </div>

    <div class="grid cols-2" style="gap: 14px; margin-bottom: 14px;">
        <div class="net-field" style="border:0; padding: 0;">
            <div class="net-field-icon" style="background:var(--accent-soft); color:var(--accent);"><?= ico('wifi', 18) ?></div>
            <div class="net-field-text">
                <strong>WiFi SSID</strong>
                <p>ชื่อ WiFi ที่ใช้</p>
            </div>
            <input type="text" id="inputWifiSSID" placeholder="ชื่อ WiFi" class="input" style="width: 180px;">
        </div>
        <div class="net-field" style="border:0; padding: 0;">
            <div class="net-field-icon" style="background:var(--accent-soft); color:var(--accent);"><?= ico('lock', 18) ?></div>
            <div class="net-field-text">
                <strong>WiFi Password</strong>
                <p>รหัสผ่าน WiFi</p>
            </div>
            <div style="position: relative; width: 180px;">
                <input type="password" id="inputWifiPass" placeholder="รหัสผ่าน" class="input" style="padding-right: 36px;">
                <button type="button" onclick="togglePass()" class="icon-btn" style="position:absolute; right:2px; top:2px;"><?= ico('eye', 14) ?></button>
            </div>
        </div>
    </div>

    <pre class="code-block term hidden" id="esp32CodeBox" style="max-height: 480px;"><code id="esp32Code"></code></pre>
</div>

<script>
document.addEventListener('DOMContentLoaded', detectNetwork);

async function detectNetwork() {
    try {
        const res = await fetchAPI('api/network.php?action=detect');
        if (!res.success) return;
        const webIP = res.web_server.primary_ip;
        document.getElementById('webIP').textContent = webIP;
        document.getElementById('inputWebIP').value = webIP;

        const piIP = res.raspberry_pi.ip || '';
        document.getElementById('piIP').textContent = piIP || '—';
        document.getElementById('inputPiIP').value = piIP;
        updatePiStatus(res.raspberry_pi.online);

        const espIP = res.esp32.ip || '';
        document.getElementById('espIP').textContent = espIP || '—';
        document.getElementById('inputEspIP').value = espIP;

        if (espIP) {
            try {
                const espRes = await fetchAPI(`api/network.php?action=test&ip=${espIP}&port=80&type=esp32`);
                updateEspStatus(espRes.online);
            } catch { updateEspStatus(false); }
        } else { updateEspStatus(false); }

        document.getElementById('inputWifiSSID').value = res.esp32.wifi_ssid || '';
        updatePreviews();
    } catch (e) { console.error('Detect failed:', e); }
}

function updatePreviews() {
    const piIP = document.getElementById('inputPiIP').value;
    const webIP = document.getElementById('inputWebIP').value;
    document.getElementById('preview1').textContent = piIP ? `http://${piIP}:5000` : '-';
    document.getElementById('preview2').textContent = webIP || '-';
    document.getElementById('preview3').textContent = piIP ? `http://${piIP}:5000` : '-';
    updatePiCommand();
    document.getElementById('sshCommand').textContent = piIP ? `ssh root1@${piIP}` : 'ssh root1@<Pi IP>';
}
document.getElementById('inputPiIP').addEventListener('input', updatePreviews);
document.getElementById('inputWebIP').addEventListener('input', updatePreviews);

function updatePiCommand() {
    const webIP = document.getElementById('inputWebIP').value;
    const piIP = document.getElementById('inputPiIP').value;
    if (!webIP || !piIP) { document.getElementById('piCommand').textContent = '# กรุณาระบุ IP ก่อน'; return; }
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

# 3. เช็คว่าทำงานมั้ย
sleep 3 && curl http://localhost:5000/api/system/health`;
}

async function testPi() {
    const ip = document.getElementById('inputPiIP').value;
    if (!ip) { showToast('กรุณาระบุ IP ของ Pi', 'warning'); return; }
    const t = document.getElementById('testPiText'); t.textContent = '...';
    try {
        const res = await fetchAPI(`api/network.php?action=test&ip=${ip}&port=5000&type=pi`);
        updatePiStatus(res.online);
        showToast(res.message, res.online ? 'success' : 'error');
    } catch { updatePiStatus(false); showToast('เชื่อมต่อ Pi ไม่ได้', 'error'); }
    t.textContent = 'ทดสอบ';
}
async function testEsp() {
    const ip = document.getElementById('inputEspIP').value;
    if (!ip) { showToast('กรุณาระบุ IP ของ ESP32', 'warning'); return; }
    const t = document.getElementById('testEspText'); t.textContent = '...';
    try {
        const res = await fetchAPI(`api/network.php?action=test&ip=${ip}&port=80&type=esp32`);
        updateEspStatus(res.online);
        showToast(res.message, res.online ? 'success' : 'error');
    } catch { updateEspStatus(false); showToast('เชื่อมต่อ ESP32 ไม่ได้', 'error'); }
    t.textContent = 'ทดสอบ';
}
function updatePiStatus(online) {
    const el = document.getElementById('piStatus');
    el.className = 'net-status ' + (online ? 'ok' : 'bad');
    el.innerHTML = '<span class="led"></span><span>' + (online ? 'ออนไลน์' : 'ออฟไลน์') + '</span>';
}
function updateEspStatus(online) {
    const el = document.getElementById('espStatus');
    el.className = 'net-status ' + (online ? 'ok' : 'bad');
    el.innerHTML = '<span class="led"></span><span>' + (online ? 'ออนไลน์' : 'ออฟไลน์') + '</span>';
}

async function saveAllIPs() {
    const piIP = document.getElementById('inputPiIP').value;
    const espIP = document.getElementById('inputEspIP').value;
    const webIP = document.getElementById('inputWebIP').value;
    if (!piIP) { showToast('กรุณาระบุ IP ของ Raspberry Pi', 'warning'); return; }
    const status = document.getElementById('saveStatus');
    status.innerHTML = '<span class="text-accent">กำลังบันทึก...</span>';
    let success = true;
    let messages = [];
    try {
        const res = await postAPI('api/network.php?action=save_web_env', { pi_ip: piIP });
        if (res.success) messages.push('web/.env');
        else { success = false; messages.push('web/.env FAILED: ' + res.error); }
    } catch { success = false; messages.push('web/.env FAILED'); }
    try {
        const res = await postAPI('api/network.php?action=save_esp32', {
            server_url: `http://${piIP}:5000`,
            esp32_ip: espIP,
            wifi_ssid: document.getElementById('inputWifiSSID')?.value || ''
        });
        if (res.success) messages.push('ESP32 settings');
    } catch {}
    try {
        const piUrl = `http://${piIP}:5000`;
        const res = await postAPI(piUrl + '/api/config/env', {
            DB_HOST: webIP || window.location.hostname,
            WEB_SERVER_URL: `http://${webIP || window.location.hostname}/bunny-door`,
            ESP32_IP: espIP
        });
        if (res?.success) messages.push('Pi .env (' + (res.updated || []).join(', ') + ')');
    } catch { messages.push('Pi .env — อาจออฟไลน์'); }

    if (success) {
        status.innerHTML = '<span class="text-ok">บันทึกสำเร็จ!</span>';
        showToast('บันทึกสำเร็จ! Pi .env อัพเดทอัตโนมัติ', 'success', 5000);
    } else {
        status.innerHTML = '<span class="text-danger">บันทึกไม่สำเร็จ</span>';
        showToast('เกิดข้อผิดพลาด: ' + messages.join(', '), 'error');
    }
}

function copyPiCommand() {
    const text = document.getElementById('piCommand').textContent;
    navigator.clipboard.writeText(text).then(() => showToast('คัดลอกคำสั่งแล้ว', 'success'))
    .catch(() => {
        const ta = document.createElement('textarea'); ta.value = text;
        document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
        showToast('คัดลอกคำสั่งแล้ว', 'success');
    });
}

function togglePass() {
    const inp = document.getElementById('inputWifiPass');
    inp.type = inp.type === 'password' ? 'text' : 'password';
}

function generateESP32Code() {
    const piIP = document.getElementById('inputPiIP').value || '192.168.1.121';
    const wifiSSID = document.getElementById('inputWifiSSID').value || 'YOUR_WIFI_SSID';
    const wifiPass = document.getElementById('inputWifiPass').value || 'YOUR_WIFI_PASSWORD';
    const code = `/*
 * Bunny Door System - ESP32 Door Controller
 * Auto-generated: ${new Date().toLocaleString('th-TH')}
 * Pin: Relay=GPIO4, LED=GPIO2, PIR_OUT=GPIO27, PIR_IN=GPIO26, Buzzer=GPIO33, BTN=GPIO13
 */
#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ArduinoJson.h>
const char* WIFI_SSID     = "${wifiSSID}";
const char* WIFI_PASSWORD = "${wifiPass}";
const char* SERVER_URL    = "http://${piIP}:5000";

#define PIN_PIR_OUTSIDE 27
#define PIN_PIR_INSIDE  26
#define PIN_RELAY        4
#define PIN_BUZZER      33
#define PIN_LED_STATUS   2
#define PIN_EMERGENCY_BTN 13
#define DOOR_UNLOCK_MS         7000
#define PIR_COOLDOWN_MS        3000
#define HEARTBEAT_INTERVAL_MS  10000
#define DEBOUNCE_MS            200
#define RELAY_UNLOCK HIGH
#define RELAY_LOCK   LOW

WebServer server(80);
bool doorLocked = true;
unsigned long doorUnlockTime = 0, lastPirOutside = 0, lastPirInside = 0, lastHeartbeat = 0, lastBtnPress = 0;
bool pirOutsideState = false, pirInsideState = false;

void setup() {
    Serial.begin(115200);
    pinMode(PIN_PIR_OUTSIDE, INPUT); pinMode(PIN_PIR_INSIDE, INPUT);
    pinMode(PIN_RELAY, OUTPUT); pinMode(PIN_BUZZER, OUTPUT);
    pinMode(PIN_LED_STATUS, OUTPUT); pinMode(PIN_EMERGENCY_BTN, INPUT_PULLUP);
    lockDoor(); connectWiFi(); setupWebServer();
    beep(2, 100);
}
void loop() {
    server.handleClient();
    unsigned long now = millis();
    if (digitalRead(PIN_PIR_OUTSIDE) == HIGH && (now - lastPirOutside > PIR_COOLDOWN_MS)) { lastPirOutside = now; pirOutsideState = true; notifyServer("outside"); }
    if (digitalRead(PIN_PIR_INSIDE)  == HIGH && (now - lastPirInside  > PIR_COOLDOWN_MS)) { lastPirInside  = now; pirInsideState  = true; notifyServer("inside"); }
    if (digitalRead(PIN_PIR_OUTSIDE) == LOW) pirOutsideState = false;
    if (digitalRead(PIN_PIR_INSIDE)  == LOW) pirInsideState  = false;
    if (digitalRead(PIN_EMERGENCY_BTN) == LOW && (now - lastBtnPress > DEBOUNCE_MS)) { lastBtnPress = now; unlockDoor(); notifyEmergency(); }
    if (!doorLocked && (now - doorUnlockTime > DOOR_UNLOCK_MS)) lockDoor();
    if (now - lastHeartbeat > HEARTBEAT_INTERVAL_MS) { lastHeartbeat = now; sendHeartbeat(); }
    delay(50);
}
void unlockDoor(){ doorLocked=false; doorUnlockTime=millis(); digitalWrite(PIN_RELAY,RELAY_UNLOCK); digitalWrite(PIN_LED_STATUS,HIGH); beep(1,200); }
void lockDoor()  { doorLocked=true; digitalWrite(PIN_RELAY,RELAY_LOCK); digitalWrite(PIN_LED_STATUS,LOW); }
void connectWiFi(){
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
    int a=0; while(WiFi.status()!=WL_CONNECTED && a<30){ delay(500); a++; }
}
void setupWebServer(){
    server.on("/api/door/unlock", HTTP_POST, [](){ unlockDoor(); server.send(200,"application/json","{\\"status\\":\\"unlocked\\"}"); });
    server.on("/api/door/lock", HTTP_POST, [](){ lockDoor(); server.send(200,"application/json","{\\"status\\":\\"locked\\"}"); });
    server.on("/api/status", HTTP_GET, [](){
        StaticJsonDocument<256> doc;
        doc["door"]=doorLocked?"locked":"unlocked"; doc["pir_outside"]=pirOutsideState; doc["pir_inside"]=pirInsideState;
        doc["uptime_sec"]=millis()/1000; doc["ip"]=WiFi.localIP().toString(); doc["rssi"]=WiFi.RSSI();
        String o; serializeJson(doc,o); server.send(200,"application/json",o);
    });
    server.on("/ping", HTTP_GET, [](){ server.send(200,"text/plain","pong"); });
    server.begin();
}
void notifyServer(const char* side){ if(WiFi.status()!=WL_CONNECTED)return; HTTPClient h; h.begin(String(SERVER_URL)+"/api/motion"); h.addHeader("Content-Type","application/json");
    StaticJsonDocument<128> d; d["sensor"]=side; d["timestamp"]=millis(); String b; serializeJson(d,b); h.POST(b); h.end(); }
void notifyEmergency(){ if(WiFi.status()!=WL_CONNECTED)return; HTTPClient h; h.begin(String(SERVER_URL)+"/api/emergency"); h.addHeader("Content-Type","application/json"); h.POST("{\\"type\\":\\"emergency_button\\"}"); h.end(); }
void sendHeartbeat(){ if(WiFi.status()!=WL_CONNECTED)return; HTTPClient h; h.begin(String(SERVER_URL)+"/api/heartbeat"); h.addHeader("Content-Type","application/json");
    StaticJsonDocument<200> d; d["device"]="esp32"; d["door"]=doorLocked?"locked":"unlocked"; d["pir_outside"]=pirOutsideState; d["pir_inside"]=pirInsideState;
    d["rssi"]=WiFi.RSSI(); d["ip"]=WiFi.localIP().toString(); String b; serializeJson(d,b); h.POST(b); h.end(); }
void beep(int n,int d){ for(int i=0;i<n;i++){ digitalWrite(PIN_BUZZER,HIGH); delay(d); digitalWrite(PIN_BUZZER,LOW); if(i<n-1) delay(d); } }`;

    document.getElementById('esp32CodeBox').classList.remove('hidden');
    document.getElementById('esp32Code').textContent = code;
    navigator.clipboard.writeText(code).then(() => showToast('สร้างโค้ดสำเร็จ & คัดลอกแล้ว', 'success', 4000))
    .catch(() => {
        const ta = document.createElement('textarea'); ta.value = code;
        document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
        showToast('สร้างโค้ดสำเร็จ!', 'success');
    });
}

// ============================================================
// Auto-Pair management
// ============================================================
async function loadPairToken() {
    const data = await fetchAPI('api/pair/token.php');
    if (data?.token) document.getElementById('pairToken').value = data.token;
}

async function copyPairToken() {
    const tk = document.getElementById('pairToken').value;
    if (!tk) return;
    try { await navigator.clipboard.writeText(tk); showToast('คัดลอก token แล้ว', 'success'); }
    catch { showToast('คัดลอกไม่สำเร็จ', 'error'); }
}

async function regenerateToken() {
    showConfirm('สร้าง pairing token ใหม่?',
        'อุปกรณ์เดิม (Pi/ESP32) จะต้องอัพเดท token ก่อนถึงจะใช้งานต่อได้', async () => {
        const res = await postAPI('api/pair/token.php', { action: 'regenerate' });
        if (res?.success) {
            document.getElementById('pairToken').value = res.token;
            showToast('สร้าง token ใหม่แล้ว', 'success');
            refreshPairedDevices();
        } else {
            showToast(res?.error || 'สร้าง token ไม่สำเร็จ', 'error');
        }
    });
}

async function togglePairing() {
    const btn = document.getElementById('btnTogglePairing');
    const enabled = btn.dataset.enabled === '1';
    const newVal = enabled ? '0' : '1';
    const res = await postAPI('api/settings.php', { pairing_enabled: newVal });
    if (res?.success) {
        applyPairingState(newVal === '1');
        showToast(newVal === '1' ? 'เปิดรับ pair แล้ว' : 'ปิดรับ pair แล้ว', 'success');
    }
}

function applyPairingState(enabled) {
    const btn = document.getElementById('btnTogglePairing');
    const badge = document.getElementById('pairEnabledBadge');
    btn.dataset.enabled = enabled ? '1' : '0';
    btn.textContent = enabled ? 'ปิดรับ pair' : 'เปิดรับ pair';
    btn.classList.toggle('primary', !enabled);
    badge.textContent = enabled ? 'เปิด' : 'ปิด';
    badge.className = 'badge ' + (enabled ? 'accent' : '');
}

async function refreshPairedDevices() {
    const list = document.getElementById('pairedList');
    const data = await fetchAPI('api/pair/list.php');
    if (!data?.devices) {
        list.innerHTML = '<div class="empty" style="padding: 16px; text-align: center; color: var(--text-2);">โหลดไม่สำเร็จ</div>';
        return;
    }
    if (data.devices.length === 0) {
        list.innerHTML = '<div class="empty" style="padding: 24px; text-align: center; color: var(--text-2);">ยังไม่มีอุปกรณ์ — รอ Pi/ESP32 ส่ง broadcast (5–10 วินาที)</div>';
        return;
    }
    const roleIcon = {
        PI: <?= json_encode(ico('cpu', 18)) ?>,
        ESP32: <?= json_encode(ico('chip', 18)) ?>,
        OTHER: <?= json_encode(ico('plug', 18)) ?>
    };
    list.innerHTML = data.devices.map(d => {
        const onlineDot = d.online ? '<span class="text-ok">● online</span>' : `<span class="text-danger">● ${d.seconds_ago}s ago</span>`;
        const statusBadge = d.status === 'PENDING'
            ? '<span class="badge warn">รออนุมัติ</span>'
            : d.status === 'TRUSTED' ? '<span class="badge ok">เชื่อถือแล้ว</span>'
            : '<span class="badge danger">ถูกปฏิเสธ</span>';
        const actions = d.status === 'PENDING'
            ? `<button class="btn sm primary" onclick="pairAction(${d.id}, 'approve')">อนุมัติ</button>
               <button class="btn sm danger" onclick="pairAction(${d.id}, 'revoke')">ปฏิเสธ</button>`
            : d.status === 'TRUSTED'
                ? `<button class="btn sm danger" onclick="pairAction(${d.id}, 'revoke')">เพิกถอน</button>`
                : `<button class="btn sm" onclick="pairAction(${d.id}, 'approve')">เปิดใหม่</button>
                   <button class="btn sm ghost" onclick="pairAction(${d.id}, 'delete')">ลบ</button>`;
        return `<div class="pair-row ${d.status.toLowerCase()}">
            <div class="role-icon ${d.role.toLowerCase()}">${roleIcon[d.role] || roleIcon.OTHER}</div>
            <div class="pair-meta">
                <span class="name">${esc(d.role)} — ${esc(d.hostname || d.device_id)}</span>
                <span class="info">${esc(d.ip_address)}${d.port ? ':' + d.port : ''} · ${esc(d.device_id)} · ${onlineDot}</span>
            </div>
            <div>${statusBadge}</div>
            <div class="pair-actions">${actions}</div>
        </div>`;
    }).join('');
}

async function pairAction(id, action) {
    const labels = { approve: 'อนุมัติ', revoke: 'เพิกถอน', delete: 'ลบ' };
    if (action === 'delete' || action === 'revoke') {
        showConfirm(labels[action] + ' device?', 'ยืนยันการ' + labels[action], async () => {
            const res = await postAPI('api/pair/approve.php', { id, action });
            if (res?.success) { showToast(labels[action] + 'แล้ว', 'success'); refreshPairedDevices(); }
            else showToast(res?.error || 'ไม่สำเร็จ', 'error');
        });
    } else {
        const res = await postAPI('api/pair/approve.php', { id, action });
        if (res?.success) { showToast(labels[action] + 'แล้ว', 'success'); refreshPairedDevices(); }
        else showToast(res?.error || 'ไม่สำเร็จ', 'error');
    }
}

// Init pairing UI
document.addEventListener('DOMContentLoaded', () => {
    loadPairToken();
    refreshPairedDevices();
    // sync toggle button state จาก settings
    fetchAPI('api/settings.php').then(rows => {
        if (Array.isArray(rows)) {
            const row = rows.find(r => r.setting_key === 'pairing_enabled');
            applyPairingState(row?.setting_value === '1');
        }
    });
    setInterval(refreshPairedDevices, 8000);
});
</script>

<?php include 'includes/footer.php'; ?>
