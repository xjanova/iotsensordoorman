<?php $pageTitle = 'ตั้งค่าระบบ - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<?php
$db = getDB();
$settingsRaw = $db->query("SELECT * FROM settings ORDER BY id")->fetchAll();
$settings = [];
foreach ($settingsRaw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

$dbOnline = false;
try { getDB(); $dbOnline = true; } catch (Exception $e) {}

function s($key, $default = '') {
    global $settings;
    return htmlspecialchars($settings[$key] ?? $default);
}
?>

<div class="page-head">
    <div>
        <h1>ตั้งค่าระบบ</h1>
        <div class="sub">กำหนดค่าของระบบ Bunny Door — ประตู, ตรวจจับ, ESP32, กล้อง</div>
    </div>
    <div class="page-head-actions">
        <button type="button" onclick="recheckDevices()" class="btn sm ghost" id="btnRecheck"><?= ico('refresh', 12) ?> ตรวจสอบอีกครั้ง</button>
    </div>
</div>

<!-- Live Device Status -->
<div class="card section">
    <div class="card-head" style="padding: 0 0 14px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
        <div>
            <div class="title">สถานะอุปกรณ์ (ตรวจสอบจริง)</div>
            <div class="sub">live detect — ทั้ง 8 อุปกรณ์</div>
        </div>
    </div>
    <div class="grid cols-4" style="gap: 12px;">
        <?php
        $devices = [
            ['lbl' => 'เว็บเซิร์ฟเวอร์',     'icon' => 'server',  'id' => 'webStatic', 'sub' => 'webIPInfo',     'static' => 'online'],
            ['lbl' => 'ฐานข้อมูล MySQL',  'icon' => 'database','id' => 'dbStatic',  'sub' => 'dbHostInfo',    'static' => $dbOnline ? 'online' : 'offline', 'subText' => DB_HOST . ':' . DB_PORT],
            ['lbl' => 'Raspberry Pi',     'icon' => 'cpu',     'id' => 'piLiveStatus', 'sub' => 'piIPInfo',   'subText' => FACE_SERVER_URL],
            ['lbl' => 'ESP32',            'icon' => 'chip',    'id' => 'espLiveStatus','sub' => 'espIPInfo'],
            ['lbl' => 'กล้องด้านนอก',     'icon' => 'camera',  'id' => 'camOutLiveStatus'],
            ['lbl' => 'กล้องด้านใน',      'icon' => 'camera',  'id' => 'camInLiveStatus'],
            ['lbl' => 'Face Recognition', 'icon' => 'face-smile','id' => 'faceLiveStatus', 'sub' => 'faceInfo'],
            ['lbl' => 'เซ็นเซอร์ PIR',    'icon' => 'eye',     'id' => 'pirLiveStatus', 'sub' => 'pirInfo'],
        ];
        foreach ($devices as $d):
            $static = $d['static'] ?? '';
        ?>
        <div class="sys-list" style="grid-template-columns: 1fr;">
            <div class="item <?= $static ?>" <?= isset($d['static']) ? 'id="' . $d['id'] . '"' : '' ?>>
                <?= ico($d['icon'], 16) ?>
                <div class="lbl" style="display:flex; flex-direction:column; min-width:0;">
                    <span style="font-size: 12.5px; color: var(--text-2);"><?= htmlspecialchars($d['lbl']) ?></span>
                    <?php if (!empty($d['sub'])): ?>
                    <span class="tiny muted" id="<?= $d['sub'] ?>" style="font-family: var(--font-mono);"><?= htmlspecialchars($d['subText'] ?? '-') ?></span>
                    <?php endif; ?>
                </div>
                <span class="state" <?= !isset($d['static']) ? 'id="' . $d['id'] . '_state"' : '' ?>>
                    <?php if ($static === 'online'): ?>
                        ONLINE
                    <?php elseif ($static === 'offline'): ?>
                        OFFLINE
                    <?php else: ?>
                        …
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Tabs -->
<div class="settings-grid section">
    <div class="settings-tabs">
        <button type="button" onclick="switchSettingsTab('door')" class="tab active" id="stab-door">
            <?= ico('door', 14) ?> ประตู & ตรวจจับ
        </button>
        <button type="button" onclick="switchSettingsTab('esp32')" class="tab" id="stab-esp32">
            <?= ico('chip', 14) ?> ESP32 & WiFi
        </button>
        <button type="button" onclick="switchSettingsTab('camera')" class="tab" id="stab-camera">
            <?= ico('camera', 14) ?> กล้อง & ประมวลผล
        </button>
        <button type="button" onclick="switchSettingsTab('logs')" class="tab" id="stab-logs">
            <?= ico('terminal', 14) ?> Server Log
        </button>
        <button type="button" onclick="switchSettingsTab('terminal')" class="tab" id="stab-terminal">
            <?= ico('terminal', 14) ?> Terminal
        </button>
    </div>

    <form id="settingsForm" onsubmit="saveSettings(event)">

    <!-- Tab 1: Door & Detection -->
    <div id="spanel-door" class="settings-panel">
        <div class="card">
            <div class="card-head" style="padding: 0 0 14px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
                <div class="title">ตั้งค่าประตู & การตรวจจับ</div>
            </div>
            <div class="field-row">
                <div class="label-col"><strong>ระยะเวลาปลดล็อกประตู</strong><p>ประตูจะล็อกอัตโนมัติหลังจากเวลาที่กำหนด</p></div>
                <div class="ctl">
                    <div class="ctl-inline">
                        <input type="number" name="door_unlock_seconds" value="<?= s('door_unlock_seconds', '7') ?>" class="input" style="width: 120px;" min="1" max="60">
                        <span class="tiny muted">วินาที</span>
                    </div>
                </div>
            </div>
            <div class="field-row">
                <div class="label-col"><strong>ความมั่นใจขั้นต่ำ (Confidence)</strong><p>% ขั้นต่ำที่ระบบจะอนุญาตให้เข้า</p></div>
                <div class="ctl">
                    <div class="ctl-inline">
                        <input type="number" name="face_confidence_threshold" value="<?= s('face_confidence_threshold', '60') ?>" class="input" style="width: 120px;" min="1" max="100">
                        <span class="tiny muted">%</span>
                    </div>
                </div>
            </div>
            <div class="field-row">
                <div class="label-col"><strong>ตรวจจับ Tailgating</strong><p>แจ้งเตือนเมื่อมีคนเดินตามเข้าพร้อมกัน</p></div>
                <div class="ctl">
                    <select name="tailgate_detection" class="select" style="width: 200px;">
                        <option value="1" <?= ($settings['tailgate_detection'] ?? '1') === '1' ? 'selected' : '' ?>>เปิดใช้งาน</option>
                        <option value="0" <?= ($settings['tailgate_detection'] ?? '1') === '0' ? 'selected' : '' ?>>ปิดใช้งาน</option>
                    </select>
                </div>
            </div>
            <div class="field-row">
                <div class="label-col"><strong>แจ้งเตือนใบหน้าไม่รู้จัก</strong><p>สร้าง Alert เมื่อพบคนที่ไม่ได้ลงทะเบียน</p></div>
                <div class="ctl">
                    <select name="alert_unknown_face" class="select" style="width: 200px;">
                        <option value="1" <?= ($settings['alert_unknown_face'] ?? '1') === '1' ? 'selected' : '' ?>>เปิดใช้งาน</option>
                        <option value="0" <?= ($settings['alert_unknown_face'] ?? '1') === '0' ? 'selected' : '' ?>>ปิดใช้งาน</option>
                    </select>
                </div>
            </div>
            <div class="field-row">
                <div class="label-col"><strong>จำนวนคนสูงสุดต่อการเข้า 1 ครั้ง</strong><p>ถ้ามากกว่านี้จะแจ้งเตือน MULTI_PERSON</p></div>
                <div class="ctl">
                    <div class="ctl-inline">
                        <input type="number" name="max_persons_per_entry" value="<?= s('max_persons_per_entry', '1') ?>" class="input" style="width: 120px;" min="1" max="10">
                        <span class="tiny muted">คน</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: ESP32 & WiFi -->
    <div id="spanel-esp32" class="settings-panel hidden">
        <div class="card section">
            <div class="card-head" style="padding: 0 0 14px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
                <div class="title">WiFi & เครือข่าย</div>
            </div>
            <div class="field-row">
                <div class="label-col"><strong>ชื่อ WiFi (SSID)</strong><p>WiFi ที่ ESP32 จะเชื่อมต่อ</p></div>
                <div class="ctl"><input type="text" name="wifi_ssid" value="<?= s('wifi_ssid') ?>" class="input" placeholder="ชื่อ WiFi"></div>
            </div>
            <div class="field-row">
                <div class="label-col"><strong>รหัสผ่าน WiFi</strong><p>รหัสผ่านของ WiFi <span class="tiny muted">(เว้นว่าง = ไม่เปลี่ยน)</span></p></div>
                <div class="ctl">
                    <div style="position: relative;">
                        <?php $_wifiHasPwd = !empty($settings['wifi_password'] ?? ''); ?>
                        <input type="password" name="wifi_password" id="wifiPassInput" value="" class="input" placeholder="<?= $_wifiHasPwd ? '••••••••• (มีค่าอยู่แล้ว)' : 'รหัสผ่าน WiFi' ?>" style="padding-right: 38px;" autocomplete="new-password">
                        <button type="button" onclick="toggleWifiPass()" class="icon-btn" style="position:absolute; right:2px; top:50%; transform: translateY(-50%);"><?= ico('eye', 14) ?></button>
                    </div>
                </div>
            </div>
            <div class="field-row">
                <div class="label-col"><strong>IP ของ ESP32</strong><p>IP ที่ ESP32 ได้รับจาก Router</p></div>
                <div class="ctl"><input type="text" name="esp32_ip" value="<?= s('esp32_ip') ?>" class="input mono" placeholder="192.168.1.100"></div>
            </div>
            <div class="field-row">
                <div class="label-col"><strong>URL ของ Raspberry Pi Server</strong><p>ESP32 จะส่งข้อมูล motion/heartbeat ที่นี่</p></div>
                <div class="ctl"><input type="text" name="server_url" value="<?= s('server_url', 'http://192.168.1.50:5000') ?>" class="input mono" placeholder="http://192.168.1.50:5000"></div>
            </div>
        </div>

        <div class="card section">
            <div class="card-head" style="padding: 0 0 14px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
                <div class="title">ESP32 Hardware</div>
            </div>
            <div class="field-row">
                <div class="label-col">
                    <strong>นโยบายปลดล็อก (Unlock Policy)</strong>
                    <p>Online Only = ปลอดภัยกว่า แต่ถ้า DB ตายประตูจะไม่เปิด<br>Offline-Aware = ใช้ข้อมูลที่ cache ไว้ปลดได้แม้ DB ตาย</p>
                </div>
                <div class="ctl">
                    <select name="pi_unlock_mode" class="select" style="width: 280px;">
                        <option value="offline" <?= ($settings['pi_unlock_mode'] ?? 'offline') === 'offline' ? 'selected' : '' ?>>Offline-Aware (ใช้ cache ปลดได้)</option>
                        <option value="online_only" <?= ($settings['pi_unlock_mode'] ?? 'offline') === 'online_only' ? 'selected' : '' ?>>Online Only (ต้อง DB ออนไลน์)</option>
                    </select>
                </div>
            </div>
            <div class="field-row">
                <div class="label-col"><strong>ประเภท Relay</strong><p>NO = ปกติเปิด (ล็อกเมื่อ HIGH), NC = ปกติปิด</p></div>
                <div class="ctl">
                    <select name="door_lock_type" class="select" style="width: 280px;">
                        <option value="NO" <?= ($settings['door_lock_type'] ?? 'NO') === 'NO' ? 'selected' : '' ?>>Normally Open (NO)</option>
                        <option value="NC" <?= ($settings['door_lock_type'] ?? 'NO') === 'NC' ? 'selected' : '' ?>>Normally Closed (NC)</option>
                    </select>
                </div>
            </div>
            <div class="field-row">
                <div class="label-col"><strong>PIR Cooldown</strong><p>ระยะเวลาพักเซ็นเซอร์หลังตรวจจับ</p></div>
                <div class="ctl">
                    <div class="ctl-inline">
                        <input type="number" name="pir_cooldown_ms" value="<?= s('pir_cooldown_ms', '3000') ?>" class="input" style="width: 140px;" min="500" max="30000" step="500">
                        <span class="tiny muted">ms</span>
                    </div>
                </div>
            </div>
            <div class="field-row">
                <div class="label-col"><strong>Heartbeat Interval</strong><p>ESP32 ส่งสถานะไปยัง Server ทุกกี่ms</p></div>
                <div class="ctl">
                    <div class="ctl-inline">
                        <input type="number" name="heartbeat_interval_ms" value="<?= s('heartbeat_interval_ms', '10000') ?>" class="input" style="width: 140px;" min="1000" max="60000" step="1000">
                        <span class="tiny muted">ms</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head" style="padding: 0 0 14px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
                <div>
                    <div class="title">สร้างโค้ด Arduino</div>
                    <div class="sub">จากค่าตั้งด้านบน — กดบันทึกการตั้งค่าก่อน</div>
                </div>
                <div class="actions">
                    <button type="button" onclick="generateAndCopyCode()" class="btn primary sm"><?= ico('code', 12) ?> สร้าง & คัดลอก</button>
                </div>
            </div>
            <pre class="code-block term hidden" id="generatedCodeBox"><code id="generatedCode"></code></pre>
        </div>
    </div>

    <!-- Tab 3: Camera & Processing -->
    <div id="spanel-camera" class="settings-panel hidden">
        <div class="card section">
            <div class="card-head" style="padding: 0 0 14px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
                <div>
                    <div class="title">กล้อง USB ที่ตรวจพบ</div>
                    <div class="sub" id="cameraDetectResult">…</div>
                </div>
                <div class="actions">
                    <button type="button" onclick="detectCameras()" class="btn sm ghost" id="btnDetectCam"><?= ico('refresh', 12) ?> สแกนใหม่</button>
                </div>
            </div>

            <div id="cameraGrid" class="grid cols-3" style="gap: 14px;"></div>

            <div id="cameraAssignBtn" class="hidden" style="margin-top: 16px;">
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" onclick="assignCameras()" class="btn success"><?= ico('check', 14) ?> บันทึก & Restart Pi</button>
                    <button type="button" onclick="restartPiService(true)" class="btn ghost sm"><?= ico('refresh', 12) ?> Restart Pi เฉยๆ</button>
                </div>
                <p class="tiny muted" style="margin-top: 8px;">หลังบันทึก ระบบจะ restart face_server บน Pi อัตโนมัติ (~5 วินาที)</p>
            </div>
        </div>

        <div class="card">
            <div class="card-head" style="padding: 0 0 14px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
                <div class="title">ตั้งค่าการประมวลผล</div>
            </div>
            <div class="field-row">
                <div class="label-col"><strong>ประมวลผลทุกกี่เฟรม</strong><p>ค่ามาก = เร็วขึ้นแต่ตรวจจับช้าลง / ค่าน้อย = ละเอียดแต่หนักกว่า</p></div>
                <div class="ctl">
                    <div class="ctl-inline">
                        <input type="number" name="process_every_x_frames" value="<?= s('process_every_x_frames', '5') ?>" class="input" style="width: 120px;" min="1" max="30">
                        <span class="tiny muted">เฟรม</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 4: Server Log -->
    <div id="spanel-logs" class="settings-panel hidden">
        <div class="card">
            <div class="card-head" style="padding: 0 0 14px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
                <div>
                    <div class="title">Face Server Log</div>
                    <div class="sub" id="logFileInfo">—</div>
                </div>
                <div class="actions">
                    <button type="button" onclick="fetchServerLogs()" class="btn sm"><?= ico('refresh', 12) ?> รีเฟรช</button>
                    <button type="button" onclick="clearServerLogs()" class="btn sm danger"><?= ico('trash', 12) ?> ลบ Log</button>
                </div>
            </div>
            <div class="row gap-2" style="margin-bottom: 14px; flex-wrap: wrap;">
                <select id="logLevel" onchange="fetchServerLogs()" class="select" style="width: 140px;">
                    <option value="">ทุก Level</option>
                    <option value="INFO">INFO</option>
                    <option value="WARNING">WARNING</option>
                    <option value="ERROR">ERROR</option>
                </select>
                <input type="text" id="logSearch" placeholder="ค้นหา..." onkeyup="if(event.key==='Enter')fetchServerLogs()" class="input" style="width: 200px;">
                <select id="logLines" onchange="fetchServerLogs()" class="select" style="width: 140px;">
                    <option value="100">100 บรรทัด</option>
                    <option value="200" selected>200 บรรทัด</option>
                    <option value="500">500 บรรทัด</option>
                    <option value="1000">1000 บรรทัด</option>
                </select>
                <label class="row gap-2" style="font-size: 12px;">
                    <input type="checkbox" id="logAutoScroll" checked> Auto-scroll
                </label>
            </div>
            <pre class="code-block" id="logOutput" style="min-height: 240px; max-height: 520px;"><span class="muted">กดแท็บ "Server Log" เพื่อโหลด...</span></pre>
            <div class="row spread" style="margin-top: 12px;">
                <span class="tiny muted" id="logStats">—</span>
                <label class="row gap-2" style="font-size: 12px;">
                    <input type="checkbox" id="logAutoRefresh" onchange="toggleLogAutoRefresh()"> Auto-refresh 3s
                </label>
            </div>
        </div>
    </div>

    <!-- Tab 5: Terminal -->
    <div id="spanel-terminal" class="settings-panel hidden">
        <div class="card">
            <div class="card-head" style="padding: 0 0 14px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
                <div>
                    <div class="title">Raspberry Pi Terminal</div>
                    <div class="sub">cwd: ~/bunny-door</div>
                </div>
                <div class="actions">
                    <button type="button" onclick="clearTerminal()" class="btn sm danger"><?= ico('trash', 12) ?> ล้างจอ</button>
                </div>
            </div>
            <div class="row gap-2" style="margin-bottom: 14px; flex-wrap: wrap;">
                <?php
                $quickCmds = ['git status','git pull','ps aux | grep face_server','free -h','df -h','uptime','vcgencmd measure_temp','v4l2-ctl --list-devices','lsusb','ip addr show'];
                foreach ($quickCmds as $cmd): ?>
                <button type="button" onclick='runQuickCmd(<?= json_encode($cmd) ?>)' class="btn sm"><?= htmlspecialchars($cmd) ?></button>
                <?php endforeach; ?>
            </div>
            <pre class="code-block term" id="terminalOutput" style="min-height: 240px; max-height: 480px;"><span class="text-ok">root1@raspberrypi:~/bunny-door$</span> <span class="muted">พร้อมใช้งาน</span></pre>
            <div class="row gap-2" style="margin-top: 12px;">
                <span class="mono text-ok" style="display:flex; align-items:center;">$</span>
                <input type="text" id="terminalInput" placeholder="พิมพ์คำสั่ง..." class="input mono" onkeydown="if(event.key==='Enter'){runTerminalCmd(); event.preventDefault();}">
                <button type="button" onclick="runTerminalCmd()" class="btn success sm"><?= ico('play', 12) ?> รัน</button>
            </div>
            <p class="tiny muted" style="margin-top: 8px;">จำกัดเฉพาะคำสั่งที่ปลอดภัย — ห้าม rm, dd, shutdown</p>
        </div>
    </div>

    <!-- Save Button -->
    <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn primary lg"><?= ico('save', 14) ?> บันทึกการตั้งค่าทั้งหมด</button>
    </div>

    </form>
</div>

<!-- System Update -->
<div class="card section">
    <div class="card-head" style="padding: 0 0 14px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
        <div>
            <div class="title">อัพเดทระบบ</div>
            <div class="sub">เวอร์ชันปัจจุบัน <span class="badge accent mono" id="currentVersion">v<?= APP_VERSION ?></span> (build <?= APP_BUILD ?>)</div>
        </div>
    </div>
    <div id="updateStatus" style="padding: 12px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-md); margin-bottom: 14px;">
        <div class="row gap-2"><?= ico('info', 14) ?><span class="tiny muted">กดปุ่ม "ตรวจสอบ" เพื่อเช็คเวอร์ชันล่าสุดจาก GitHub</span></div>
    </div>
    <pre class="code-block term hidden" id="updateLog" style="margin-bottom: 14px;"><code id="updateLogText"></code></pre>
    <div class="row gap-3">
        <button type="button" onclick="checkUpdate()" id="btnCheck" class="btn primary"><?= ico('search', 14) ?> ตรวจสอบเวอร์ชันล่าสุด</button>
        <button type="button" onclick="pullUpdate()" id="btnUpdate" class="btn success hidden"><?= ico('download', 14) ?> อัพเดทเลย</button>
    </div>
</div>

<script>
// ============================================================
// Tab Switching
// ============================================================
function switchSettingsTabBase(tab) {
    document.querySelectorAll('.settings-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.settings-tabs .tab').forEach(b => b.classList.remove('active'));
    document.getElementById('spanel-' + tab).classList.remove('hidden');
    document.getElementById('stab-' + tab).classList.add('active');
}
let switchSettingsTab = switchSettingsTabBase;

// ============================================================
// Save Settings
// ============================================================
function toggleWifiPass() {
    const inp = document.getElementById('wifiPassInput');
    inp.type = inp.type === 'password' ? 'text' : 'password';
}
async function saveSettings(e) {
    e.preventDefault();
    const form = new FormData(e.target);
    const data = Object.fromEntries(form);
    // ถ้า wifi_password ว่าง → ไม่ส่งค่าเพื่อไม่ overwrite ค่าเดิมที่ถูก mask ใน UI
    if (data.wifi_password === '') delete data.wifi_password;
    const res = await postAPI('api/settings.php', data);
    if (res?.success) showToast('บันทึกการตั้งค่าสำเร็จ (' + (res.updated || 0) + ' รายการ)', 'success');
    else showToast(res?.error || 'เกิดข้อผิดพลาด', 'error');
}

// ============================================================
// Generate Arduino Code (จากค่า settings)
// ============================================================
// escape C-string literal — ใช้กับค่าที่ embed ใน const char*
function escC(v) {
    if (v == null) return '';
    return String(v)
        .replace(/\\/g, '\\\\')
        .replace(/"/g, '\\"')
        .replace(/\n/g, '\\n')
        .replace(/\r/g, '\\r')
        .replace(/\t/g, '\\t')
        .replace(/[\x00-\x1f\x7f]/g, '');
}
function generateAndCopyCode() {
    const form = document.getElementById('settingsForm');
    const fd = new FormData(form);
    const s = Object.fromEntries(fd);
    const code = `/*
 * Bunny Door System - ESP32 (auto-generated from settings)
 * ${new Date().toLocaleString('th-TH')}
 * Pin: Relay=4, LED=2, PIR_OUT=27, PIR_IN=26, Buzzer=33, BTN=13
 */
#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ArduinoJson.h>
const char* WIFI_SSID     = "${escC(s.wifi_ssid || 'YOUR_WIFI_SSID')}";
const char* WIFI_PASSWORD = "${escC(s.wifi_password || 'YOUR_WIFI_PASSWORD')}";
const char* SERVER_URL    = "${escC(s.server_url || 'http://192.168.1.50:5000')}";

#define PIN_PIR_OUTSIDE 27
#define PIN_PIR_INSIDE  26
#define PIN_RELAY        4
#define PIN_BUZZER      33
#define PIN_LED_STATUS   2
#define PIN_EMERGENCY_BTN 13
#define DOOR_UNLOCK_MS         ${parseInt(s.door_unlock_seconds || 7) * 1000}
#define PIR_COOLDOWN_MS        ${s.pir_cooldown_ms || 3000}
#define HEARTBEAT_INTERVAL_MS  ${s.heartbeat_interval_ms || 10000}
#define DEBOUNCE_MS            200
#define RELAY_UNLOCK  ${s.door_lock_type === 'NC' ? 'LOW' : 'HIGH'}
#define RELAY_LOCK    ${s.door_lock_type === 'NC' ? 'HIGH' : 'LOW'}

WebServer server(80);
bool doorLocked = true;
unsigned long doorUnlockTime=0, lastPirOutside=0, lastPirInside=0, lastHeartbeat=0, lastBtnPress=0;
bool pirOutsideState=false, pirInsideState=false;

void setup(){
    Serial.begin(115200);
    pinMode(PIN_PIR_OUTSIDE, INPUT); pinMode(PIN_PIR_INSIDE, INPUT);
    pinMode(PIN_RELAY, OUTPUT); pinMode(PIN_BUZZER, OUTPUT);
    pinMode(PIN_LED_STATUS, OUTPUT); pinMode(PIN_EMERGENCY_BTN, INPUT_PULLUP);
    lockDoor(); connectWiFi(); setupWebServer();
}
void loop(){
    server.handleClient();
    unsigned long now=millis();
    if(digitalRead(PIN_PIR_OUTSIDE)==HIGH && (now-lastPirOutside>PIR_COOLDOWN_MS)){ lastPirOutside=now; pirOutsideState=true; notifyServer("outside"); }
    if(digitalRead(PIN_PIR_INSIDE) ==HIGH && (now-lastPirInside >PIR_COOLDOWN_MS)){ lastPirInside =now; pirInsideState =true; notifyServer("inside"); }
    if(digitalRead(PIN_PIR_OUTSIDE)==LOW) pirOutsideState=false;
    if(digitalRead(PIN_PIR_INSIDE) ==LOW) pirInsideState =false;
    if(digitalRead(PIN_EMERGENCY_BTN)==LOW && (now-lastBtnPress>DEBOUNCE_MS)){ lastBtnPress=now; unlockDoor(); notifyEmergency(); }
    if(!doorLocked && (now-doorUnlockTime>DOOR_UNLOCK_MS)) lockDoor();
    if(now-lastHeartbeat>HEARTBEAT_INTERVAL_MS){ lastHeartbeat=now; sendHeartbeat(); }
    delay(50);
}
void unlockDoor(){ doorLocked=false; doorUnlockTime=millis(); digitalWrite(PIN_RELAY,RELAY_UNLOCK); digitalWrite(PIN_LED_STATUS,HIGH); }
void lockDoor()  { doorLocked=true; digitalWrite(PIN_RELAY,RELAY_LOCK); digitalWrite(PIN_LED_STATUS,LOW); }
void connectWiFi(){ WiFi.begin(WIFI_SSID,WIFI_PASSWORD); int a=0; while(WiFi.status()!=WL_CONNECTED && a<30){ delay(500); a++; } }
void setupWebServer(){
    server.on("/api/door/unlock", HTTP_POST, [](){ unlockDoor(); server.send(200,"application/json","{\\"status\\":\\"unlocked\\"}"); });
    server.on("/api/door/lock",   HTTP_POST, [](){ lockDoor();   server.send(200,"application/json","{\\"status\\":\\"locked\\"}"); });
    server.on("/api/status",      HTTP_GET,  [](){ StaticJsonDocument<256> d; d["door"]=doorLocked?"locked":"unlocked"; d["pir_outside"]=pirOutsideState; d["pir_inside"]=pirInsideState; d["uptime_sec"]=millis()/1000; d["ip"]=WiFi.localIP().toString(); d["rssi"]=WiFi.RSSI(); String o; serializeJson(d,o); server.send(200,"application/json",o); });
    server.on("/ping", HTTP_GET, [](){ server.send(200,"text/plain","pong"); });
    server.begin();
}
void notifyServer(const char* side){ if(WiFi.status()!=WL_CONNECTED)return; HTTPClient h; h.begin(String(SERVER_URL)+"/api/motion"); h.addHeader("Content-Type","application/json"); StaticJsonDocument<128> d; d["sensor"]=side; d["timestamp"]=millis(); String b; serializeJson(d,b); h.POST(b); h.end(); }
void notifyEmergency(){ if(WiFi.status()!=WL_CONNECTED)return; HTTPClient h; h.begin(String(SERVER_URL)+"/api/emergency"); h.addHeader("Content-Type","application/json"); h.POST("{\\"type\\":\\"emergency_button\\"}"); h.end(); }
void sendHeartbeat(){ if(WiFi.status()!=WL_CONNECTED)return; HTTPClient h; h.begin(String(SERVER_URL)+"/api/heartbeat"); h.addHeader("Content-Type","application/json"); StaticJsonDocument<200> d; d["device"]="esp32"; d["door"]=doorLocked?"locked":"unlocked"; d["pir_outside"]=pirOutsideState; d["pir_inside"]=pirInsideState; d["rssi"]=WiFi.RSSI(); d["ip"]=WiFi.localIP().toString(); String b; serializeJson(d,b); h.POST(b); h.end(); }`;
    const box = document.getElementById('generatedCodeBox');
    box.classList.remove('hidden');
    document.getElementById('generatedCode').textContent = code;
    navigator.clipboard.writeText(code).then(() => showToast('สร้างโค้ดสำเร็จ & คัดลอกแล้ว', 'success'))
    .catch(() => {
        const ta = document.createElement('textarea'); ta.value = code; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
        showToast('สร้างโค้ดสำเร็จ!', 'success');
    });
}

// ============================================================
// System Update
// ============================================================
async function checkUpdate() {
    const btn = document.getElementById('btnCheck');
    const status = document.getElementById('updateStatus');
    btn.disabled = true; btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite;"><path d="M3 12a9 9 0 0 1 15.5-6.4L21 8"/><path d="M21 4v4h-4"/></svg> กำลังตรวจสอบ...';
    try {
        const res = await fetch('api/update.php?action=check');
        const data = await res.json();
        if (data.success) {
            const local = data.local, remote = data.remote;
            if (data.has_update && remote) {
                status.innerHTML = `<div class="row gap-3">
                    <span class="badge ok"><span class="dot"></span>มีเวอร์ชันใหม่</span>
                    <span class="mono tiny muted">ปัจจุบัน: v${local?.version || '?'}</span>
                    <span class="text-accent">→</span>
                    <span class="mono text-ok">v${remote.version} (build ${remote.build})</span>
                </div>${remote.changelog ? '<p class="tiny muted" style="margin-top: 8px;">' + esc(remote.changelog) + '</p>' : ''}`;
                document.getElementById('btnUpdate').classList.remove('hidden');
            } else {
                status.innerHTML = '<div class="row gap-2"><span class="badge accent"><span class="dot"></span>เวอร์ชันล่าสุดแล้ว</span><span class="tiny muted mono">v' + (local?.version || '?') + ' build ' + (local?.build || '?') + '</span></div>';
                document.getElementById('btnUpdate').classList.add('hidden');
            }
        } else {
            status.innerHTML = '<div class="text-warn">ไม่สามารถตรวจสอบได้</div>';
        }
    } catch (e) {
        status.innerHTML = '<div class="text-danger">เกิดข้อผิดพลาด: ' + esc(e.message) + '</div>';
    }
    btn.disabled = false;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg> ตรวจสอบเวอร์ชันล่าสุด';
}

async function pullUpdate() {
    const btn = document.getElementById('btnUpdate');
    const log = document.getElementById('updateLog');
    const logText = document.getElementById('updateLogText');
    btn.disabled = true; btn.textContent = 'กำลังอัพเดท...';
    log.classList.remove('hidden');
    logText.textContent = 'Running git pull origin main...\n';
    try {
        const data = await postAPI('api/update.php?action=pull', {});
        if (!data) throw new Error('network');
        logText.textContent += (data.output || '') + '\n';
        if (data.success) {
            const nv = data.new_version;
            logText.textContent += `\nUpdate successful! v${nv?.version || '?'}\n`;
            showToast('อัพเดทสำเร็จ! v' + (nv?.version || '?'), 'success', 3000);
            document.getElementById('currentVersion').textContent = 'v' + (nv?.version || '?');
            setTimeout(() => location.reload(), 2000);
        } else {
            logText.textContent += '\nError: ' + (data.error || 'Unknown') + '\n';
            showToast('อัพเดทล้มเหลว', 'error');
        }
    } catch (e) {
        logText.textContent += '\nException: ' + e.message + '\n';
        showToast('เกิดข้อผิดพลาด', 'error');
    }
    btn.disabled = false; btn.textContent = 'อัพเดทเลย';
}

// ============================================================
// Live Device Detection
// ============================================================
async function checkDevicesLive() {
    try {
        const net = await fetchAPI('api/network.php?action=detect');
        if (net.success) {
            document.getElementById('webIPInfo').textContent = net.web_server.primary_ip;
            setDeviceStatus('piLiveStatus', net.raspberry_pi.online);
            if (net.raspberry_pi.online) document.getElementById('piIPInfo').textContent = net.raspberry_pi.url + ' (เชื่อมต่อได้)';
            if (net.raspberry_pi.online) { checkCameras(); checkFaceRecognition(); checkESP32(); }
            else {
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
    } catch (e) { console.error(e); }
}
async function checkESP32() {
    try {
        const data = await fetchAPI(FACE_SERVER + '/api/esp32/health');
        if (data && data.online) {
            setDeviceStatus('espLiveStatus', true);
            document.getElementById('espIPInfo').textContent = (data.ip || '-') + ' · RSSI ' + (data.rssi || '-') + 'dBm';
            setDeviceStatus('pirLiveStatus', true);
            document.getElementById('pirInfo').textContent = 'นอก: ' + (data.pir_outside ? 'ตรวจจับ' : 'ปกติ') + ' · ใน: ' + (data.pir_inside ? 'ตรวจจับ' : 'ปกติ');
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
    el.classList.remove('online', 'offline', 'warn', 'unknown');
    el.classList.add(online ? 'online' : 'offline');
    const state = el.querySelector('.state');
    if (state) state.textContent = online ? 'ONLINE' : 'OFFLINE';
}
function checkCameras() {
    const imgOut = new Image();
    imgOut.onload = () => setDeviceStatus('camOutLiveStatus', true);
    imgOut.onerror = () => setDeviceStatus('camOutLiveStatus', false);
    setTimeout(() => { if (!imgOut.complete) setDeviceStatus('camOutLiveStatus', false); }, 5000);
    imgOut.src = FACE_SERVER + '/api/snapshot/outside?t=' + Date.now();

    const imgIn = new Image();
    imgIn.onload  = () => setDeviceStatus('camInLiveStatus', true);
    imgIn.onerror = () => { const el = document.getElementById('camInLiveStatus'); el.classList.remove('online','offline'); el.classList.add('unknown'); const s = el.querySelector('.state'); if (s) s.textContent = 'ปิดใช้งาน'; };
    setTimeout(() => { if (!imgIn.complete) { const el = document.getElementById('camInLiveStatus'); el.classList.remove('online','offline'); el.classList.add('unknown'); const s = el.querySelector('.state'); if (s) s.textContent = 'ปิดใช้งาน'; }}, 5000);
    imgIn.src = FACE_SERVER + '/api/snapshot/inside?t=' + Date.now();
}
async function checkFaceRecognition() {
    try {
        const res = await fetchAPI('api/pi_health.php');
        if (res && res.cpu_percent !== undefined) {
            setDeviceStatus('faceLiveStatus', true);
            document.getElementById('faceInfo').textContent = 'CPU ' + res.cpu_percent + '% · RAM ' + res.ram_percent + '%';
        } else setDeviceStatus('faceLiveStatus', false);
    } catch { setDeviceStatus('faceLiveStatus', false); }
}
function recheckDevices() {
    const btn = document.getElementById('btnRecheck');
    btn.disabled = true;
    checkDevicesLive().then(() => { btn.disabled = false; showToast('ตรวจสอบอุปกรณ์เสร็จแล้ว', 'info'); });
}
document.addEventListener('DOMContentLoaded', checkDevicesLive);

// ============================================================
// Camera Detection & Assignment
// ============================================================
let _detectedCameras = [];
let _prevCameraCount = -1;
async function detectCameras() {
    const grid = document.getElementById('cameraGrid');
    const result = document.getElementById('cameraDetectResult');
    const assignBtn = document.getElementById('cameraAssignBtn');
    result.innerHTML = '<span class="text-accent">กำลังสแกนกล้อง USB...</span>';
    grid.innerHTML = '';
    assignBtn.classList.add('hidden');
    try {
        const resp = await fetch(FACE_SERVER + '/api/cameras/detect', { signal: AbortSignal.timeout(15000) });
        if (!resp.ok) throw new Error('Pi ไม่ตอบสนอง');
        const data = await resp.json();
        _detectedCameras = data.cameras || [];
        if (_prevCameraCount >= 0 && _prevCameraCount !== _detectedCameras.length) showToast(`พบกล้อง ${_detectedCameras.length} ตัว (เปลี่ยนจาก ${_prevCameraCount})`, 'info');
        _prevCameraCount = _detectedCameras.length;
        if (_detectedCameras.length === 0) { result.innerHTML = '<span class="text-warn">ไม่พบกล้อง USB</span>'; return; }
        result.innerHTML = `<span class="text-ok">พบ ${_detectedCameras.length} กล้อง</span>`;
        _detectedCameras.forEach(cam => {
            const card = document.createElement('div');
            card.className = 'card';
            card.style.padding = '14px';
            card.innerHTML = `
                <img src="data:image/jpeg;base64,${cam.thumbnail}" style="width:100%; aspect-ratio: 16/9; object-fit:cover; border-radius:6px; margin-bottom: 10px;" alt="Camera ${cam.id}">
                <div class="row spread" style="margin-bottom: 8px;">
                    <div>
                        <p style="font-size: 13px; font-weight: 600; margin: 0;">${esc(cam.device)}</p>
                        <p class="tiny muted" style="margin: 2px 0 0;">${esc(cam.name)}</p>
                    </div>
                    <span class="badge ${cam.can_read ? 'ok' : 'danger'}">${cam.can_read ? 'OK' : 'X'}</span>
                </div>
                <select id="camAssign_${cam.id}" class="select">
                    <option value="">ไม่ใช้งาน</option>
                    <option value="outside" ${cam.assigned === 'outside' ? 'selected' : ''}>กล้องด้านนอก</option>
                    <option value="inside"  ${cam.assigned === 'inside'  ? 'selected' : ''}>กล้องด้านใน</option>
                </select>`;
            grid.appendChild(card);
        });
        assignBtn.classList.remove('hidden');
    } catch (e) {
        result.innerHTML = `<span class="text-danger">สแกนไม่ได้: ${esc(e.message)}</span>`;
    }
}
async function assignCameras() {
    let outsideId = -1, insideId = -1;
    _detectedCameras.forEach(cam => {
        const sel = document.getElementById(`camAssign_${cam.id}`);
        if (sel) { if (sel.value === 'outside') outsideId = cam.id; if (sel.value === 'inside') insideId = cam.id; }
    });
    try {
        const resp = await fetch(FACE_SERVER + '/api/cameras/assign', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ outside: outsideId, inside: insideId }) });
        const data = await resp.json();
        if (data.success) {
            showToast('บันทึกการกำหนดกล้องสำเร็จ — กำลัง restart Pi...', 'success');
            // restart Pi อัตโนมัติเพื่อให้กล้องใหม่มีผล
            setTimeout(() => restartPiService(false), 500);
        } else {
            showToast(data.error || 'เกิดข้อผิดพลาด', 'error');
        }
    } catch (e) { showToast('บันทึกไม่ได้: ' + e.message, 'error'); }
}

async function restartPiService(confirmFirst) {
    if (confirmFirst && !confirm('Restart face_server บน Pi?\nกล้อง + face recognition จะหยุดประมาณ 5 วินาที')) return;
    try {
        const r = await postAPI('api/system/restart.php', {});
        if (r?.success) {
            showToast('สั่ง restart แล้ว — Pi จะกลับมาออนไลน์ใน ~5-10 วินาที', 'success');
        } else {
            showToast(r?.error || 'Restart Pi ไม่ได้', 'error');
        }
    } catch (e) {
        showToast('Restart Pi ไม่ได้: ' + e.message, 'error');
    }
}

// ============================================================
// Pi Web Terminal
// ============================================================
let _cmdHistory = []; let _cmdIndex = -1;
async function runTerminalCmd() {
    const input = document.getElementById('terminalInput');
    const cmd = input.value.trim();
    if (!cmd) return;
    input.value = ''; _cmdHistory.unshift(cmd); _cmdIndex = -1;
    runQuickCmd(cmd);
}
async function runQuickCmd(cmd) {
    const output = document.getElementById('terminalOutput');
    output.innerHTML += `\n<span class="text-ok">root1@raspberrypi:~/bunny-door$</span> <span>${esc(cmd)}</span>\n<span class="muted">กำลังรัน...</span>`;
    output.scrollTop = output.scrollHeight;
    try {
        const resp = await fetch(FACE_SERVER + '/api/terminal', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ command: cmd }), signal: AbortSignal.timeout(35000) });
        const data = await resp.json();
        const lines = output.innerHTML.split('\n');
        lines.pop();
        output.innerHTML = lines.join('\n');
        if (data.error) output.innerHTML += `\n<span class="text-danger">${esc(data.error)}</span>`;
        else {
            if (data.stdout) output.innerHTML += `\n<span>${esc(data.stdout)}</span>`;
            if (data.stderr) output.innerHTML += `<span class="text-warn">${esc(data.stderr)}</span>`;
            if (data.returncode !== 0) output.innerHTML += `<span class="text-danger">[exit ${data.returncode}]</span>`;
        }
    } catch (e) {
        output.innerHTML += `\n<span class="text-danger">Error: ${esc(e.message)}</span>`;
    }
    output.scrollTop = output.scrollHeight;
}
function clearTerminal() {
    document.getElementById('terminalOutput').innerHTML = '<span class="text-ok">root1@raspberrypi:~/bunny-door$</span> <span class="muted">Terminal ถูกล้าง</span>';
}
document.addEventListener('keydown', (e) => {
    const input = document.getElementById('terminalInput');
    if (document.activeElement !== input || _cmdHistory.length === 0) return;
    if (e.key === 'ArrowUp')   { e.preventDefault(); _cmdIndex = Math.min(_cmdIndex + 1, _cmdHistory.length - 1); input.value = _cmdHistory[_cmdIndex] || ''; }
    else if (e.key === 'ArrowDown'){ e.preventDefault(); _cmdIndex = Math.max(_cmdIndex - 1, -1); input.value = _cmdIndex >= 0 ? _cmdHistory[_cmdIndex] : ''; }
});

