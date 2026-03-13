<?php $pageTitle = 'คู่มือระบบ - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<div class="mb-8">
    <h2 class="text-2xl font-bold">คู่มือระบบ</h2>
    <p class="text-gray-400 text-sm mt-1">เอกสารประกอบการติดตั้งและใช้งานระบบ Bunny Door</p>
</div>

<!-- Tab Navigation -->
<div class="flex gap-1 mb-6 bg-white/5 rounded-xl p-1 overflow-x-auto">
    <button onclick="switchTab('overview')" id="tab-overview" class="tab-btn flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium transition whitespace-nowrap bg-blue-600 text-white">
        <i class="fas fa-sitemap"></i> ภาพรวมระบบ
    </button>
    <button onclick="switchTab('wiring')" id="tab-wiring" class="tab-btn flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium transition whitespace-nowrap text-gray-400 hover:text-white">
        <i class="fas fa-plug"></i> การต่อวงจร
    </button>
    <button onclick="switchTab('arduino')" id="tab-arduino" class="tab-btn flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium transition whitespace-nowrap text-gray-400 hover:text-white">
        <i class="fas fa-microchip"></i> โค้ด Arduino
    </button>
    <button onclick="switchTab('raspi')" id="tab-raspi" class="tab-btn flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium transition whitespace-nowrap text-gray-400 hover:text-white">
        <i class="fab fa-raspberry-pi"></i> ติดตั้ง Raspberry Pi
    </button>
</div>

<!-- ============================================================ -->
<!-- Tab 1: System Overview -->
<!-- ============================================================ -->
<div id="panel-overview" class="tab-panel">

<!-- Architecture -->
<div class="glass rounded-2xl p-6 mb-6">
    <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
        <i class="fas fa-project-diagram text-blue-400"></i> สถาปัตยกรรมระบบ
    </h3>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <!-- ESP32 -->
        <div class="bg-gradient-to-br from-green-500/10 to-green-500/5 border border-green-500/20 rounded-xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-microchip text-green-400"></i>
                </div>
                <div>
                    <h4 class="font-bold text-green-400">ESP32</h4>
                    <p class="text-xs text-gray-400">ตัวควบคุมประตู</p>
                </div>
            </div>
            <ul class="text-sm text-gray-300 space-y-1">
                <li><i class="fas fa-check text-green-500 mr-2 text-xs"></i>เซ็นเซอร์ PIR 2 ตัว</li>
                <li><i class="fas fa-check text-green-500 mr-2 text-xs"></i>Relay กลอนแม่เหล็ก 12V</li>
                <li><i class="fas fa-check text-green-500 mr-2 text-xs"></i>LED + Buzzer แจ้งเตือน</li>
                <li><i class="fas fa-check text-green-500 mr-2 text-xs"></i>ปุ่ม Emergency Exit</li>
                <li><i class="fas fa-check text-green-500 mr-2 text-xs"></i>WiFi HTTP Server</li>
            </ul>
        </div>

        <!-- Raspberry Pi -->
        <div class="bg-gradient-to-br from-purple-500/10 to-purple-500/5 border border-purple-500/20 rounded-xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center">
                    <i class="fab fa-raspberry-pi text-purple-400"></i>
                </div>
                <div>
                    <h4 class="font-bold text-purple-400">Raspberry Pi 4</h4>
                    <p class="text-xs text-gray-400">ศูนย์กลางประมวลผล</p>
                </div>
            </div>
            <ul class="text-sm text-gray-300 space-y-1">
                <li><i class="fas fa-check text-purple-500 mr-2 text-xs"></i>กล้อง USB 2 ตัว</li>
                <li><i class="fas fa-check text-purple-500 mr-2 text-xs"></i>Face Recognition (HOG)</li>
                <li><i class="fas fa-check text-purple-500 mr-2 text-xs"></i>Python Flask API Server</li>
                <li><i class="fas fa-check text-purple-500 mr-2 text-xs"></i>Motion Detection (MOG2)</li>
                <li><i class="fas fa-check text-purple-500 mr-2 text-xs"></i>MariaDB Database</li>
            </ul>
        </div>

        <!-- Web Dashboard -->
        <div class="bg-gradient-to-br from-blue-500/10 to-blue-500/5 border border-blue-500/20 rounded-xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-display text-blue-400"></i>
                </div>
                <div>
                    <h4 class="font-bold text-blue-400">Web Dashboard</h4>
                    <p class="text-xs text-gray-400">หน้าจอควบคุม</p>
                </div>
            </div>
            <ul class="text-sm text-gray-300 space-y-1">
                <li><i class="fas fa-check text-blue-500 mr-2 text-xs"></i>Dashboard สถิติ</li>
                <li><i class="fas fa-check text-blue-500 mr-2 text-xs"></i>จัดการพนักงาน CRUD</li>
                <li><i class="fas fa-check text-blue-500 mr-2 text-xs"></i>ดูกล้องสด Live Stream</li>
                <li><i class="fas fa-check text-blue-500 mr-2 text-xs"></i>ประวัติเข้า-ออก</li>
                <li><i class="fas fa-check text-blue-500 mr-2 text-xs"></i>แจ้งเตือนความผิดปกติ</li>
            </ul>
        </div>
    </div>

    <!-- Flow -->
    <h4 class="font-bold mb-3 text-gray-200">ขั้นตอนการทำงาน</h4>
    <div class="flex flex-wrap items-center gap-2 text-sm">
        <div class="bg-green-500/10 border border-green-500/20 rounded-lg px-3 py-2 text-green-400">
            <i class="fas fa-walking mr-1"></i> คนเดินมา
        </div>
        <i class="fas fa-arrow-right text-gray-600"></i>
        <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-lg px-3 py-2 text-yellow-400">
            <i class="fas fa-satellite-dish mr-1"></i> PIR ตรวจจับ
        </div>
        <i class="fas fa-arrow-right text-gray-600"></i>
        <div class="bg-purple-500/10 border border-purple-500/20 rounded-lg px-3 py-2 text-purple-400">
            <i class="fas fa-camera mr-1"></i> กล้องถ่ายรูป
        </div>
        <i class="fas fa-arrow-right text-gray-600"></i>
        <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg px-3 py-2 text-blue-400">
            <i class="fas fa-face-smile mr-1"></i> จดจำใบหน้า
        </div>
        <i class="fas fa-arrow-right text-gray-600"></i>
        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-lg px-3 py-2 text-emerald-400">
            <i class="fas fa-door-open mr-1"></i> ปลดล็อกประตู
        </div>
    </div>
</div>

