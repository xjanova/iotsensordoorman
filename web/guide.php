<?php $pageTitle = 'คู่มือระบบ - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<div class="page-head">
    <div>
        <h1>คู่มือระบบ</h1>
        <div class="sub">เอกสารประกอบการติดตั้งและใช้งานระบบ Bunny Door</div>
    </div>
</div>

<!-- Tab Navigation (new design) -->
<div class="guide-tabs section">
    <button onclick="switchTab('overview')" id="tab-overview" class="guide-tab active"><?= ico('sitemap', 14) ?> ภาพรวมระบบ</button>
    <button onclick="switchTab('wiring')"   id="tab-wiring"   class="guide-tab"><?= ico('plug', 14) ?> การต่อวงจร</button>
    <button onclick="switchTab('arduino')"  id="tab-arduino"  class="guide-tab"><?= ico('chip', 14) ?> โค้ด Arduino</button>
    <button onclick="switchTab('raspi')"    id="tab-raspi"    class="guide-tab"><?= ico('raspberry', 14) ?> Raspberry Pi</button>
</div>

<!-- ============================================================ -->
<!-- Tab 1: System Overview -->
<!-- ============================================================ -->
<div id="panel-overview" class="tab-panel">

<div class="card section">
    <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 16px;">สถาปัตยกรรมระบบ</h3>
    <div class="grid cols-3" style="gap: 14px; margin-bottom: 18px;">
        <!-- ESP32 -->
        <div class="stat-block" style="--accent-soft: rgba(16,185,129,0.14); --accent: #10b981;">
            <div class="ic"><?= ico('chip', 18) ?></div>
            <strong>ESP32</strong>
            <p>ตัวควบคุมประตู</p>
            <ul class="check-list" style="margin-top: 10px;">
                <li><?= ico('check', 12) ?> เซ็นเซอร์ PIR 2 ตัว</li>
                <li><?= ico('check', 12) ?> Relay กลอนแม่เหล็ก 12V</li>
                <li><?= ico('check', 12) ?> LED + Buzzer แจ้งเตือน</li>
                <li><?= ico('check', 12) ?> ปุ่ม Emergency Exit</li>
                <li><?= ico('check', 12) ?> WiFi HTTP Server</li>
            </ul>
        </div>
        <!-- Raspberry Pi -->
        <div class="stat-block" style="--accent-soft: rgba(99,102,241,0.14); --accent: #6366f1;">
            <div class="ic"><?= ico('raspberry', 18) ?></div>
            <strong>Raspberry Pi 4</strong>
            <p>ศูนย์กลางประมวลผล</p>
            <ul class="check-list" style="margin-top: 10px;">
                <li><?= ico('check', 12) ?> กล้อง USB 2 ตัว</li>
                <li><?= ico('check', 12) ?> Face Recognition (HOG)</li>
                <li><?= ico('check', 12) ?> Python Flask API</li>
                <li><?= ico('check', 12) ?> Motion Detection (MOG2)</li>
                <li><?= ico('check', 12) ?> MariaDB Database</li>
            </ul>
        </div>
        <!-- Web Dashboard -->
        <div class="stat-block" style="--accent-soft: rgba(37,99,235,0.14); --accent: #2563eb;">
            <div class="ic"><?= ico('globe', 18) ?></div>
            <strong>Web Dashboard</strong>
            <p>หน้าจอควบคุม</p>
            <ul class="check-list" style="margin-top: 10px;">
                <li><?= ico('check', 12) ?> Dashboard สถิติ</li>
                <li><?= ico('check', 12) ?> จัดการพนักงาน CRUD</li>
                <li><?= ico('check', 12) ?> ดูกล้องสด Live</li>
                <li><?= ico('check', 12) ?> ประวัติเข้า-ออก</li>
                <li><?= ico('check', 12) ?> แจ้งเตือนความผิดปกติ</li>
            </ul>
        </div>
    </div>

    <h4 style="font-size: 14px; font-weight: 600; margin: 18px 0 10px;">ขั้นตอนการทำงาน</h4>
    <div class="arch-diagram">
        <div class="arch-node"><strong>คนเดินมา</strong><small>walker</small></div>
        <span class="arch-arrow">→</span>
        <div class="arch-node"><strong>PIR ตรวจจับ</strong><small>motion</small></div>
        <span class="arch-arrow">→</span>
        <div class="arch-node"><strong>กล้องถ่ายรูป</strong><small>frame</small></div>
        <span class="arch-arrow">→</span>
        <div class="arch-node"><strong>จดจำใบหน้า</strong><small>recognize</small></div>
        <span class="arch-arrow">→</span>
        <div class="arch-node ok"><strong>ปลดล็อกประตู</strong><small>unlock 8s</small></div>
    </div>
