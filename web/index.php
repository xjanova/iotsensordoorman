<?php $pageTitle = 'ภาพรวมระบบ - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<?php
// ดึงสถานะทั้งหมดจาก DB (ไม่พึ่ง face_server API)
$db = getDB();

// ── สถานะอุปกรณ์ — ใช้ paired_devices เป็นหลัก fall back system_status ──
require_once __DIR__ . '/includes/device_status.php';
$ds = getDeviceStatus(90);  // fresh window = 90s (Pi re-announce every 30s + tolerance)
$cam1Online      = $ds['cam_outside'];
$cam2Online      = $ds['cam_inside'];
$faceServerOnline = $ds['pi_online'];
$esp32Online     = $ds['esp32_online'];

// สถิติวันนี้ — นับจำนวนครั้ง (rows) ไม่ใช่จำนวนคน เพื่อให้เปรียบเทียบ in/out เป็นคู่ได้ถูกต้อง
$stmt = $db->query("SELECT COUNT(*) as c FROM access_logs WHERE direction='IN' AND DATE(created_at) = CURDATE() AND is_authorized = 1 AND employee_id IS NOT NULL");
$todayIn = (int) ($stmt->fetch()['c'] ?? 0);

$stmt = $db->query("SELECT COUNT(*) as c FROM access_logs WHERE direction='OUT' AND DATE(created_at) = CURDATE() AND is_authorized = 1 AND employee_id IS NOT NULL");
$todayOut = (int) ($stmt->fetch()['c'] ?? 0);