<!-- Component List -->
<div class="glass rounded-2xl p-6">
    <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
        <i class="fas fa-boxes-stacked text-orange-400"></i> รายการอุปกรณ์
    </h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-gray-400 border-b border-white/10">
                    <th class="text-left py-3 px-3">อุปกรณ์</th>
                    <th class="text-left py-3 px-3">รุ่น/สเปค</th>
                    <th class="text-left py-3 px-3">จำนวน</th>
                    <th class="text-left py-3 px-3">หน้าที่</th>
                </tr>
            </thead>
            <tbody class="text-gray-300">
                <tr class="border-b border-white/5"><td class="py-3 px-3 font-medium text-white">ESP32</td><td class="py-3 px-3">ESP32-WROOM-32</td><td class="py-3 px-3">1</td><td class="py-3 px-3">ควบคุมประตู, อ่าน PIR, สั่ง Relay</td></tr>
                <tr class="border-b border-white/5"><td class="py-3 px-3 font-medium text-white">Raspberry Pi</td><td class="py-3 px-3">Raspberry Pi 4 (4GB+)</td><td class="py-3 px-3">1</td><td class="py-3 px-3">ประมวลผลใบหน้า, Web Server</td></tr>
                <tr class="border-b border-white/5"><td class="py-3 px-3 font-medium text-white">PIR Sensor</td><td class="py-3 px-3">HC-SR501</td><td class="py-3 px-3">2</td><td class="py-3 px-3">ตรวจจับความเคลื่อนไหว (นอก/ใน)</td></tr>
                <tr class="border-b border-white/5"><td class="py-3 px-3 font-medium text-white">USB Camera</td><td class="py-3 px-3">USB Webcam 720p+</td><td class="py-3 px-3">2</td><td class="py-3 px-3">ถ่ายภาพใบหน้า (นอก/ใน)</td></tr>
                <tr class="border-b border-white/5"><td class="py-3 px-3 font-medium text-white">Relay Module</td><td class="py-3 px-3">5V 1-Channel</td><td class="py-3 px-3">1</td><td class="py-3 px-3">ควบคุมกลอนแม่เหล็กไฟฟ้า</td></tr>
                <tr class="border-b border-white/5"><td class="py-3 px-3 font-medium text-white">กลอนแม่เหล็ก</td><td class="py-3 px-3">Electromagnetic Lock 12V</td><td class="py-3 px-3">1</td><td class="py-3 px-3">ล็อก/ปลดล็อกประตู</td></tr>
                <tr class="border-b border-white/5"><td class="py-3 px-3 font-medium text-white">Buzzer</td><td class="py-3 px-3">Active Buzzer 5V</td><td class="py-3 px-3">1</td><td class="py-3 px-3">แจ้งเตือนเสียง</td></tr>
                <tr class="border-b border-white/5"><td class="py-3 px-3 font-medium text-white">LED</td><td class="py-3 px-3">5mm (เขียว + แดง)</td><td class="py-3 px-3">2</td><td class="py-3 px-3">แสดงสถานะอนุญาต/ไม่อนุญาต</td></tr>
                <tr class="border-b border-white/5"><td class="py-3 px-3 font-medium text-white">Emergency Button</td><td class="py-3 px-3">Push Button + Pull-up</td><td class="py-3 px-3">1</td><td class="py-3 px-3">ปลดล็อกฉุกเฉิน</td></tr>
                <tr><td class="py-3 px-3 font-medium text-white">Power Supply</td><td class="py-3 px-3">12V 2A + 5V 3A</td><td class="py-3 px-3">2</td><td class="py-3 px-3">จ่ายไฟกลอนแม่เหล็ก + ESP32/Pi</td></tr>
            </tbody>
        </table>
    </div>
</div>
</div>

<!-- ============================================================ -->
<!-- Tab 2: Wiring Guide -->
<!-- ============================================================ -->
<div id="panel-wiring" class="tab-panel hidden">