</div>

<div class="card flush">
    <div class="card-head">
        <div class="title">รายการอุปกรณ์ (BOM)</div>
        <div class="sub">Bill of Materials</div>
    </div>
    <div style="overflow-x: auto;">
        <table class="tbl">
            <thead><tr><th>อุปกรณ์</th><th>รุ่น/สเปค</th><th>จำนวน</th><th>หน้าที่</th></tr></thead>
            <tbody>
                <tr><td><strong>ESP32</strong></td><td>ESP32-WROOM-32</td><td><span class="mono">1</span></td><td class="muted">ควบคุมประตู, อ่าน PIR, สั่ง Relay</td></tr>
                <tr><td><strong>Raspberry Pi</strong></td><td>Raspberry Pi 4 (4GB+)</td><td><span class="mono">1</span></td><td class="muted">ประมวลผลใบหน้า, Web Server</td></tr>
                <tr><td><strong>PIR Sensor</strong></td><td>HC-SR501</td><td><span class="mono">2</span></td><td class="muted">ตรวจจับความเคลื่อนไหว (นอก/ใน)</td></tr>
                <tr><td><strong>USB Camera</strong></td><td>USB Webcam 720p+</td><td><span class="mono">2</span></td><td class="muted">ถ่ายภาพใบหน้า (นอก/ใน)</td></tr>
                <tr><td><strong>Relay Module</strong></td><td>5V 1-Channel</td><td><span class="mono">1</span></td><td class="muted">ควบคุมกลอนแม่เหล็กไฟฟ้า</td></tr>
                <tr><td><strong>กลอนแม่เหล็ก</strong></td><td>Electromagnetic Lock 12V</td><td><span class="mono">1</span></td><td class="muted">ล็อก/ปลดล็อกประตู</td></tr>
                <tr><td><strong>Buzzer</strong></td><td>Active Buzzer 5V</td><td><span class="mono">1</span></td><td class="muted">แจ้งเตือนเสียง</td></tr>
                <tr><td><strong>LED</strong></td><td>5mm (เขียว + แดง)</td><td><span class="mono">2</span></td><td class="muted">แสดงสถานะอนุญาต/ไม่อนุญาต</td></tr>
                <tr><td><strong>Emergency Button</strong></td><td>Push Button + Pull-up</td><td><span class="mono">1</span></td><td class="muted">ปลดล็อกฉุกเฉิน</td></tr>
                <tr><td><strong>Power Supply</strong></td><td>12V 2A + 5V 3A</td><td><span class="mono">2</span></td><td class="muted">กลอนแม่เหล็ก + ESP32/Pi</td></tr>
            </tbody>
        </table>
    </div>
</div>
</div>

<!-- ============================================================ -->
<!-- Tab 2: Wiring Guide -->
<!-- ============================================================ -->
<div id="panel-wiring" class="tab-panel hidden">