// จำนวนคนปัจจุบันใน = นับ employee ที่ direction ล่าสุด = IN (วันนี้)
$stmt = $db->query("
    SELECT COUNT(*) AS c FROM (
        SELECT employee_id,
               SUBSTRING_INDEX(GROUP_CONCAT(direction ORDER BY created_at DESC), ',', 1) AS last_dir
        FROM access_logs
        WHERE DATE(created_at) = CURDATE() AND is_authorized = 1 AND employee_id IS NOT NULL
        GROUP BY employee_id
    ) t
    WHERE last_dir = 'IN'
");
$currentlyInside = (int) ($stmt->fetch()['c'] ?? 0);

$stmt = $db->query("SELECT COUNT(*) as c FROM anomaly_alerts WHERE is_resolved = 0");
$unresolvedAlerts = (int) ($stmt->fetch()['c'] ?? 0);

// เปรียบเทียบกับเมื่อวาน — ใช้สูตรเดียวกัน (count events)
$stmt = $db->query("SELECT COUNT(*) as c FROM access_logs WHERE direction='IN' AND DATE(created_at) = CURDATE() - INTERVAL 1 DAY AND is_authorized = 1 AND employee_id IS NOT NULL");
$yIn = (int) ($stmt->fetch()['c'] ?? 0);
$stmt = $db->query("SELECT COUNT(*) as c FROM access_logs WHERE direction='OUT' AND DATE(created_at) = CURDATE() - INTERVAL 1 DAY AND is_authorized = 1 AND employee_id IS NOT NULL");
$yOut = (int) ($stmt->fetch()['c'] ?? 0);
$yInside = max(0, $yIn - $yOut);  // สำหรับ delta — เปรียบเทียบ in-out
$stmt = $db->query("SELECT COUNT(*) as c FROM anomaly_alerts WHERE DATE(created_at) = CURDATE() - INTERVAL 1 DAY");
$yAlerts = (int) ($stmt->fetch()['c'] ?? 0);

// ประวัติล่าสุด
$stmt = $db->query("SELECT al.*, e.first_name, e.last_name, e.emp_code
    FROM access_logs al LEFT JOIN employees e ON al.employee_id = e.id
    ORDER BY al.created_at DESC LIMIT 8");
$recentLogs = $stmt->fetchAll();

function deltaBadge(int $today, int $yesterday): string {
    $diff = $today - $yesterday;
    if ($diff > 0) return '<span class="up">▲ +' . $diff . '</span>';
    if ($diff < 0) return '<span class="down">▼ ' . $diff . '</span>';
    return '<span class="muted">— 0</span>';
}
?>

<div class="page-head">
    <div>
        <h1>ภาพรวมระบบ</h1>
        <div class="sub">สถานการณ์เข้า-ออกห้องสโตร์ — อัปเดตทุก 5 วินาที</div>
    </div>
    <div class="page-head-actions">
        <button class="btn sm" id="btnPickDate" title="เลือกวันที่"><?= ico('calendar', 14) ?> วันนี้</button>
        <button class="btn sm" id="btnExport" title="ส่งออก CSV"><?= ico('download', 14) ?> ส่งออก</button>
    </div>
</div>

<!-- KPI Row -->
<div class="grid kpis section">
    <div class="card kpi">
        <div class="label"><span class="dot ok"></span>เข้างานวันนี้</div>
        <div class="num" id="kpiIn"><?= $todayIn ?></div>
        <div class="delta"><?= deltaBadge($todayIn, $yIn) ?><span>เทียบกับเมื่อวาน</span></div>
        <svg class="spark" viewBox="0 0 120 36" preserveAspectRatio="none" aria-hidden="true">
            <defs><linearGradient id="sparkOk" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="var(--ok)" stop-opacity="0.4"/><stop offset="100%" stop-color="var(--ok)" stop-opacity="0"/></linearGradient></defs>
            <polyline class="spark-line" points="0,28 12,20 24,24 36,16 48,22 60,12 72,18 84,8 96,16 108,10 120,14" fill="none" stroke="var(--ok)" stroke-width="1.6"/>
            <polygon points="0,28 12,20 24,24 36,16 48,22 60,12 72,18 84,8 96,16 108,10 120,14 120,36 0,36" fill="url(#sparkOk)"/>
        </svg>
    </div>
    <div class="card kpi">
        <div class="label"><span class="dot info"></span>ออกงานวันนี้</div>
        <div class="num" id="kpiOut"><?= $todayOut ?></div>
        <div class="delta"><?= deltaBadge($todayOut, $yOut) ?><span>เทียบกับเมื่อวาน</span></div>
        <svg class="spark" viewBox="0 0 120 36" preserveAspectRatio="none" aria-hidden="true">
            <defs><linearGradient id="sparkInfo" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="var(--info)" stop-opacity="0.4"/><stop offset="100%" stop-color="var(--info)" stop-opacity="0"/></linearGradient></defs>
            <polyline points="0,26 12,18 24,22 36,14 48,20 60,10 72,16 84,12 96,18 108,8 120,16" fill="none" stroke="var(--info)" stroke-width="1.6"/>
            <polygon points="0,26 12,18 24,22 36,14 48,20 60,10 72,16 84,12 96,18 108,8 120,16 120,36 0,36" fill="url(#sparkInfo)"/>
        </svg>
    </div>
    <div class="card kpi">
        <div class="label"><span class="dot accent"></span>อยู่ในพื้นที่</div>
        <div class="num" id="kpiInside"><?= $currentlyInside ?> <span class="unit">คน</span></div>
        <div class="delta"><?= deltaBadge($currentlyInside, $yInside) ?><span>เทียบกับเมื่อวาน</span></div>
        <svg class="spark" viewBox="0 0 120 36" preserveAspectRatio="none" aria-hidden="true">
            <defs><linearGradient id="sparkAccent" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="var(--accent)" stop-opacity="0.4"/><stop offset="100%" stop-color="var(--accent)" stop-opacity="0"/></linearGradient></defs>
            <polyline points="0,24 12,22 24,18 36,20 48,14 60,18 72,12 84,16 96,10 108,14 120,8" fill="none" stroke="var(--accent)" stroke-width="1.6"/>
            <polygon points="0,24 12,22 24,18 36,20 48,14 60,18 72,12 84,16 96,10 108,14 120,8 120,36 0,36" fill="url(#sparkAccent)"/>
        </svg>
    </div>
    <div class="card kpi">
        <div class="label"><span class="dot danger"></span>แจ้งเตือนค้าง</div>
        <div class="num" id="kpiAlerts"><?= $unresolvedAlerts ?></div>
        <div class="delta"><?= deltaBadge($unresolvedAlerts, $yAlerts) ?><span>เทียบกับเมื่อวาน</span></div>
        <svg class="spark" viewBox="0 0 120 36" preserveAspectRatio="none" aria-hidden="true">
            <defs><linearGradient id="sparkDanger" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="var(--danger)" stop-opacity="0.4"/><stop offset="100%" stop-color="var(--danger)" stop-opacity="0"/></linearGradient></defs>
            <polyline points="0,28 12,26 24,30 36,22 48,28 60,16 72,24 84,20 96,28 108,22 120,18" fill="none" stroke="var(--danger)" stroke-width="1.6"/>
            <polygon points="0,28 12,26 24,30 36,22 48,28 60,16 72,24 84,20 96,28 108,22 120,18 120,36 0,36" fill="url(#sparkDanger)"/>
        </svg>
    </div>
</div>

<!-- Row 2: Activity chart + Door card -->
<div class="grid dash section">
    <div class="card flush">
        <div class="card-head">
            <div>
                <div class="title">กิจกรรมรายชั่วโมง</div>
                <div class="sub">วันนี้ — เข้า / ออก</div>
            </div>
            <div class="actions">
                <span class="badge ok"><span class="dot"></span>เข้า</span>
                <span class="badge info"><span class="dot"></span>ออก</span>
            </div>
        </div>
        <div class="card-body">
            <div class="bar-chart" id="hourlyChart">
                <!-- bars filled by JS -->
            </div>
            <div class="chart-axis">
                <span>00</span><span>04</span><span>08</span><span>12</span><span>16</span><span>20</span><span>23</span>
            </div>
        </div>
    </div>

    <div class="card door-card">
        <div class="card-head" style="padding:0 0 14px; border-bottom:1px solid var(--border); margin-bottom:14px;">
            <div>
                <div class="title">สถานะประตู</div>
                <div class="sub">ESP32 controller</div>
            </div>
            <div class="actions"><span class="badge" id="doorTimer" style="display:none;"></span></div>
        </div>

        <div class="door-state locked" id="doorState">
            <div class="icon" id="doorStateIcon"><?= ico('lock', 22) ?></div>
            <div class="text">
                <strong id="doorStateText">ประตูล็อก</strong>
                <div class="sub" id="doorStateSub">ตรวจสอบ...</div>
            </div>
        </div>

        <div class="btn-row">
            <button class="btn primary sm" onclick="dashUnlock()"><?= ico('unlock', 14) ?> ปลดล็อก 8s</button>
            <button class="btn sm" onclick="dashLock()"><?= ico('lock', 14) ?> ล็อก</button>
        </div>

        <div class="sys-list" style="margin-top:14px;">
            <div class="item" id="sysFaceServer"><?= ico('server', 16) ?><span class="lbl">Face Server</span><span class="state">…</span></div>
            <div class="item" id="sysEsp32"><?= ico('chip', 16) ?><span class="lbl">ESP32</span><span class="state">…</span></div>
            <div class="item" id="sysCamOut"><?= ico('camera', 16) ?><span class="lbl">กล้องนอก</span><span class="state">…</span></div>
            <div class="item" id="sysCamIn"><?= ico('camera', 16) ?><span class="lbl">กล้องใน</span><span class="state">…</span></div>
        </div>
    </div>
</div>

<!-- Row 3: Live cameras + recent activity -->
<div class="grid dash section">
    <div class="card flush">
        <div class="card-head">
            <div>
                <div class="title">กล้องสด</div>
                <div class="sub">snapshot ทุก 5 วินาที</div>
            </div>
            <div class="actions">
                <a href="cameras.php" class="btn sm ghost">ดูเต็มจอ <?= ico('chev-r', 12) ?></a>
            </div>
        </div>
        <div class="card-body">
            <div class="grid cams">
                <!-- Camera Outside -->
                <div>
                    <div class="cam" id="camOutCard">
                        <img id="streamOutside" alt="Camera Outside" style="display:none">
                        <div class="cam-feed" id="streamOutPlaceholder">CAMERA — OUTSIDE</div>
                        <div class="cam-overlay">
                            <div class="row">
                                <span class="cam-chip" id="camOutChip"><span class="rec"></span><span id="camOutChipText">…</span></span>
                                <span class="cam-chip" id="camOutMeta">—</span>
                            </div>
                            <div class="row">
                                <span class="cam-chip" id="camOutTitle">กล้องด้านนอก</span>
                                <span class="cam-chip" id="camOutTime">—</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Camera Inside -->
                <div>
                    <div class="cam" id="camInCard">
                        <img id="streamInside" alt="Camera Inside" style="display:none">
                        <div class="cam-feed" id="streamInPlaceholder">CAMERA — INSIDE</div>
                        <div class="cam-overlay">
                            <div class="row">
                                <span class="cam-chip" id="camInChip"><span class="rec"></span><span id="camInChipText">…</span></span>
                                <span class="cam-chip" id="camInMeta">—</span>
                            </div>
                            <div class="row">
                                <span class="cam-chip" id="camInTitle">กล้องด้านใน</span>
                                <span class="cam-chip" id="camInTime">—</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card flush">
        <div class="card-head">
            <div>
                <div class="title">ประวัติล่าสุด</div>
                <div class="sub">8 รายการล่าสุด</div>
            </div>
            <div class="actions"><a href="logs.php" class="btn sm ghost">ดูทั้งหมด <?= ico('chev-r', 12) ?></a></div>
        </div>
        <div class="activity">
            <?php if (empty($recentLogs)): ?>
                <div class="empty">ยังไม่มีกิจกรรม</div>
            <?php else: foreach ($recentLogs as $log):
                $name = $log['first_name']
                    ? htmlspecialchars($log['first_name'] . ' ' . $log['last_name'])
                    : 'ไม่รู้จัก';
                $isUnknown = !$log['first_name'];
                $confidence = (float) ($log['confidence'] ?? 0);
                $confClass = $confidence > 70 ? '' : ($confidence > 40 ? 'warn' : 'danger');
                $dir = $log['direction'] === 'IN' ? 'เข้าห้อง' : 'ออกห้อง';
                $time = date('H:i:s', strtotime($log['created_at']));
                $initials = $isUnknown ? '?' : mb_strtoupper(mb_substr($log['first_name'], 0, 1));
                ?>
                <div class="activity-row">
                    <div class="av"><div class="avatar" <?= $isUnknown ? 'style="background:var(--danger-soft); color:var(--danger);"' : '' ?>><?= htmlspecialchars($initials) ?></div></div>
                    <div class="body">
                        <div class="top">
                            <span class="name <?= $isUnknown ? 'text-danger' : '' ?>"><?= $name ?></span>
                            <span class="verb"><?= htmlspecialchars($dir) ?></span>
                        </div>
                        <div class="meta">
                            <span><?= htmlspecialchars($log['emp_code'] ?? '-') ?></span>
                            <span>·</span>
                            <span><?= $log['camera_id'] == 1 ? 'cam-out' : 'cam-in' ?></span>
                        </div>
                    </div>
                    <div class="conf-bar <?= $confClass ?>"><div style="width: <?= max(2, min(100, $confidence)) ?>%"></div></div>
                    <span class="ts"><?= $time ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- Row 4: Hardware health -->
<div class="grid health section">
    <div class="card flush">
        <div class="card-head">
            <div>
                <div class="title">Raspberry Pi</div>
                <div class="sub" id="piUptime">uptime: —</div>
            </div>
            <div class="actions"><span class="badge" id="piStatusBadge">…</span></div>
        </div>
        <div class="card-body">
            <div class="gauge-grid">
                <div class="gauge-cell">
                    <div class="dial">
                        <svg width="84" height="84" viewBox="0 0 120 120" style="transform: rotate(-90deg);">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="var(--border)" stroke-width="8"/>
                            <circle id="tempRing" cx="60" cy="60" r="52" fill="none" stroke="var(--danger)" stroke-width="8" stroke-dasharray="327" stroke-dashoffset="327" stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease, stroke 0.5s ease"/>
                        </svg>
                        <div class="num"><span id="piTemp">—</span><small>°C</small></div>
                    </div>
                    <div class="gauge-label">อุณหภูมิ</div>
                </div>
                <div class="gauge-cell">
                    <div class="dial">
                        <svg width="84" height="84" viewBox="0 0 120 120" style="transform: rotate(-90deg);">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="var(--border)" stroke-width="8"/>
                            <circle id="cpuRing" cx="60" cy="60" r="52" fill="none" stroke="var(--accent)" stroke-width="8" stroke-dasharray="327" stroke-dashoffset="327" stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease, stroke 0.5s ease"/>
                        </svg>
                        <div class="num"><span id="piCpu">—</span><small>%</small></div>
                    </div>
                    <div class="gauge-label">CPU</div>
                </div>
                <div class="gauge-cell">
                    <div class="dial">
                        <svg width="84" height="84" viewBox="0 0 120 120" style="transform: rotate(-90deg);">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="var(--border)" stroke-width="8"/>
                            <circle id="ramRing" cx="60" cy="60" r="52" fill="none" stroke="var(--info)" stroke-width="8" stroke-dasharray="327" stroke-dashoffset="327" stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease, stroke 0.5s ease"/>
                        </svg>
                        <div class="num"><span id="piRam">—</span><small>%</small></div>
                    </div>
                    <div class="gauge-label">RAM</div>
                </div>
                <div class="gauge-cell">
                    <div class="dial">
                        <svg width="84" height="84" viewBox="0 0 120 120" style="transform: rotate(-90deg);">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="var(--border)" stroke-width="8"/>
                            <circle id="diskRing" cx="60" cy="60" r="52" fill="none" stroke="var(--warn)" stroke-width="8" stroke-dasharray="327" stroke-dashoffset="327" stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease, stroke 0.5s ease"/>
                        </svg>
                        <div class="num"><span id="piDisk">—</span><small>%</small></div>
                    </div>
                    <div class="gauge-label">Disk</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card flush">
        <div class="card-head">
            <div>
                <div class="title">ESP32</div>
                <div class="sub">Door controller</div>
            </div>
            <div class="actions"><span class="badge" id="espStatusBadge">…</span></div>
        </div>
        <div class="card-body">
            <div class="gauge" style="margin-bottom: 12px;">
                <div class="dial">
                    <svg width="84" height="84" viewBox="0 0 120 120" style="transform: rotate(-90deg);">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="var(--border)" stroke-width="8"/>
                        <circle id="wifiRing" cx="60" cy="60" r="52" fill="none" stroke="var(--info)" stroke-width="8" stroke-dasharray="327" stroke-dashoffset="327" stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease, stroke 0.5s ease"/>
                    </svg>
                    <div class="num"><span id="espRssi">—</span><small>dBm</small></div>
                </div>
                <div class="info">
                    <div class="lbl">WiFi signal</div>
                    <div class="sub" id="espIp">—</div>
                </div>
            </div>
            <div class="metric-row"><div class="lbl"><?= ico('door', 14) ?> ประตู</div><div class="val" id="espDoor">—</div></div>
            <div class="metric-row"><div class="lbl"><?= ico('clock', 14) ?> Uptime</div><div class="val" id="espUptime">—</div></div>
            <div class="metric-row"><div class="lbl"><?= ico('eye', 14) ?> PIR นอก</div><div class="val" id="espPirOut">—</div></div>
            <div class="metric-row"><div class="lbl"><?= ico('eye', 14) ?> PIR ใน</div><div class="val" id="espPirIn">—</div></div>
        </div>
    </div>
</div>

<script>
// ============================================================
// State
// ============================================================
let _camConfig = { outside: null, inside: null };

// ============================================================
// Gauge helpers (port from old dashboard, same math)
// ============================================================
const CIRC = 327; // 2π * 52
function setRing(id, percent, color) {
    const el = document.getElementById(id);
    if (!el) return;
    const offset = CIRC * (1 - Math.min(percent, 100) / 100);
    el.setAttribute('stroke-dashoffset', offset);
    if (color) el.setAttribute('stroke', color);
}
function colorByPercent(val, thresholds) {
    if (val < thresholds[0]) return 'var(--ok)';
    if (val < thresholds[1]) return 'var(--warn)';
    return 'var(--danger)';
}
function formatUptime(seconds) {
    const d = Math.floor(seconds / 86400);
    const h = Math.floor((seconds % 86400) / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (d > 0) return d + 'ว ' + h + 'ชม';
    if (h > 0) return h + 'ชม ' + m + 'น';
    return m + ' นาที';
}

// ============================================================
// Camera snapshots (เหมือนเดิม — refresh ทุก 5 วินาที)
// ============================================================
async function fetchCameraConfig() {
    try {
        const data = await fetchAPI(FACE_SERVER + '/api/status');
        if (!data) return;
        _camConfig.outside = data.camera_outside;
        _camConfig.inside  = data.camera_inside;
        updateCamCard('outside', data.camera_outside, 'camOutTitle', 'camOutChip', 'camOutChipText', 'streamOutside', 'streamOutPlaceholder', 'sysCamOut', 'camOutMeta');
        updateCamCard('inside',  data.camera_inside,  'camInTitle',  'camInChip',  'camInChipText',  'streamInside',  'streamInPlaceholder', 'sysCamIn',  'camInMeta');
    } catch {}
}

function setSysItem(id, status) {
    // status: 'online' | 'offline' | 'warn' | 'unknown'
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('online', 'offline', 'warn', 'unknown');
    el.classList.add(status);
    const stateText = { online: 'ONLINE', offline: 'OFFLINE', warn: 'WARN', unknown: '—' }[status] || '—';
    el.querySelector('.state').textContent = stateText;
}

function updateCamCard(side, cfg, titleId, chipId, chipTextId, imgId, phId, sysId, metaId) {
    const label = side === 'outside' ? 'ด้านนอก' : 'ด้านใน';
    const titleEl = document.getElementById(titleId);
    const chipEl  = document.getElementById(chipId);
    const chipTxt = document.getElementById(chipTextId);
    const imgEl   = document.getElementById(imgId);
    const phEl    = document.getElementById(phId);
    const metaEl  = document.getElementById(metaId);

    if (!cfg || cfg.id < 0) {
        titleEl.textContent = 'กล้อง' + label;
        chipEl.classList.add('warn'); chipEl.classList.remove('ok');
        chipTxt.textContent = 'ไม่ได้กำหนด';
        imgEl.style.display = 'none'; phEl.style.display = '';
        phEl.textContent = 'NO CAMERA ASSIGNED';
        metaEl.textContent = '—';
        setSysItem(sysId, 'warn');
        return;
    }
    const devName = cfg.device_name || ('video' + cfg.id);
    titleEl.textContent = 'กล้อง' + label + ' · ' + devName;
    metaEl.textContent = (cfg.width || '?') + '×' + (cfg.height || '?') + ' · ' + (cfg.fps || '15') + 'fps';

    if (cfg.active && cfg.has_frame) {
        chipEl.classList.add('ok'); chipEl.classList.remove('warn');
        chipTxt.textContent = 'LIVE';
        loadSnapshot(side, imgId, phId, chipId, chipTextId, sysId);
    } else {
        chipEl.classList.remove('ok'); chipEl.classList.remove('warn');
        chipTxt.textContent = 'OFFLINE';
        imgEl.style.display = 'none'; phEl.style.display = '';
        phEl.textContent = 'CAMERA OFFLINE';
        setSysItem(sysId, 'offline');
    }
}

function loadSnapshot(camId, imgId, phId, chipId, chipTextId, sysId) {
    const img = new Image();
    const timeout = setTimeout(() => { img.src = ''; setCamOffline(imgId, phId, chipId, chipTextId, sysId); }, 5000);
    img.onload = () => {
        clearTimeout(timeout);
        const el = document.getElementById(imgId);
        el.src = img.src;
        el.style.display = '';
        document.getElementById(phId).style.display = 'none';
        const chip = document.getElementById(chipId);
        chip.classList.add('ok');
        document.getElementById(chipTextId).textContent = 'LIVE';
        setSysItem(sysId, 'online');
        const timeId = camId === 'outside' ? 'camOutTime' : 'camInTime';
        document.getElementById(timeId).textContent = new Date().toLocaleTimeString('th-TH');
    };
    img.onerror = () => { clearTimeout(timeout); setCamOffline(imgId, phId, chipId, chipTextId, sysId); };
    img.src = FACE_SERVER + '/api/snapshot/' + camId + '?t=' + Date.now();
}
function setCamOffline(imgId, phId, chipId, chipTextId, sysId) {
    document.getElementById(imgId).style.display = 'none';
    document.getElementById(phId).style.display = '';
    document.getElementById(phId).textContent = 'CAMERA OFFLINE';
    document.getElementById(chipId).classList.remove('ok');
    document.getElementById(chipTextId).textContent = 'OFFLINE';
    setSysItem(sysId, 'offline');
}
function refreshDashboardSnapshots() {
    if (_camConfig.outside && _camConfig.outside.id >= 0) loadSnapshot('outside', 'streamOutside', 'streamOutPlaceholder', 'camOutChip', 'camOutChipText', 'sysCamOut');
    if (_camConfig.inside  && _camConfig.inside.id  >= 0) loadSnapshot('inside',  'streamInside',  'streamInPlaceholder',  'camInChip',  'camInChipText',  'sysCamIn');
}

// ============================================================
// Door controls
// ============================================================
async function dashUnlock() {
    const res = await postAPI(FACE_SERVER + '/api/door/unlock');
    if (res?.success) {
        applyDoorState('unlocked');
        showToast('ปลดล็อกประตูแล้ว · auto-lock ใน 8s', 'success');
    } else {
        showToast('สั่งปลดล็อกไม่สำเร็จ', 'error');
    }
}
async function dashLock() {
    const res = await postAPI(FACE_SERVER + '/api/door/lock');
    if (res?.success) {
        applyDoorState('locked');
        showToast('ล็อกประตูแล้ว', 'success');
    } else {
        showToast('สั่งล็อกไม่สำเร็จ', 'error');
    }
}
function applyDoorState(state) {
    const card = document.getElementById('doorState');
    const text = document.getElementById('doorStateText');
    const icon = document.getElementById('doorStateIcon');
    if (state === 'unlocked') {
        card.classList.add('unlocked'); card.classList.remove('locked');
        text.textContent = 'ประตูปลดล็อก';
        icon.innerHTML = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 7-2.7"/></svg>`;
    } else {
        card.classList.add('locked'); card.classList.remove('unlocked');
        text.textContent = 'ประตูล็อก';
        icon.innerHTML = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>`;
    }
}

// ============================================================
// Pi health
// ============================================================
async function fetchPiHealth() {
    try {
        const resp = await fetch('api/pi_health.php', { signal: AbortSignal.timeout(5000) });
        if (!resp.ok) throw new Error();
        const d = await resp.json();

        // Temperature (max 85°C scale)
        const temp = d.cpu_temp ?? 0;
        const tempPct = Math.min(temp / 85 * 100, 100);
        document.getElementById('piTemp').textContent = temp ? temp.toFixed(1) : '—';
        setRing('tempRing', tempPct, colorByPercent(temp, [55, 70]));

        document.getElementById('piCpu').textContent = d.cpu_percent.toFixed(0);
        setRing('cpuRing', d.cpu_percent, colorByPercent(d.cpu_percent, [50, 80]));

        document.getElementById('piRam').textContent = d.ram_percent.toFixed(0);
        setRing('ramRing', d.ram_percent, colorByPercent(d.ram_percent, [60, 85]));

        document.getElementById('piDisk').textContent = d.disk_percent.toFixed(0);
        setRing('diskRing', d.disk_percent, colorByPercent(d.disk_percent, [70, 90]));

        document.getElementById('piUptime').textContent = 'uptime: ' + formatUptime(d.uptime);

        const badge = document.getElementById('piStatusBadge');
        badge.textContent = 'ONLINE';
        badge.className = 'badge ok';
        setSysItem('sysFaceServer', 'online');
    } catch {
        const badge = document.getElementById('piStatusBadge');
        badge.textContent = 'OFFLINE';
        badge.className = 'badge danger';
        setSysItem('sysFaceServer', 'offline');
    }
}

// ============================================================
// ESP32 health
// ============================================================
async function fetchEspHealth() {
    const badge = document.getElementById('espStatusBadge');
    try {
        const resp = await fetch(FACE_SERVER + '/api/esp32/health', { signal: AbortSignal.timeout(5000) });
        if (!resp.ok) throw new Error();
        const d = await resp.json();

        if (!d.online) {
            badge.textContent = 'OFFLINE'; badge.className = 'badge danger';
            setRing('wifiRing', 0);
            setSysItem('sysEsp32', 'offline');
            document.getElementById('espPirOut').innerHTML = '<span class="muted">—</span>';
            document.getElementById('espPirIn').innerHTML  = '<span class="muted">—</span>';
            return;
        }

        badge.textContent = 'ONLINE'; badge.className = 'badge ok';
        setSysItem('sysEsp32', 'online');

        const rssi = d.rssi ?? -90;
        const wifiPct = Math.max(0, Math.min(100, (rssi + 90) / 60 * 100));
        document.getElementById('espRssi').textContent = rssi;
        setRing('wifiRing', wifiPct, wifiPct > 60 ? 'var(--ok)' : wifiPct > 30 ? 'var(--warn)' : 'var(--danger)');

        // Door
        applyDoorState(d.door === 'locked' ? 'locked' : 'unlocked');
        document.getElementById('espDoor').textContent = d.door === 'locked' ? 'ล็อก' : 'เปิด';
        document.getElementById('doorStateSub').textContent = 'ESP32 ' + (d.ip || '-');

        document.getElementById('espUptime').textContent = formatUptime(d.uptime_sec ?? 0);
        document.getElementById('espIp').textContent = d.ip ?? '—';

        document.getElementById('espPirOut').innerHTML = d.pir_outside
            ? '<span class="text-warn">ตรวจจับ</span>'
            : '<span class="muted">ปกติ</span>';
        document.getElementById('espPirIn').innerHTML = d.pir_inside
            ? '<span class="text-warn">ตรวจจับ</span>'
            : '<span class="muted">ปกติ</span>';
    } catch {
        badge.textContent = 'OFFLINE'; badge.className = 'badge danger';
        setSysItem('sysEsp32', 'offline');
    }
}

// ============================================================
// Hourly activity chart (เข้า/ออก รายชั่วโมง วันนี้)
// ============================================================
async function loadHourlyChart() {
    const chart = document.getElementById('hourlyChart');
    try {
        const today = new Date().toISOString().split('T')[0];
        const res = await fetchAPI('api/access_logs.php?limit=500&page=1&date_from=' + today + '&date_to=' + today);
        if (!res?.data) return;
        const hoursIn = new Array(24).fill(0);
        const hoursOut = new Array(24).fill(0);
        res.data.forEach(log => {
            const h = new Date(log.created_at).getHours();
            if (log.is_authorized == 1 && log.direction === 'IN') hoursIn[h]++;
            else if (log.is_authorized == 1 && log.direction === 'OUT') hoursOut[h]++;
        });
        const maxVal = Math.max(1, ...hoursIn, ...hoursOut);
        const currentHour = new Date().getHours();
        chart.innerHTML = '';
        for (let h = 0; h < 24; h++) {
            const inH  = (hoursIn[h] / maxVal) * 100;
            const outH = (hoursOut[h] / maxVal) * 100;
            const isCurrent = h === currentHour;
            const dim = h > currentHour ? 'dim' : '';
            chart.innerHTML += `
                <div class="bar-col ${dim}">
                    <div class="tip">${h.toString().padStart(2,'0')}:00 · เข้า ${hoursIn[h]} ออก ${hoursOut[h]}</div>
                    ${outH > 0 ? `<div class="bar out" style="height:${outH}%"></div>` : ''}
                    <div class="bar in ${isCurrent ? 'current' : ''}" style="height:${Math.max(2, inH)}%"></div>
                </div>`;
        }
    } catch {
        chart.innerHTML = '<div class="empty" style="width:100%;">โหลดกราฟไม่ได้</div>';
    }
}

// ============================================================
// Init + polling intervals (5s ตามเดิม)
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    fetchCameraConfig();
    fetchPiHealth();
    fetchEspHealth();
    loadHourlyChart();
    setTimeout(refreshDashboardSnapshots, 2000);
    setInterval(refreshDashboardSnapshots, 5000);
    setInterval(fetchCameraConfig, 15000);
    setInterval(fetchPiHealth, 5000);
    setInterval(fetchEspHealth, 5000);
    setInterval(loadHourlyChart, 60000);

    // Export CSV — ใช้ logs endpoint ที่มีอยู่
    document.getElementById('btnExport').addEventListener('click', () => {
        const today = new Date().toISOString().split('T')[0];
        window.location.href = 'api/access_logs.php?export=csv&date_from=' + today + '&date_to=' + today;
    });
});
</script>

<?php include 'includes/footer.php'; ?>