<!-- รายการอุปกรณ์ที่ต้องใช้ พร้อมรูปภาพ -->
<div class="glass rounded-2xl p-6 mb-6">
    <h3 class="text-lg font-bold mb-5 flex items-center gap-2">
        <i class="fas fa-shopping-cart text-orange-400"></i> อุปกรณ์ที่ต้องซื้อ (พร้อมรูปจริง)
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">

        <!-- 1. ESP32 -->
        <div class="bg-white/5 border border-white/10 rounded-xl overflow-hidden">
            <div class="bg-white p-3 flex items-center justify-center h-44">
                <img src="https://lastminuteengineers.com/wp-content/uploads/arduino/ESP32-Development-Board.jpg" alt="ESP32 WROOM-32" class="max-h-full object-contain" onerror="this.parentElement.innerHTML='<div class=\'text-gray-400 text-center\'><i class=\'fas fa-microchip text-4xl mb-2\'></i><br>ESP32</div>'">
            </div>
            <div class="p-4">
                <h4 class="font-bold text-green-400 text-sm">ESP32-WROOM-32 Dev Board</h4>
                <p class="text-xs text-gray-400 mt-1">บอร์ดไมโครคอนโทรลเลอร์ WiFi+Bluetooth</p>
                <div class="mt-2 space-y-1 text-xs text-gray-300">
                    <div><span class="text-gray-500">รุ่น:</span> ESP32-WROOM-32 (30 pin)</div>
                    <div><span class="text-gray-500">จำนวน:</span> <span class="text-white font-medium">1 ตัว</span></div>
                    <div><span class="text-gray-500">ราคาประมาณ:</span> 120-250 บาท</div>
                    <div><span class="text-gray-500">ซื้อได้ที่:</span> Shopee, Lazada, ร้าน IoT</div>
                </div>
            </div>
        </div>

        <!-- 2. PIR Sensor -->
        <div class="bg-white/5 border border-white/10 rounded-xl overflow-hidden">
            <div class="bg-white p-3 flex items-center justify-center h-44">
                <img src="https://components101.com/sites/default/files/component_pin/HC-SR501-PIR-Sensor-Module.jpg" alt="HC-SR501 PIR Sensor" class="max-h-full object-contain" onerror="this.parentElement.innerHTML='<div class=\'text-gray-400 text-center\'><i class=\'fas fa-satellite-dish text-4xl mb-2\'></i><br>PIR Sensor</div>'">
            </div>
            <div class="p-4">
                <h4 class="font-bold text-yellow-400 text-sm">PIR Motion Sensor HC-SR501</h4>
                <p class="text-xs text-gray-400 mt-1">เซ็นเซอร์ตรวจจับความเคลื่อนไหว (อินฟราเรด)</p>
                <div class="mt-2 space-y-1 text-xs text-gray-300">
                    <div><span class="text-gray-500">รุ่น:</span> HC-SR501 (ตัวเขียว โดมขาว)</div>
                    <div><span class="text-gray-500">จำนวน:</span> <span class="text-white font-medium">2 ตัว</span> (นอก+ใน)</div>
                    <div><span class="text-gray-500">ระยะตรวจจับ:</span> 3-7 เมตร, มุม 120&deg;</div>
                    <div><span class="text-gray-500">ไฟเลี้ยง:</span> 4.5V - 12V (ใช้ 5V)</div>
                    <div><span class="text-gray-500">ราคาประมาณ:</span> 25-50 บาท/ตัว</div>
                </div>
            </div>
        </div>

        <!-- 3. Relay Module -->
        <div class="bg-white/5 border border-white/10 rounded-xl overflow-hidden">
            <div class="bg-white p-3 flex items-center justify-center h-44">
                <img src="https://components101.com/sites/default/files/component_pin/5V-Single-Channel-Relay-Module.jpg" alt="5V Relay Module" class="max-h-full object-contain" onerror="this.parentElement.innerHTML='<div class=\'text-gray-400 text-center\'><i class=\'fas fa-bolt text-4xl mb-2\'></i><br>Relay Module</div>'">
            </div>
            <div class="p-4">
                <h4 class="font-bold text-red-400 text-sm">Relay Module 5V 1-Channel</h4>
                <p class="text-xs text-gray-400 mt-1">รีเลย์สำหรับตัดต่อไฟ 12V กลอนแม่เหล็ก</p>
                <div class="mt-2 space-y-1 text-xs text-gray-300">
                    <div><span class="text-gray-500">รุ่น:</span> 5V 1-Channel Relay Module (มี optocoupler)</div>
                    <div><span class="text-gray-500">จำนวน:</span> <span class="text-white font-medium">1 ตัว</span></div>
                    <div><span class="text-gray-500">รับโหลดสูงสุด:</span> 10A 250VAC / 10A 30VDC</div>
                    <div><span class="text-gray-500">ราคาประมาณ:</span> 20-45 บาท</div>
                </div>
            </div>
        </div>

        <!-- 4. Electromagnetic Lock -->
        <div class="bg-white/5 border border-white/10 rounded-xl overflow-hidden">
            <div class="bg-white p-3 flex items-center justify-center h-44">
                <img src="https://arduinogetstarted.com/images/tutorial/solenoid-lock.jpg" alt="Electromagnetic Lock 12V" class="max-h-full object-contain" onerror="this.parentElement.innerHTML='<div class=\'text-gray-400 text-center\'><i class=\'fas fa-lock text-4xl mb-2\'></i><br>EM Lock</div>'">
            </div>
            <div class="p-4">
                <h4 class="font-bold text-blue-400 text-sm">กลอนแม่เหล็กไฟฟ้า 12V (Solenoid Lock)</h4>
                <p class="text-xs text-gray-400 mt-1">กลอนประตูล็อก/ปลดล็อกด้วยไฟฟ้า</p>
                <div class="mt-2 space-y-1 text-xs text-gray-300">
                    <div><span class="text-gray-500">รุ่น:</span> Electric Solenoid Lock 12V DC</div>
                    <div><span class="text-gray-500">จำนวน:</span> <span class="text-white font-medium">1 ตัว</span></div>
                    <div><span class="text-gray-500">ไฟเลี้ยง:</span> 12V DC, กินไฟ ~500mA</div>
                    <div><span class="text-gray-500">ราคาประมาณ:</span> 80-200 บาท</div>
                </div>
            </div>
        </div>

        <!-- 5. USB Camera -->
        <div class="bg-white/5 border border-white/10 rounded-xl overflow-hidden">
            <div class="bg-white p-3 flex items-center justify-center h-44">
                <img src="https://m.media-amazon.com/images/I/51vwWjGTp+L._AC_SL1000_.jpg" alt="USB Webcam" class="max-h-full object-contain" onerror="this.parentElement.innerHTML='<div class=\'text-gray-400 text-center\'><i class=\'fas fa-camera text-4xl mb-2\'></i><br>USB Camera</div>'">
            </div>
            <div class="p-4">
                <h4 class="font-bold text-purple-400 text-sm">USB Webcam 720p+</h4>
                <p class="text-xs text-gray-400 mt-1">กล้องถ่ายภาพใบหน้า ต่อ Raspberry Pi</p>
                <div class="mt-2 space-y-1 text-xs text-gray-300">
                    <div><span class="text-gray-500">รุ่น:</span> USB Webcam 720p ขึ้นไป (แนะนำ 1080p)</div>
                    <div><span class="text-gray-500">จำนวน:</span> <span class="text-white font-medium">2 ตัว</span> (นอก+ใน)</div>
                    <div><span class="text-gray-500">คุณสมบัติ:</span> UVC compatible, ไม่ต้องลง driver</div>
                    <div><span class="text-gray-500">ราคาประมาณ:</span> 150-500 บาท/ตัว</div>
                </div>
            </div>
        </div>

        <!-- 6. Raspberry Pi 4 -->
        <div class="bg-white/5 border border-white/10 rounded-xl overflow-hidden">
            <div class="bg-white p-3 flex items-center justify-center h-44">
                <img src="https://lastminuteengineers.com/wp-content/uploads/iot/Raspberry-Pi-4-Model-B.jpg" alt="Raspberry Pi 4" class="max-h-full object-contain" onerror="this.parentElement.innerHTML='<div class=\'text-gray-400 text-center\'><i class=\'fab fa-raspberry-pi text-4xl mb-2\'></i><br>Raspberry Pi 4</div>'">
            </div>
            <div class="p-4">
                <h4 class="font-bold text-pink-400 text-sm">Raspberry Pi 4 Model B (4GB+)</h4>
                <p class="text-xs text-gray-400 mt-1">ศูนย์กลางประมวลผล Face Recognition</p>
                <div class="mt-2 space-y-1 text-xs text-gray-300">
                    <div><span class="text-gray-500">รุ่น:</span> Raspberry Pi 4 Model B (4GB RAM ขึ้นไป)</div>
                    <div><span class="text-gray-500">จำนวน:</span> <span class="text-white font-medium">1 ตัว</span></div>
                    <div><span class="text-gray-500">อุปกรณ์เสริม:</span> SD Card 32GB+, Power Supply 5V 3A USB-C</div>
                    <div><span class="text-gray-500">ราคาประมาณ:</span> 1,800-3,500 บาท</div>
                </div>
            </div>
        </div>

    </div>

    <!-- อุปกรณ์เสริมเล็กๆ -->
    <div class="mt-5 bg-white/5 rounded-xl p-4">
        <h4 class="font-medium text-sm mb-3 text-gray-200"><i class="fas fa-puzzle-piece text-gray-400 mr-2"></i>อุปกรณ์เสริมอื่นๆ</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs text-gray-300">
            <div class="bg-white/5 rounded-lg p-3 text-center">
                <i class="fas fa-lightbulb text-green-400 text-lg mb-1"></i>
                <div class="font-medium text-white">LED 5mm</div>
                <div class="text-gray-500">เขียว 1 + แดง 1</div>
                <div class="text-gray-500">~2 บาท/ตัว</div>
            </div>
            <div class="bg-white/5 rounded-lg p-3 text-center">
                <i class="fas fa-volume-up text-purple-400 text-lg mb-1"></i>
                <div class="font-medium text-white">Active Buzzer 5V</div>
                <div class="text-gray-500">1 ตัว</div>
                <div class="text-gray-500">~10 บาท</div>
            </div>
            <div class="bg-white/5 rounded-lg p-3 text-center">
                <i class="fas fa-circle-dot text-gray-400 text-lg mb-1"></i>
                <div class="font-medium text-white">Push Button</div>
                <div class="text-gray-500">Emergency 1 ตัว</div>
                <div class="text-gray-500">~5 บาท</div>
            </div>
            <div class="bg-white/5 rounded-lg p-3 text-center">
                <i class="fas fa-plug text-yellow-400 text-lg mb-1"></i>
                <div class="font-medium text-white">อุปกรณ์อื่น</div>
                <div class="text-gray-500">สายไฟ, Breadboard</div>
                <div class="text-gray-500">R 220&Omega; x2, Adapter 12V</div>
            </div>
        </div>
    </div>
</div>