<div class="card flush section">
    <div class="card-head">
        <div class="title">ESP32 GPIO Pin Mapping</div>
        <div class="sub">ESP32-WROOM-32 Dev Board (30 pin)</div>
    </div>
    <div style="overflow-x: auto;">
        <table class="tbl">
            <thead><tr><th>GPIO Pin</th><th>อุปกรณ์</th><th>ทิศทาง</th><th>หมายเหตุ</th></tr></thead>
            <tbody>
                <tr><td><span class="mono badge warn">GPIO 27</span></td><td><strong>PIR Sensor ด้านนอก</strong></td><td><span class="badge info">INPUT</span></td><td class="muted">ขา OUT ของ PIR ตัวที่ 1 (หน้าประตู)</td></tr>
                <tr><td><span class="mono badge warn">GPIO 26</span></td><td><strong>PIR Sensor ด้านใน</strong></td><td><span class="badge info">INPUT</span></td><td class="muted">ขา OUT ของ PIR ตัวที่ 2 (ในห้อง)</td></tr>
                <tr><td><span class="mono badge danger">GPIO 4</span></td><td><strong>Relay Module (IN)</strong></td><td><span class="badge warn">OUTPUT</span></td><td class="muted">HIGH = ปลดล็อก / LOW = ล็อก</td></tr>
                <tr><td><span class="mono badge accent">GPIO 33</span></td><td><strong>Buzzer</strong></td><td><span class="badge warn">OUTPUT</span></td><td class="muted">เสียงแจ้งเตือน (อนุญาต / ปฏิเสธ)</td></tr>
                <tr><td><span class="mono badge ok">GPIO 2</span></td><td><strong>LED สถานะ</strong></td><td><span class="badge warn">OUTPUT</span></td><td class="muted">ติด = ประตูปลดล็อก (LED Built-in)</td></tr>
                <tr><td><span class="mono badge">GPIO 13</span></td><td><strong>Emergency Button</strong></td><td><span class="badge info">INPUT_PULLUP</span></td><td class="muted">กด = ปลดล็อกฉุกเฉิน</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card section">
    <h3 style="font-size: 15px; font-weight: 600; margin: 0 0 14px;">การต่อ PIR Sensor (HC-SR501)</h3>
    <div class="grid cols-2" style="gap: 14px;">
        <div>
            <strong style="font-size: 13px; color: var(--ok);">PIR ตัวที่ 1 — ด้านนอกประตู</strong>
            <div style="margin-top: 8px; display: flex; flex-direction: column; gap: 6px;">
                <div class="metric-row"><div class="lbl"><span class="badge danger mono">VCC</span> →</div><div class="val mono">5V (ESP32 VIN)</div></div>
                <div class="metric-row"><div class="lbl"><span class="badge warn mono">OUT</span> →</div><div class="val mono">GPIO 27</div></div>
                <div class="metric-row"><div class="lbl"><span class="badge mono">GND</span> →</div><div class="val mono">GND</div></div>
            </div>
        </div>
        <div>
            <strong style="font-size: 13px; color: var(--info);">PIR ตัวที่ 2 — ด้านในห้อง</strong>
            <div style="margin-top: 8px; display: flex; flex-direction: column; gap: 6px;">
                <div class="metric-row"><div class="lbl"><span class="badge danger mono">VCC</span> →</div><div class="val mono">5V (ESP32 VIN)</div></div>
                <div class="metric-row"><div class="lbl"><span class="badge warn mono">OUT</span> →</div><div class="val mono">GPIO 26</div></div>
                <div class="metric-row"><div class="lbl"><span class="badge mono">GND</span> →</div><div class="val mono">GND</div></div>
            </div>
        </div>
    </div>
    <div class="setup-card warn" style="margin-top: 14px;">
        <div class="setup-icon warn"><?= ico('lightbulb', 18) ?></div>
        <div>
            <strong style="font-size: 13px;">Tip การติดตั้ง</strong>
            <p class="tiny muted" style="margin: 4px 0 0;">ติด PIR สูงจากพื้น 1.5-2 เมตร หันโดมลงเล็กน้อย — อย่าหันไปทางหน้าต่างที่มีแสงแดด (อาจ trigger ผิด)</p>
        </div>
    </div>
</div>