// ============================================================
// Server Log Viewer
// ============================================================
let _logInterval = null;
async function fetchServerLogs() {
    const level  = document.getElementById('logLevel')?.value || '';
    const search = document.getElementById('logSearch')?.value || '';
    const lines  = document.getElementById('logLines')?.value || 200;
    const output = document.getElementById('logOutput');
    try {
        const params = new URLSearchParams({ lines, level, search });
        const resp = await fetch(FACE_SERVER + '/api/logs/server?' + params, { signal: AbortSignal.timeout(5000) });
        if (!resp.ok) throw new Error('ไม่สามารถดึง log ได้');
        const data = await resp.json();
        const colored = data.logs.map(line => {
            if (line.includes('[ERROR]'))   return `<span class="text-danger">${esc(line)}</span>`;
            if (line.includes('[WARNING]')) return `<span class="text-warn">${esc(line)}</span>`;
            if (line.includes('[Camera]'))  return `<span class="text-info">${esc(line)}</span>`;
            if (line.includes('[PIR]') || line.includes('[DOOR]')) return `<span class="text-ok">${esc(line)}</span>`;
            if (line.includes('[ESP32]'))   return `<span class="text-accent">${esc(line)}</span>`;
            return `<span>${esc(line)}</span>`;
        });
        output.innerHTML = colored.join('\n') || '<span class="muted">ไม่มี log</span>';
        if (document.getElementById('logAutoScroll')?.checked) output.scrollTop = output.scrollHeight;
        document.getElementById('logFileInfo').textContent = data.file_size_mb + ' MB';
        document.getElementById('logStats').textContent = `แสดง ${data.logs.length} / ${data.filtered_lines} บรรทัด (ทั้งหมด ${data.total_lines})`;
    } catch (e) {
        output.innerHTML = `<span class="text-danger">ไม่สามารถดึง log: ${esc(e.message)}</span>`;
    }
}
async function clearServerLogs() {
    showConfirm('ลบ Log ทั้งหมด', 'ต้องการลบ log ทั้งหมด?', async () => {
        try {
            const resp = await fetch(FACE_SERVER + '/api/logs/server/clear', { method: 'POST' });
            const data = await resp.json();
            if (data.success) { showToast('ลบ log เรียบร้อย', 'success'); fetchServerLogs(); }
        } catch (e) { showToast('ลบไม่ได้: ' + e.message, 'error'); }
    });
}
function toggleLogAutoRefresh() {
    if (document.getElementById('logAutoRefresh')?.checked) _logInterval = setInterval(fetchServerLogs, 3000);
    else { clearInterval(_logInterval); _logInterval = null; }
}

// Wrap switchSettingsTab to add side-effects (camera poll / log auto-load)
let _cameraPollInterval = null;
switchSettingsTab = function(tab) {
    switchSettingsTabBase(tab);
    if (tab === 'camera') { detectCameras(); if (!_cameraPollInterval) _cameraPollInterval = setInterval(detectCameras, 8000); }
    else { if (_cameraPollInterval) { clearInterval(_cameraPollInterval); _cameraPollInterval = null; } }
    if (tab === 'logs') fetchServerLogs();
    else if (_logInterval) { clearInterval(_logInterval); _logInterval = null; const cb = document.getElementById('logAutoRefresh'); if (cb) cb.checked = false; }
};
</script>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

<?php include 'includes/footer.php'; ?>