<!-- ESP32 Pin Mapping -->
<div class="glass rounded-2xl p-6 mb-6">
    <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
        <i class="fas fa-microchip text-green-400"></i> ESP32 GPIO Pin Mapping
    </h3>
    <p class="text-sm text-gray-400 mb-4">ตารางแสดง GPIO ที่ใช้ต่ออุปกรณ์แต่ละตัว (อ้างอิงจาก ESP32-WROOM-32 Dev Board 30 pin)</p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-gray-400 border-b border-white/10">
                    <th class="text-left py-3 px-3">GPIO Pin</th>
                    <th class="text-left py-3 px-3">อุปกรณ์</th>
                    <th class="text-left py-3 px-3">ทิศทาง</th>
                    <th class="text-left py-3 px-3">หมายเหตุ</th>
                </tr>
            </thead>
            <tbody class="text-gray-300">
                <tr class="border-b border-white/5">
                    <td class="py-3 px-3"><span class="bg-yellow-500/20 text-yellow-400 px-2 py-0.5 rounded font-mono text-xs">GPIO 27</span></td>
                    <td class="py-3 px-3 font-medium text-white">PIR Sensor ด้านนอก</td>
                    <td class="py-3 px-3"><span class="bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded text-xs">INPUT</span></td>
                    <td class="py-3 px-3">ต่อขา OUT ของ PIR ตัวที่ 1 (หน้าประตู)</td>
                </tr>
                <tr class="border-b border-white/5">
                    <td class="py-3 px-3"><span class="bg-yellow-500/20 text-yellow-400 px-2 py-0.5 rounded font-mono text-xs">GPIO 26</span></td>
                    <td class="py-3 px-3 font-medium text-white">PIR Sensor ด้านใน</td>
                    <td class="py-3 px-3"><span class="bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded text-xs">INPUT</span></td>
                    <td class="py-3 px-3">ต่อขา OUT ของ PIR ตัวที่ 2 (ในห้อง)</td>
                </tr>
                <tr class="border-b border-white/5">
                    <td class="py-3 px-3"><span class="bg-red-500/20 text-red-400 px-2 py-0.5 rounded font-mono text-xs">GPIO 25</span></td>
                    <td class="py-3 px-3 font-medium text-white">Relay Module (IN)</td>
                    <td class="py-3 px-3"><span class="bg-orange-500/20 text-orange-400 px-2 py-0.5 rounded text-xs">OUTPUT</span></td>
                    <td class="py-3 px-3">HIGH = ปลดล็อกประตู / LOW = ล็อก</td>
                </tr>
                <tr class="border-b border-white/5">
                    <td class="py-3 px-3"><span class="bg-purple-500/20 text-purple-400 px-2 py-0.5 rounded font-mono text-xs">GPIO 33</span></td>
                    <td class="py-3 px-3 font-medium text-white">Buzzer</td>
                    <td class="py-3 px-3"><span class="bg-orange-500/20 text-orange-400 px-2 py-0.5 rounded text-xs">OUTPUT</span></td>
                    <td class="py-3 px-3">เสียงแจ้งเตือน (อนุญาต / ปฏิเสธ)</td>
                </tr>
                <tr class="border-b border-white/5">
                    <td class="py-3 px-3"><span class="bg-green-500/20 text-green-400 px-2 py-0.5 rounded font-mono text-xs">GPIO 32</span></td>
                    <td class="py-3 px-3 font-medium text-white">LED สีเขียว</td>
                    <td class="py-3 px-3"><span class="bg-orange-500/20 text-orange-400 px-2 py-0.5 rounded text-xs">OUTPUT</span></td>
                    <td class="py-3 px-3">ติดเมื่ออนุญาตเข้า (ผ่าน R 220&Omega;)</td>
                </tr>
                <tr class="border-b border-white/5">
                    <td class="py-3 px-3"><span class="bg-red-500/20 text-red-400 px-2 py-0.5 rounded font-mono text-xs">GPIO 14</span></td>
                    <td class="py-3 px-3 font-medium text-white">LED สีแดง</td>
                    <td class="py-3 px-3"><span class="bg-orange-500/20 text-orange-400 px-2 py-0.5 rounded text-xs">OUTPUT</span></td>
                    <td class="py-3 px-3">ติดเมื่อปฏิเสธ / ล็อก (ผ่าน R 220&Omega;)</td>
                </tr>
                <tr>
                    <td class="py-3 px-3"><span class="bg-gray-500/20 text-gray-400 px-2 py-0.5 rounded font-mono text-xs">GPIO 13</span></td>
                    <td class="py-3 px-3 font-medium text-white">Emergency Button</td>
                    <td class="py-3 px-3"><span class="bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded text-xs">INPUT_PULLUP</span></td>
                    <td class="py-3 px-3">กดค้าง = ปลดล็อกฉุกเฉินทันที</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== PIR Sensor (รายละเอียดการต่อ) ===== -->
<div class="glass rounded-2xl p-6 mb-6">
    <h3 class="text-lg font-bold mb-2 flex items-center gap-2">
        <i class="fas fa-satellite-dish text-yellow-400"></i> การต่อเซ็นเซอร์ตรวจจับความเคลื่อนไหว (PIR HC-SR501)
    </h3>
    <p class="text-sm text-gray-400 mb-5">ใช้ 2 ตัว: ตัวที่ 1 ติดด้านนอกประตู, ตัวที่ 2 ติดด้านในห้อง</p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- รูป PIR + Pinout -->
        <div>
            <div class="bg-white rounded-xl p-4 mb-4 flex items-center justify-center">
                <img src="https://components101.com/sites/default/files/component_pin/HC-SR501-PIR-Sensor-Pinout.jpg" alt="HC-SR501 Pinout" class="max-h-56 object-contain" onerror="this.parentElement.innerHTML='<div class=\'text-gray-600 text-center py-8\'><i class=\'fas fa-satellite-dish text-5xl mb-3\'></i><br>HC-SR501 Pinout<br><small>VCC | OUT | GND</small></div>'">
            </div>
            <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-xl p-4 text-sm">
                <h4 class="font-bold text-yellow-400 mb-2"><i class="fas fa-star mr-1"></i> จุดสำคัญของ HC-SR501</h4>
                <ul class="space-y-1 text-gray-300 text-xs">
                    <li><i class="fas fa-check text-yellow-500 mr-1"></i> <strong>3 ขา:</strong> VCC (ซ้าย) / OUT (กลาง) / GND (ขวา) — ดูจากด้านหลัง</li>
                    <li><i class="fas fa-check text-yellow-500 mr-1"></i> <strong>Potentiometer 2 ตัว:</strong> ซ้ายปรับ Sensitivity, ขวาปรับ Time Delay</li>
                    <li><i class="fas fa-check text-yellow-500 mr-1"></i> <strong>Jumper:</strong> H = Repeat Trigger (แนะนำ), L = Single Trigger</li>
                    <li><i class="fas fa-check text-yellow-500 mr-1"></i> <strong>ระยะตรวจจับ:</strong> 3-7 เมตร ปรับได้ด้วย Sensitivity</li>
                    <li><i class="fas fa-check text-yellow-500 mr-1"></i> <strong>มุมตรวจจับ:</strong> ~120&deg; กรวยรูปกรวย</li>
                    <li><i class="fas fa-check text-yellow-500 mr-1"></i> <strong>Output:</strong> 3.3V เมื่อตรวจเจอ, 0V เมื่อไม่เจอ</li>
                </ul>
            </div>
        </div>

        <!-- วิธีต่อสาย PIR -->
        <div>
            <h4 class="font-bold text-sm text-white mb-3"><i class="fas fa-plug text-blue-400 mr-2"></i>วิธีต่อสาย PIR &rarr; ESP32</h4>

            <!-- PIR ตัวที่ 1 (นอก) -->
            <div class="bg-green-500/10 border border-green-500/20 rounded-xl p-4 mb-3">
                <h5 class="font-medium text-green-400 text-sm mb-2"><i class="fas fa-door-closed mr-1"></i> PIR ตัวที่ 1 — ด้านนอกประตู</h5>
                <div class="space-y-2 text-sm text-gray-300">
                    <div class="flex items-center gap-2 p-2 bg-white/5 rounded-lg">
                        <span class="bg-red-600 text-white px-2 py-0.5 rounded font-mono text-xs w-14 text-center">VCC</span>
                        <i class="fas fa-long-arrow-alt-right text-gray-500"></i>
                        <span class="font-mono text-red-300">5V</span>
                        <span class="text-gray-500 text-xs">(ขา VIN ของ ESP32)</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-white/5 rounded-lg">
                        <span class="bg-yellow-600 text-white px-2 py-0.5 rounded font-mono text-xs w-14 text-center">OUT</span>
                        <i class="fas fa-long-arrow-alt-right text-gray-500"></i>
                        <span class="font-mono text-yellow-300">GPIO 27</span>
                        <span class="text-gray-500 text-xs">(สายสัญญาณ)</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-white/5 rounded-lg">
                        <span class="bg-gray-700 text-white px-2 py-0.5 rounded font-mono text-xs w-14 text-center">GND</span>
                        <i class="fas fa-long-arrow-alt-right text-gray-500"></i>
                        <span class="font-mono text-gray-300">GND</span>
                        <span class="text-gray-500 text-xs">(กราวด์ร่วม ESP32)</span>
                    </div>
                </div>
            </div>

            <!-- PIR ตัวที่ 2 (ใน) -->
            <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 mb-3">
                <h5 class="font-medium text-blue-400 text-sm mb-2"><i class="fas fa-door-open mr-1"></i> PIR ตัวที่ 2 — ด้านในห้อง</h5>
                <div class="space-y-2 text-sm text-gray-300">
                    <div class="flex items-center gap-2 p-2 bg-white/5 rounded-lg">
                        <span class="bg-red-600 text-white px-2 py-0.5 rounded font-mono text-xs w-14 text-center">VCC</span>
                        <i class="fas fa-long-arrow-alt-right text-gray-500"></i>
                        <span class="font-mono text-red-300">5V</span>
                        <span class="text-gray-500 text-xs">(ขา VIN ของ ESP32)</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-white/5 rounded-lg">
                        <span class="bg-yellow-600 text-white px-2 py-0.5 rounded font-mono text-xs w-14 text-center">OUT</span>
                        <i class="fas fa-long-arrow-alt-right text-gray-500"></i>
                        <span class="font-mono text-yellow-300">GPIO 26</span>
                        <span class="text-gray-500 text-xs">(สายสัญญาณ)</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-white/5 rounded-lg">
                        <span class="bg-gray-700 text-white px-2 py-0.5 rounded font-mono text-xs w-14 text-center">GND</span>
                        <i class="fas fa-long-arrow-alt-right text-gray-500"></i>
                        <span class="font-mono text-gray-300">GND</span>
                        <span class="text-gray-500 text-xs">(กราวด์ร่วม ESP32)</span>
                    </div>
                </div>
            </div>

            <div class="bg-orange-500/10 border border-orange-500/20 rounded-xl p-3 text-xs text-orange-300">
                <i class="fas fa-lightbulb text-orange-400 mr-1"></i>
                <strong>Tip:</strong> ติด PIR ในตำแหน่งสูงจากพื้น ~1.5-2 เมตร หันโดมลงเล็กน้อยเพื่อจับการเดินของคนได้ดี ระวังอย่าหันไปทางหน้าต่างที่มีแสงแดดส่อง (อาจ trigger ผิดพลาด)
            </div>
        </div>
    </div>