<div class="card section">
    <h3 style="font-size: 15px; font-weight: 600; margin: 0 0 14px;">Relay Module + กลอนแม่เหล็ก 12V</h3>
    <div class="grid cols-2" style="gap: 14px;">
        <div>
            <strong style="font-size: 13px; color: var(--ok);">ด้าน Low Voltage (ESP32 → Relay)</strong>
            <div style="margin-top: 8px; display: flex; flex-direction: column; gap: 6px;">
                <div class="metric-row"><div class="lbl"><span class="badge danger mono">VCC</span> →</div><div class="val mono">5V (ESP32)</div></div>
                <div class="metric-row"><div class="lbl"><span class="badge mono">GND</span> →</div><div class="val mono">GND (ESP32)</div></div>
                <div class="metric-row"><div class="lbl"><span class="badge ok mono">IN</span> →</div><div class="val mono">GPIO 4</div></div>
            </div>
        </div>
        <div>
            <strong style="font-size: 13px; color: var(--danger);">ด้าน High Voltage (Relay → กลอน 12V)</strong>
            <div style="margin-top: 8px; display: flex; flex-direction: column; gap: 6px;">
                <div class="metric-row"><div class="lbl"><span class="badge warn mono">COM</span> →</div><div class="val">Adapter 12V (+)</div></div>
                <div class="metric-row"><div class="lbl"><span class="badge info mono">NO</span> →</div><div class="val">กลอน (+)</div></div>
                <div class="metric-row"><div class="lbl muted">—</div><div class="val tiny muted">กลอน (-) → 12V (-)</div></div>
            </div>
        </div>
    </div>
    <pre class="code-block term" style="margin-top: 14px;"><span class="muted">// แผนภาพวงจร Relay</span>
<span class="text-ok">ESP32 GPIO 4 ──&gt; [Relay IN]</span>
<span class="text-danger">ESP32 5V ────────&gt; [Relay VCC]</span>
<span class="muted">ESP32 GND ───────&gt; [Relay GND]</span>
<span class="text-warn">Adapter 12V (+) ─&gt; [Relay COM]</span>
<span class="text-info">[Relay NO] ──────&gt; กลอน (+)</span>
<span class="muted">กลอน (-) ────────&gt; Adapter 12V (-)</span></pre>
</div>

<div class="grid cols-2 section">
    <div class="card">
        <h3 style="font-size: 14px; font-weight: 600; margin: 0 0 12px;">LED + Buzzer</h3>
        <div class="metric-row"><div class="lbl"><span class="mono badge ok">GPIO 2</span> →</div><div class="val tiny">LED Built-in (ติด=ปลดล็อก / ดับ=ล็อก)</div></div>
        <div class="metric-row"><div class="lbl"><span class="mono badge accent">GPIO 33</span> →</div><div class="val tiny">Buzzer (+) (Buzzer (-) → GND)</div></div>
        <p class="tiny muted" style="margin-top: 10px;">Active Buzzer มีขั้ว: ขายาว=(+) ขาสั้น=(-)</p>
    </div>
    <div class="card">
        <h3 style="font-size: 14px; font-weight: 600; margin: 0 0 12px;">Emergency Button</h3>
        <div class="metric-row"><div class="lbl"><span class="badge mono">ขา 1</span> →</div><div class="val mono">GPIO 13 (PULLUP)</div></div>
        <div class="metric-row"><div class="lbl"><span class="badge mono">ขา 2</span> →</div><div class="val mono">GND</div></div>
        <p class="tiny muted" style="margin-top: 10px;">ใช้ Internal Pull-up — ไม่ต้องต่อ Resistor เพิ่ม / ติดด้านในห้องเท่านั้น</p>
    </div>
</div>

<div class="card flush">
    <div class="card-head"><div class="title">แผนภาพรวมการต่อสายทั้งระบบ</div></div>
    <pre class="code-block term" style="border-radius: 0 0 var(--radius-lg) var(--radius-lg); border-top: 0;">                    ┌─────────────────────────────────────┐
                    │          <span class="text-ok">ESP32-WROOM-32</span>             │
  <span class="text-warn">PIR นอก</span> (OUT) ──── │  <span class="text-warn">GPIO 27</span>                       5V │ ──── <span class="text-danger">PIR VCC (x2)</span>
  <span class="text-warn">PIR ใน</span>  (OUT) ──── │  <span class="text-warn">GPIO 26</span>                      GND │ ──── <span class="muted">GND ร่วมทั้งหมด</span>
 <span class="text-danger">Relay</span> (IN) ──────── │  <span class="text-danger">GPIO 4</span>                           │
 <span class="text-accent">Buzzer</span> (+) ─────── │  <span class="text-accent">GPIO 33</span>                          │
 <span class="text-ok">LED สถานะ</span> ──────── │  <span class="text-ok">GPIO 2</span>   (Built-in LED)          │
 <span class="muted">ปุ่ม Emergency</span> ──── │  <span class="muted">GPIO 13</span>  (PULLUP)              │
                    └─────────────────────────────────────┘

  ┌──────────────┐        ┌─────────────────┐        ┌─────────────┐
  │  <span class="text-warn">Adapter 12V</span>  │        │   <span class="text-danger">Relay Module</span>   │        │  <span class="text-info">กลอนแม่เหล็ก</span> │
  │  (+)────────────────── │ COM         NO │ ─────── │ (+)         │
  │  (-)──────────────┬─── │ GND            │        │ (-)─────┐   │
  └──────────────┘    │    └─────────────────┘        └─────────┘   │
                      └─────────────────────────────────────────────┘

  ┌───────────────────────────────────────────────────────┐
  │                  <span class="text-accent">Raspberry Pi 4</span>                       │
  │   USB Camera x2 (นอก+ใน) ── ต่อ USB Port             │
  │   Face Recognition Server ── Python Flask :5000      │
  │   MariaDB Database ── เก็บข้อมูลพนักงาน/ประวัติ       │
  └───────────────────────────────────────────────────────┘</pre>