</div>

<!-- ===== Relay + กลอนแม่เหล็ก ===== -->
<div class="glass rounded-2xl p-6 mb-6">
    <h3 class="text-lg font-bold mb-2 flex items-center gap-2">
        <i class="fas fa-bolt text-red-400"></i> การต่อ Relay Module + กลอนแม่เหล็กไฟฟ้า 12V
    </h3>
    <p class="text-sm text-gray-400 mb-5">Relay ทำหน้าที่เป็นสวิตช์ตัดต่อไฟ 12V ให้กลอนแม่เหล็ก</p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- รูป Relay -->
        <div>
            <div class="bg-white rounded-xl p-4 mb-4 flex items-center justify-center">
                <img src="https://components101.com/sites/default/files/component_pin/Relay-Module-Pinout.jpg" alt="Relay Module Pinout" class="max-h-56 object-contain" onerror="this.parentElement.innerHTML='<div class=\'text-gray-600 text-center py-8\'><i class=\'fas fa-bolt text-5xl mb-3\'></i><br>Relay Module Pinout<br><small>VCC | IN | GND | COM | NO | NC</small></div>'">
            </div>
            <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-sm">
                <h4 class="font-bold text-red-400 mb-2"><i class="fas fa-exclamation-triangle mr-1"></i> สำคัญ: ระวังไฟ 12V</h4>
                <ul class="space-y-1 text-gray-300 text-xs">
                    <li><i class="fas fa-check text-red-500 mr-1"></i> Relay มี 2 ด้าน: ด้านควบคุม (VCC/IN/GND) ต่อ ESP32</li>
                    <li><i class="fas fa-check text-red-500 mr-1"></i> ด้านสวิตช์ (COM/NO/NC) ต่อวงจรไฟ 12V + กลอน</li>
                    <li><i class="fas fa-check text-red-500 mr-1"></i> <strong>NO</strong> = Normally Open, <strong>NC</strong> = Normally Closed</li>
                    <li><i class="fas fa-check text-red-500 mr-1"></i> ต่อแบบ NO: ไม่จ่ายไฟจนกว่า ESP32 สั่ง HIGH (ปลอดภัยกว่า)</li>
                    <li><i class="fas fa-check text-red-500 mr-1"></i> ใช้ Adapter 12V 2A แยกต่างหากสำหรับกลอนแม่เหล็ก</li>
                </ul>
            </div>
        </div>

        <!-- วิธีต่อสาย Relay -->
        <div>
            <h4 class="font-bold text-sm text-white mb-3"><i class="fas fa-plug text-blue-400 mr-2"></i>วิธีต่อสาย</h4>

            <!-- ด้านควบคุม -->
            <div class="bg-green-500/10 border border-green-500/20 rounded-xl p-4 mb-3">
                <h5 class="font-medium text-green-400 text-sm mb-2">ด้าน Low Voltage (ESP32 &rarr; Relay)</h5>
                <div class="space-y-2 text-sm text-gray-300">
                    <div class="flex items-center gap-2 p-2 bg-white/5 rounded-lg">
                        <span class="bg-red-600 text-white px-2 py-0.5 rounded font-mono text-xs w-14 text-center">VCC</span>
                        <i class="fas fa-long-arrow-alt-right text-gray-500"></i>
                        <span class="font-mono text-red-300">5V (ESP32)</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-white/5 rounded-lg">
                        <span class="bg-gray-700 text-white px-2 py-0.5 rounded font-mono text-xs w-14 text-center">GND</span>
                        <i class="fas fa-long-arrow-alt-right text-gray-500"></i>
                        <span class="font-mono text-gray-300">GND (ESP32)</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-white/5 rounded-lg">
                        <span class="bg-green-600 text-white px-2 py-0.5 rounded font-mono text-xs w-14 text-center">IN</span>
                        <i class="fas fa-long-arrow-alt-right text-gray-500"></i>
                        <span class="font-mono text-green-300">GPIO 25</span>
                    </div>
                </div>
            </div>

            <!-- ด้าน High Voltage -->
            <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-3">
                <h5 class="font-medium text-red-400 text-sm mb-2">ด้าน High Voltage (Relay &rarr; กลอน 12V)</h5>
                <div class="space-y-2 text-sm text-gray-300">
                    <div class="flex items-center gap-2 p-2 bg-white/5 rounded-lg">
                        <span class="bg-orange-600 text-white px-2 py-0.5 rounded font-mono text-xs w-14 text-center">COM</span>
                        <i class="fas fa-long-arrow-alt-right text-gray-500"></i>
                        <span>Adapter 12V <strong class="text-orange-300">(+)</strong></span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-white/5 rounded-lg">
                        <span class="bg-blue-600 text-white px-2 py-0.5 rounded font-mono text-xs w-14 text-center">NO</span>
                        <i class="fas fa-long-arrow-alt-right text-gray-500"></i>
                        <span>กลอนแม่เหล็ก <strong class="text-blue-300">(+)</strong></span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-white/5 rounded-lg">
                        <span class="text-gray-400 text-xs w-14 text-center">—</span>
                        <span class="text-gray-400 text-xs"></span>
                        <span>กลอนแม่เหล็ก <strong class="text-gray-300">(-)</strong> ต่อกลับ Adapter 12V <strong class="text-gray-300">(-)</strong></span>
                    </div>
                </div>
            </div>

            <!-- Diagram ง่าย -->
            <div class="bg-black/40 rounded-xl p-4 font-mono text-xs text-gray-300 leading-relaxed">
                <div class="text-gray-500 mb-1">// แผนภาพวงจร Relay</div>
                <div class="text-green-400">ESP32 GPIO 25 ──&gt; [Relay IN]</div>
                <div class="text-red-400">ESP32 5V ────────&gt; [Relay VCC]</div>
                <div class="text-gray-400">ESP32 GND ───────&gt; [Relay GND]</div>
                <div class="text-yellow-400 mt-2">Adapter 12V (+) ─&gt; [Relay COM]</div>
                <div class="text-blue-400">[Relay NO] ──────&gt; กลอน (+)</div>
                <div class="text-gray-400">กลอน (-) ────────&gt; Adapter 12V (-)</div>
            </div>
        </div>
    </div>
</div>

<!-- ===== LED + Buzzer + Emergency Button ===== -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="glass rounded-2xl p-6">
        <h3 class="font-bold mb-4 flex items-center gap-2">
            <i class="fas fa-lightbulb text-green-400"></i> การต่อ LED + Buzzer
        </h3>
        <div class="space-y-3 text-sm text-gray-300">
            <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-3">
                <div class="font-medium text-green-400 text-xs mb-2">LED สีเขียว (อนุญาตเข้า)</div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs">GPIO 32</span>
                    <span class="text-gray-500">&rarr;</span>
                    <span class="text-xs">R 220&Omega;</span>
                    <span class="text-gray-500">&rarr;</span>
                    <span class="text-xs">LED (+) ขายาว</span>
                    <span class="text-gray-500">&rarr;</span>
                    <span class="text-xs">LED (-) ขาสั้น &rarr; GND</span>
                </div>
            </div>
            <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-3">
                <div class="font-medium text-red-400 text-xs mb-2">LED สีแดง (ปฏิเสธ/ล็อก)</div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs">GPIO 14</span>
                    <span class="text-gray-500">&rarr;</span>
                    <span class="text-xs">R 220&Omega;</span>
                    <span class="text-gray-500">&rarr;</span>
                    <span class="text-xs">LED (+) ขายาว</span>
                    <span class="text-gray-500">&rarr;</span>
                    <span class="text-xs">LED (-) ขาสั้น &rarr; GND</span>
                </div>
            </div>
            <div class="bg-purple-500/10 border border-purple-500/20 rounded-lg p-3">
                <div class="font-medium text-purple-400 text-xs mb-2">Active Buzzer (เสียงแจ้งเตือน)</div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs">GPIO 33</span>
                    <span class="text-gray-500">&rarr;</span>
                    <span class="text-xs">Buzzer (+)</span>
                    <span class="text-gray-500">|</span>
                    <span class="text-xs">Buzzer (-) &rarr; GND</span>
                </div>
            </div>
            <p class="text-xs text-gray-500"><i class="fas fa-info-circle mr-1"></i> Active Buzzer มีขั้ว (+/-) ขายาว = (+) ต่อ GPIO, ขาสั้น = (-) ต่อ GND</p>
        </div>
    </div>

    <div class="glass rounded-2xl p-6">
        <h3 class="font-bold mb-4 flex items-center gap-2">
            <i class="fas fa-hand-pointer text-gray-400"></i> การต่อ Emergency Button
        </h3>
        <div class="space-y-3 text-sm text-gray-300">
            <div class="bg-white/5 rounded-lg p-4">
                <div class="font-medium text-white text-sm mb-3">Push Button แบบกดติดปล่อยดับ</div>
                <div class="space-y-2">
                    <div class="flex items-center gap-2 p-2 bg-white/5 rounded-lg">
                        <span class="bg-blue-600 text-white px-2 py-0.5 rounded font-mono text-xs w-14 text-center">ขา 1</span>
                        <i class="fas fa-long-arrow-alt-right text-gray-500"></i>
                        <span class="font-mono">GPIO 13</span>
                        <span class="text-gray-500 text-xs">(INPUT_PULLUP)</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-white/5 rounded-lg">
                        <span class="bg-gray-700 text-white px-2 py-0.5 rounded font-mono text-xs w-14 text-center">ขา 2</span>
                        <i class="fas fa-long-arrow-alt-right text-gray-500"></i>
                        <span class="font-mono">GND</span>
                    </div>
                </div>
            </div>
            <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-lg p-3 text-xs">
                <p class="text-yellow-400 mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> ข้อควรระวัง</p>
                <ul class="space-y-1 text-gray-300">
                    <li>&bull; ใช้ Internal Pull-up ของ ESP32 — ไม่ต้องต่อ Resistor เพิ่ม</li>
                    <li>&bull; เมื่อกดปุ่ม ประตูจะเปิดทันทีโดยไม่ต้องสแกนใบหน้า</li>
                    <li>&bull; ควรติดปุ่มไว้ด้านในห้องเท่านั้น เพื่อความปลอดภัย</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ===== แผนภาพรวมทั้งระบบ ===== -->
<div class="glass rounded-2xl p-6">
    <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
        <i class="fas fa-project-diagram text-cyan-400"></i> แผนภาพรวมการต่อสายทั้งระบบ
    </h3>
    <div class="bg-black/50 rounded-xl p-5 font-mono text-xs leading-loose overflow-x-auto">
<pre class="text-gray-300">
                    ┌─────────────────────────────────────┐
                    │          <span class="text-green-400 font-bold">ESP32-WROOM-32</span>             │
                    │                                     │
  <span class="text-yellow-400">PIR นอก</span> (OUT) ──── │  <span class="text-yellow-400">GPIO 27</span>                       5V │ ──── <span class="text-red-400">PIR VCC (x2)</span>
  <span class="text-yellow-400">PIR ใน</span>  (OUT) ──── │  <span class="text-yellow-400">GPIO 26</span>                      GND │ ──── <span class="text-gray-400">GND ร่วมทั้งหมด</span>
                    │                                     │
 <span class="text-red-400">Relay</span> (IN) ──────── │  <span class="text-red-400">GPIO 25</span>                          │
 <span class="text-purple-400">Buzzer</span> (+) ─────── │  <span class="text-purple-400">GPIO 33</span>                          │
 <span class="text-green-400">LED เขียว</span> (+) ──── │  <span class="text-green-400">GPIO 32</span>  (ผ่าน R220&Omega;)          │
 <span class="text-red-300">LED แดง</span> (+) ────── │  <span class="text-red-300">GPIO 14</span>  (ผ่าน R220&Omega;)          │
 <span class="text-gray-400">ปุ่ม Emergency</span> ──── │  <span class="text-gray-400">GPIO 13</span>  (PULLUP)              │
                    └─────────────────────────────────────┘

  ┌──────────────┐        ┌─────────────────┐        ┌─────────────┐
  │  <span class="text-orange-400">Adapter 12V</span>  │        │   <span class="text-red-400">Relay Module</span>   │        │  <span class="text-blue-400">กลอนแม่เหล็ก</span> │
  │  (+)────────────────── │ COM         NO │ ─────── │ (+)         │
  │  (-)──────────────┬─── │ GND            │        │ (-)─────┐   │
  └──────────────┘    │    └─────────────────┘        └─────────┘   │
                      └─────────────────────────────────────────────┘

  ┌───────────────────────────────────────────────────────┐
  │                  <span class="text-purple-400 font-bold">Raspberry Pi 4</span>                       │
  │   USB Camera x2 (นอก+ใน) ── ต่อ USB Port            │
  │   Face Recognition Server ── Python Flask :5000      │
  │   MariaDB Database ── เก็บข้อมูลพนักงาน/ประวัติ       │
  └───────────────────────────────────────────────────────┘
</pre>
    </div>
    <p class="text-xs text-gray-500 mt-3"><i class="fas fa-info-circle mr-1"></i> ESP32 และ Raspberry Pi ต้องเชื่อมต่อ WiFi เดียวกัน เพื่อสื่อสาร HTTP กัน</p>
</div>
</div>

<!-- ============================================================ -->
<!-- Tab 3: Arduino Code -->
<!-- ============================================================ -->
<div id="panel-arduino" class="tab-panel hidden">

<div class="glass rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold flex items-center gap-2">
            <i class="fas fa-code text-blue-400"></i> ESP32 Arduino Source Code
        </h3>
        <button onclick="copyArduinoCode()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition flex items-center gap-2">
            <i class="fas fa-copy"></i> คัดลอกโค้ด
        </button>
    </div>
    <div class="bg-black/40 rounded-xl p-4 overflow-x-auto max-h-[70vh] overflow-y-auto">
        <pre id="arduinoCode" class="text-sm font-mono text-gray-300 whitespace-pre leading-relaxed"><?php