</div>
</div>

<!-- ============================================================ -->
<!-- Tab 3: Arduino Code -->
<!-- ============================================================ -->
<div id="panel-arduino" class="tab-panel hidden">

<div class="card flush section">
    <div class="card-head">
        <div>
            <div class="title">ESP32 Arduino Source Code</div>
            <div class="sub">door_controller.ino</div>
        </div>
        <div class="actions">
            <button onclick="copyArduinoCode()" class="btn primary sm"><?= ico('copy', 12) ?> คัดลอกโค้ด</button>
        </div>
    </div>
    <pre class="code-block term" style="border-radius: 0 0 var(--radius-lg) var(--radius-lg); border-top: 0; max-height: 70vh;"><code id="arduinoCode"><?php
$arduinoPath = __DIR__ . '/../esp32/door_controller/door_controller.ino';
if (file_exists($arduinoPath)) echo htmlspecialchars(file_get_contents($arduinoPath));
else echo '// ไม่พบไฟล์ door_controller.ino';
?></code></pre>
</div>

<div class="card section">
    <h3 style="font-size: 15px; font-weight: 600; margin: 0 0 14px;">การตั้งค่าก่อนอัปโหลด</h3>
    <div class="setup-card warn">
        <div class="setup-icon warn"><?= ico('alert-tri', 18) ?></div>
        <div>
            <strong style="font-size: 13px;">ต้องแก้ไขค่าเหล่านี้ก่อนอัปโหลด:</strong>
            <ul style="margin: 8px 0 0; padding-left: 16px; font-size: 12.5px; color: var(--text-2); list-style: disc;">
                <li><code class="mono text-accent">WIFI_SSID</code> — ชื่อ WiFi ที่ต้องการเชื่อมต่อ</li>
                <li><code class="mono text-accent">WIFI_PASSWORD</code> — รหัสผ่าน WiFi</li>
                <li><code class="mono text-accent">SERVER_URL</code> — IP ของ Raspberry Pi (เช่น http://192.168.1.50:5000)</li>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <h3 style="font-size: 15px; font-weight: 600; margin: 0 0 14px;">วิธีอัปโหลดโค้ด</h3>
    <ol class="numbered-list">
        <li><div class="num">1</div><div><strong>ติดตั้ง Arduino IDE</strong> — ดาวน์โหลดจาก arduino.cc/en/software</div></li>
        <li><div class="num">2</div><div><strong>เพิ่ม ESP32 Board</strong> — File &gt; Preferences &gt; Additional Board URLs: เพิ่ม ESP32 URL แล้วติดตั้งผ่าน Board Manager</div></li>
        <li><div class="num">3</div><div><strong>ติดตั้ง Library: ArduinoJson</strong> — Sketch &gt; Include Library &gt; Manage Libraries &gt; ค้นหา "ArduinoJson" by Benoit Blanchon</div></li>
        <li><div class="num">4</div><div><strong>เลือก Board</strong> — Tools &gt; Board &gt; ESP32 Arduino &gt; ESP32 Dev Module</div></li>
        <li><div class="num">5</div><div><strong>เลือก Port + Upload</strong> — Tools &gt; Port &gt; เลือก COM Port ของ ESP32 แล้วกด Upload</div></li>
    </ol>
</div>
</div>

<!-- ============================================================ -->
<!-- Tab 4: Raspberry Pi Setup -->
<!-- ============================================================ -->
<div id="panel-raspi" class="tab-panel hidden">

<div class="card section">
    <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 8px;">ติดตั้งระบบบน Raspberry Pi 4</h3>
    <p class="muted" style="font-size: 13px; margin: 0 0 18px;">ขั้นตอนทั้งหมดสำหรับติดตั้งระบบ Bunny Door</p>
    <ol class="numbered-list">
        <li><div class="num">1</div><div>
            <strong>เตรียม OS</strong> — ใช้ Raspberry Pi Imager เขียน Raspberry Pi OS (64-bit) ลง SD Card (32GB+)<br>
            <span class="tiny muted">เปิด SSH และตั้งค่า WiFi ใน Imager ก่อนเขียน</span>
        </div></li>
        <li><div class="num">2</div><div>
            <strong>อัปเดตระบบ</strong>
            <pre class="code-block term" style="margin-top: 8px;">sudo apt update && sudo apt upgrade -y</pre>
        </div></li>
        <li><div class="num">3</div><div>
            <strong>ติดตั้ง Dependencies</strong>
            <pre class="code-block term" style="margin-top: 8px;">sudo apt install -y python3-pip python3-venv cmake \
  build-essential libopenblas-dev liblapack-dev \
  libhdf5-dev libjpeg-dev libpng-dev \
  mariadb-server libmariadb-dev</pre>
        </div></li>
        <li><div class="num">4</div><div>
            <strong>ตั้งค่า MariaDB</strong>
            <pre class="code-block term" style="margin-top: 8px;">sudo mysql_secure_installation
sudo mysql -u root -p
CREATE DATABASE bunny_door CHARACTER SET utf8mb4;
CREATE USER 'bunny'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL ON bunny_door.* TO 'bunny'@'localhost';
FLUSH PRIVILEGES;</pre>
        </div></li>
        <li><div class="num">5</div><div>
            <strong>Python Virtual Environment</strong>
            <pre class="code-block term" style="margin-top: 8px;">cd ~
python3 -m venv bunny-env
source bunny-env/bin/activate</pre>
        </div></li>
        <li><div class="num">6</div><div>
            <strong>ติดตั้ง dlib + face_recognition</strong>
            <div class="setup-card warn" style="margin: 8px 0;">
                <div class="setup-icon warn"><?= ico('clock', 18) ?></div>
                <div><strong style="font-size: 12.5px;">ใช้เวลาประมาณ 30-60 นาทีในการ compile</strong></div>
            </div>
            <pre class="code-block term"># เพิ่ม swap ชั่วคราว (สำคัญมาก!)
sudo dphys-swapfile swapoff
sudo sed -i 's/CONF_SWAPSIZE=.*/CONF_SWAPSIZE=2048/' /etc/dphys-swapfile
sudo dphys-swapfile setup
sudo dphys-swapfile swapon

pip install dlib face_recognition

# คืนค่า swap
sudo sed -i 's/CONF_SWAPSIZE=.*/CONF_SWAPSIZE=100/' /etc/dphys-swapfile
sudo dphys-swapfile setup</pre>
        </div></li>
        <li><div class="num">7</div><div>
            <strong>ติดตั้ง Packages ที่เหลือ</strong>
            <pre class="code-block term" style="margin-top: 8px;">pip install flask flask-cors opencv-python-headless \
  numpy mysql-connector-python requests Pillow</pre>
        </div></li>
        <li><div class="num">8</div><div>
            <strong>Clone โปรเจค + ตั้งค่า .env</strong>
            <pre class="code-block term" style="margin-top: 8px;">cd ~
git clone https://github.com/xjanova/iotsensordoorman.git bunny-door
cd bunny-door/python
cp .env.example .env
nano .env  # แก้ DB_USER, DB_PASS, ESP32_IP</pre>
        </div></li>
        <li><div class="num">9</div><div>
            <strong>นำเข้าฐานข้อมูล</strong>
            <pre class="code-block term" style="margin-top: 8px;">mysql -u bunny -p bunny_door < ~/bunny-door/database/schema.sql</pre>
        </div></li>
        <li><div class="num">10</div><div>
            <strong>เตรียมรูปใบหน้า</strong>
            <pre class="code-block term" style="margin-top: 8px;">mkdir -p ~/bunny-door/python/images
# คัดลอกรูปใบหน้าพนักงานไปยัง images/
# ชื่อไฟล์ตรงกับ face_image ในตาราง employees</pre>
        </div></li>
        <li><div class="num">11</div><div>
            <strong>ทดสอบกล้อง</strong>
            <pre class="code-block term" style="margin-top: 8px;">ls /dev/video*
# ควรเห็น /dev/video0 และ /dev/video2</pre>
        </div></li>
        <li><div class="num">12</div><div>
            <strong>รันระบบ</strong>
            <pre class="code-block term" style="margin-top: 8px;">source ~/bunny-env/bin/activate
cd ~/bunny-door/python
python face_server.py</pre>
        </div></li>
        <li><div class="num">13</div><div>
            <strong>ตั้งค่า Auto-start (systemd)</strong>
            <pre class="code-block term" style="margin-top: 8px;">sudo nano /etc/systemd/system/bunny-door.service</pre>
            <p class="tiny muted" style="margin: 8px 0 4px;">เนื้อหาไฟล์:</p>
            <pre class="code-block term">[Unit]
Description=Bunny Door Face Recognition
After=network.target mariadb.service

[Service]
User=pi
WorkingDirectory=/home/pi/bunny-door/python
Environment=PATH=/home/pi/bunny-env/bin
ExecStart=/home/pi/bunny-env/bin/python face_server.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target</pre>
            <pre class="code-block term" style="margin-top: 8px;">sudo systemctl daemon-reload
sudo systemctl enable bunny-door
sudo systemctl start bunny-door</pre>
        </div></li>
    </ol>
</div>

<div class="card">
    <h3 style="font-size: 15px; font-weight: 600; margin: 0 0 14px;">แก้ปัญหาที่พบบ่อย</h3>
    <div style="display: flex; flex-direction: column; gap: 10px;">
        <div class="setup-card">
            <div class="setup-icon info"><?= ico('camera', 18) ?></div>
            <div><strong style="font-size: 13px;">กล้องไม่ทำงาน</strong><p class="tiny muted" style="margin: 4px 0 0;">ตรวจสอบว่า <code class="mono">/dev/video*</code> มี ลอง <code class="mono">sudo chmod 666 /dev/video0</code></p></div>
        </div>
        <div class="setup-card">
            <div class="setup-icon warn"><?= ico('alert-tri', 18) ?></div>
            <div><strong style="font-size: 13px;">dlib compile ล้มเหลว</strong><p class="tiny muted" style="margin: 4px 0 0;">ตรวจสอบ swap (ต้อง 2GB+) ด้วย <code class="mono">free -h</code></p></div>
        </div>
        <div class="setup-card">
            <div class="setup-icon danger"><?= ico('wifi', 18) ?></div>
            <div><strong style="font-size: 13px;">เชื่อมต่อ ESP32 ไม่ได้</strong><p class="tiny muted" style="margin: 4px 0 0;">ตรวจสอบ WiFi และ IP ของ Pi ให้ตรงกับ SERVER_URL ใน Arduino code</p></div>
        </div>
        <div class="setup-card">
            <div class="setup-icon info"><?= ico('activity', 18) ?></div>
            <div><strong style="font-size: 13px;">Face Recognition ช้า</strong><p class="tiny muted" style="margin: 4px 0 0;">ใช้โมเดล HOG / ลดความละเอียดกล้อง / เพิ่ม process_every_x_frames</p></div>
        </div>
    </div>
</div>
</div>

<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.guide-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('panel-' + tabName).classList.remove('hidden');
    document.getElementById('tab-' + tabName).classList.add('active');
}
function copyArduinoCode() {
    const code = document.getElementById('arduinoCode').textContent;
    navigator.clipboard.writeText(code).then(() => showToast('คัดลอกโค้ด Arduino สำเร็จ!', 'success'))
    .catch(() => {
        const ta = document.createElement('textarea'); ta.value = code;
        document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
        showToast('คัดลอกโค้ด Arduino สำเร็จ!', 'success');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