// Read the actual Arduino source file
$arduinoPath = __DIR__ . '/../esp32/door_controller/door_controller.ino';
if (file_exists($arduinoPath)) {
    echo htmlspecialchars(file_get_contents($arduinoPath));
} else {
    echo '// ไม่พบไฟล์ door_controller.ino';
}
?></pre>
    </div>
</div>

<!-- Setup Instructions -->
<div class="glass rounded-2xl p-6 mb-6">
    <h3 class="font-bold mb-4 flex items-center gap-2">
        <i class="fas fa-cog text-purple-400"></i> การตั้งค่าก่อนอัปโหลด
    </h3>
    <div class="space-y-3 text-sm text-gray-300">
        <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-lg p-4">
            <p class="text-yellow-400 font-medium mb-2"><i class="fas fa-exclamation-triangle mr-1"></i> ต้องแก้ไขค่าเหล่านี้ก่อนอัปโหลด:</p>
            <ul class="space-y-1">
                <li><code class="bg-white/10 px-2 py-0.5 rounded">WIFI_SSID</code> - ชื่อ WiFi ที่ต้องการเชื่อมต่อ</li>
                <li><code class="bg-white/10 px-2 py-0.5 rounded">WIFI_PASSWORD</code> - รหัสผ่าน WiFi</li>
                <li><code class="bg-white/10 px-2 py-0.5 rounded">SERVER_URL</code> - IP ของ Raspberry Pi (เช่น http://192.168.1.50:5000)</li>
            </ul>
        </div>
    </div>
</div>

<div class="glass rounded-2xl p-6">
    <h3 class="font-bold mb-4 flex items-center gap-2">
        <i class="fas fa-download text-green-400"></i> วิธีอัปโหลดโค้ด
    </h3>
    <div class="space-y-3">
        <div class="flex gap-3 items-start">
            <span class="w-7 h-7 bg-blue-500/20 rounded-lg flex items-center justify-center text-blue-400 text-sm font-bold flex-shrink-0">1</span>
            <div>
                <p class="text-sm text-white font-medium">ติดตั้ง Arduino IDE</p>
                <p class="text-xs text-gray-400">ดาวน์โหลดจาก arduino.cc/en/software</p>
            </div>
        </div>
        <div class="flex gap-3 items-start">
            <span class="w-7 h-7 bg-blue-500/20 rounded-lg flex items-center justify-center text-blue-400 text-sm font-bold flex-shrink-0">2</span>
            <div>
                <p class="text-sm text-white font-medium">เพิ่ม ESP32 Board</p>
                <p class="text-xs text-gray-400">File > Preferences > Additional Board URLs: เพิ่ม ESP32 URL แล้วติดตั้งผ่าน Board Manager</p>
            </div>
        </div>
        <div class="flex gap-3 items-start">
            <span class="w-7 h-7 bg-blue-500/20 rounded-lg flex items-center justify-center text-blue-400 text-sm font-bold flex-shrink-0">3</span>
            <div>
                <p class="text-sm text-white font-medium">ติดตั้ง Library: ArduinoJson</p>
                <p class="text-xs text-gray-400">Sketch > Include Library > Manage Libraries > ค้นหา "ArduinoJson" by Benoit Blanchon</p>
            </div>
        </div>
        <div class="flex gap-3 items-start">
            <span class="w-7 h-7 bg-blue-500/20 rounded-lg flex items-center justify-center text-blue-400 text-sm font-bold flex-shrink-0">4</span>
            <div>
                <p class="text-sm text-white font-medium">เลือก Board: ESP32 Dev Module</p>
                <p class="text-xs text-gray-400">Tools > Board > ESP32 Arduino > ESP32 Dev Module</p>
            </div>
        </div>
        <div class="flex gap-3 items-start">
            <span class="w-7 h-7 bg-blue-500/20 rounded-lg flex items-center justify-center text-blue-400 text-sm font-bold flex-shrink-0">5</span>
            <div>
                <p class="text-sm text-white font-medium">เลือก Port + Upload</p>
                <p class="text-xs text-gray-400">Tools > Port > เลือก COM Port ของ ESP32 แล้วกด Upload</p>
            </div>
        </div>
    </div>
</div>
</div>

<!-- ============================================================ -->
<!-- Tab 4: Raspberry Pi Setup -->
<!-- ============================================================ -->
<div id="panel-raspi" class="tab-panel hidden">

<div class="glass rounded-2xl p-6 mb-6">
    <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
        <i class="fab fa-raspberry-pi text-red-400"></i> ติดตั้งระบบบน Raspberry Pi 4
    </h3>
    <p class="text-gray-400 text-sm mb-4">ขั้นตอนทั้งหมดสำหรับติดตั้งระบบ Bunny Door บน Raspberry Pi 4</p>

    <div class="space-y-6">

        <!-- Step 1 -->
        <div class="border-l-2 border-blue-500 pl-4">
            <h4 class="font-bold text-blue-400 mb-2">ขั้นที่ 1: เตรียม OS</h4>
            <p class="text-sm text-gray-300 mb-2">ใช้ Raspberry Pi Imager เขียน <strong>Raspberry Pi OS (64-bit)</strong> ลง SD Card (32GB+)</p>
            <p class="text-sm text-gray-300">เปิด SSH และตั้งค่า WiFi ใน Imager ก่อนเขียน</p>
        </div>

        <!-- Step 2 -->
        <div class="border-l-2 border-blue-500 pl-4">
            <h4 class="font-bold text-blue-400 mb-2">ขั้นที่ 2: อัปเดตระบบ</h4>
            <div class="bg-black/40 rounded-lg p-3 mt-2">
                <code class="text-sm text-green-400 block">sudo apt update && sudo apt upgrade -y</code>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="border-l-2 border-blue-500 pl-4">
            <h4 class="font-bold text-blue-400 mb-2">ขั้นที่ 3: ติดตั้ง Dependencies</h4>
            <div class="bg-black/40 rounded-lg p-3 mt-2">
                <code class="text-sm text-green-400 block whitespace-pre">sudo apt install -y python3-pip python3-venv cmake \
  build-essential libopenblas-dev liblapack-dev \
  libatlas-base-dev libhdf5-dev libjpeg-dev \
  libpng-dev mariadb-server libmariadb-dev</code>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="border-l-2 border-blue-500 pl-4">
            <h4 class="font-bold text-blue-400 mb-2">ขั้นที่ 4: ตั้งค่า MariaDB</h4>
            <div class="bg-black/40 rounded-lg p-3 mt-2">
                <code class="text-sm text-green-400 block whitespace-pre">sudo mysql_secure_installation
sudo mysql -u root -p
# สร้าง database และ user:
CREATE DATABASE bunny_door CHARACTER SET utf8mb4;
CREATE USER 'bunny'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL ON bunny_door.* TO 'bunny'@'localhost';
FLUSH PRIVILEGES;</code>
            </div>
        </div>

        <!-- Step 5 -->
        <div class="border-l-2 border-blue-500 pl-4">
            <h4 class="font-bold text-blue-400 mb-2">ขั้นที่ 5: สร้าง Python Virtual Environment</h4>
            <div class="bg-black/40 rounded-lg p-3 mt-2">
                <code class="text-sm text-green-400 block whitespace-pre">cd ~
python3 -m venv bunny-env
source bunny-env/bin/activate</code>
            </div>
        </div>

        <!-- Step 6 -->
        <div class="border-l-2 border-blue-500 pl-4">
            <h4 class="font-bold text-blue-400 mb-2">ขั้นที่ 6: ติดตั้ง dlib + face_recognition</h4>
            <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-lg p-3 mb-2">
                <p class="text-yellow-400 text-xs"><i class="fas fa-exclamation-triangle mr-1"></i> ใช้เวลาประมาณ 30-60 นาทีในการ compile</p>
            </div>
            <div class="bg-black/40 rounded-lg p-3 mt-2">
                <code class="text-sm text-green-400 block whitespace-pre"># เพิ่ม swap ชั่วคราว (สำคัญมาก!)
sudo dphys-swapfile swapoff
sudo sed -i 's/CONF_SWAPSIZE=.*/CONF_SWAPSIZE=2048/' /etc/dphys-swapfile
sudo dphys-swapfile setup
sudo dphys-swapfile swapon

pip install dlib face_recognition

# คืนค่า swap
sudo sed -i 's/CONF_SWAPSIZE=.*/CONF_SWAPSIZE=100/' /etc/dphys-swapfile
sudo dphys-swapfile setup</code>
            </div>
        </div>

        <!-- Step 7 -->
        <div class="border-l-2 border-blue-500 pl-4">
            <h4 class="font-bold text-blue-400 mb-2">ขั้นที่ 7: ติดตั้ง Packages ที่เหลือ</h4>
            <div class="bg-black/40 rounded-lg p-3 mt-2">
                <code class="text-sm text-green-400 block whitespace-pre">pip install flask flask-cors opencv-python-headless \
  numpy mysql-connector-python requests Pillow</code>
            </div>
        </div>

        <!-- Step 8 -->
        <div class="border-l-2 border-blue-500 pl-4">
            <h4 class="font-bold text-blue-400 mb-2">ขั้นที่ 8: Clone โปรเจค + ตั้งค่า .env</h4>
            <div class="bg-black/40 rounded-lg p-3 mt-2">
                <code class="text-sm text-green-400 block whitespace-pre">cd ~
git clone https://github.com/xjanova/iotsensordoorman.git bunny-door
cd bunny-door/python
cp .env.example .env
nano .env  # แก้ไข DB_USER, DB_PASS, ESP32_IP</code>
            </div>
        </div>

        <!-- Step 9 -->
        <div class="border-l-2 border-blue-500 pl-4">
            <h4 class="font-bold text-blue-400 mb-2">ขั้นที่ 9: นำเข้าฐานข้อมูล</h4>
            <div class="bg-black/40 rounded-lg p-3 mt-2">
                <code class="text-sm text-green-400 block">mysql -u bunny -p bunny_door < ~/bunny-door/database/schema.sql</code>
            </div>
        </div>

        <!-- Step 10 -->
        <div class="border-l-2 border-blue-500 pl-4">
            <h4 class="font-bold text-blue-400 mb-2">ขั้นที่ 10: เตรียมรูปใบหน้า</h4>
            <div class="bg-black/40 rounded-lg p-3 mt-2">
                <code class="text-sm text-green-400 block whitespace-pre">mkdir -p ~/bunny-door/python/images
# คัดลอกรูปใบหน้าพนักงานไปยัง images/
# ชื่อไฟล์ต้องตรงกับ face_image ในตาราง employees
# เช่น kulthida.jpg, nattanan.jpg</code>
            </div>
        </div>

        <!-- Step 11 -->
        <div class="border-l-2 border-blue-500 pl-4">
            <h4 class="font-bold text-blue-400 mb-2">ขั้นที่ 11: ทดสอบกล้อง</h4>
            <div class="bg-black/40 rounded-lg p-3 mt-2">
                <code class="text-sm text-green-400 block whitespace-pre"># ตรวจสอบกล้องที่เชื่อมต่อ
ls /dev/video*
# ควรเห็น /dev/video0 และ /dev/video2 (สำหรับ 2 กล้อง)</code>
            </div>
        </div>

        <!-- Step 12 -->
        <div class="border-l-2 border-blue-500 pl-4">
            <h4 class="font-bold text-blue-400 mb-2">ขั้นที่ 12: รันระบบ</h4>
            <div class="bg-black/40 rounded-lg p-3 mt-2">
                <code class="text-sm text-green-400 block whitespace-pre">source ~/bunny-env/bin/activate
cd ~/bunny-door/python
python face_server.py</code>
            </div>
        </div>

        <!-- Step 13 -->
        <div class="border-l-2 border-blue-500 pl-4">
            <h4 class="font-bold text-blue-400 mb-2">ขั้นที่ 13: ตั้งค่า Auto-start (systemd)</h4>
            <div class="bg-black/40 rounded-lg p-3 mt-2">
                <code class="text-sm text-green-400 block whitespace-pre">sudo nano /etc/systemd/system/bunny-door.service</code>
            </div>
            <p class="text-sm text-gray-300 my-2">เนื้อหาไฟล์:</p>
            <div class="bg-black/40 rounded-lg p-3">
                <code class="text-sm text-green-400 block whitespace-pre">[Unit]
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
WantedBy=multi-user.target</code>
            </div>
            <div class="bg-black/40 rounded-lg p-3 mt-2">
                <code class="text-sm text-green-400 block whitespace-pre">sudo systemctl daemon-reload
sudo systemctl enable bunny-door
sudo systemctl start bunny-door</code>
            </div>
        </div>

    </div>
</div>

<!-- Troubleshooting -->
<div class="glass rounded-2xl p-6">
    <h3 class="font-bold mb-4 flex items-center gap-2">
        <i class="fas fa-wrench text-orange-400"></i> แก้ปัญหาที่พบบ่อย
    </h3>
    <div class="space-y-3">
        <div class="bg-white/5 rounded-lg p-4">
            <p class="text-sm font-medium text-white mb-1">กล้องไม่ทำงาน</p>
            <p class="text-xs text-gray-400">ตรวจสอบว่า /dev/video* มีอยู่ ลอง <code class="bg-white/10 px-1 rounded">sudo chmod 666 /dev/video0</code></p>
        </div>
        <div class="bg-white/5 rounded-lg p-4">
            <p class="text-sm font-medium text-white mb-1">dlib compile ล้มเหลว</p>
            <p class="text-xs text-gray-400">ตรวจสอบ swap (ต้อง 2GB+) ด้วย <code class="bg-white/10 px-1 rounded">free -h</code></p>
        </div>
        <div class="bg-white/5 rounded-lg p-4">
            <p class="text-sm font-medium text-white mb-1">เชื่อมต่อ ESP32 ไม่ได้</p>
            <p class="text-xs text-gray-400">ตรวจสอบ WiFi SSID/Password และ IP ของ Raspberry Pi ให้ตรงกับ SERVER_URL ใน Arduino code</p>
        </div>
        <div class="bg-white/5 rounded-lg p-4">
            <p class="text-sm font-medium text-white mb-1">Face Recognition ช้า</p>
            <p class="text-xs text-gray-400">ใช้โมเดล HOG (ค่า default) แทน CNN / ลดความละเอียดกล้อง / เพิ่ม process_every_x_frames</p>
        </div>
    </div>
</div>
</div>

<script>
// ============================================================
// Tab Switching
// ============================================================
function switchTab(tabName) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('bg-blue-600', 'text-white');
        b.classList.add('text-gray-400');
    });
    document.getElementById('panel-' + tabName).classList.remove('hidden');
    const btn = document.getElementById('tab-' + tabName);
    btn.classList.add('bg-blue-600', 'text-white');
    btn.classList.remove('text-gray-400');
}

// ============================================================
// Copy Arduino Code
// ============================================================
function copyArduinoCode() {
    const code = document.getElementById('arduinoCode').textContent;
    navigator.clipboard.writeText(code).then(() => {
        showToast('คัดลอกโค้ด Arduino สำเร็จ!', 'success');
    }).catch(() => {
        // Fallback
        const ta = document.createElement('textarea');
        ta.value = code;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        showToast('คัดลอกโค้ด Arduino สำเร็จ!', 'success');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
