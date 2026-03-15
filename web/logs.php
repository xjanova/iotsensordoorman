<?php $pageTitle = 'ประวัติเข้า-ออก - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<?php
// Get employee list for filter dropdown
$db = getDB();
$employees = $db->query("SELECT id, emp_code, first_name, last_name FROM employees ORDER BY first_name")->fetchAll();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold">ประวัติการเข้า-ออก</h2>
        <p class="text-gray-400 text-sm mt-1">บันทึกจากการเปิดกลอนโซลินอย (Solenoid Lock)</p>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="glass rounded-xl p-4 stat-card">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-door-open text-blue-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold" id="statTotal">-</p>
                <p class="text-xs text-gray-500">เปิดประตูทั้งหมด</p>
            </div>
        </div>
    </div>
    <div class="glass rounded-xl p-4 stat-card">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-green-400" id="statAuthorized">-</p>
                <p class="text-xs text-gray-500">อนุญาต</p>
            </div>
        </div>
    </div>
    <div class="glass rounded-xl p-4 stat-card">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-red-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-times-circle text-red-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-red-400" id="statDenied">-</p>
                <p class="text-xs text-gray-500">ปฏิเสธ</p>
            </div>
        </div>
    </div>
    <div class="glass rounded-xl p-4 stat-card">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-orange-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-xmark text-orange-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-orange-400" id="statUnknown">-</p>
                <p class="text-xs text-gray-500">ไม่รู้จัก</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="glass rounded-2xl p-5 mb-6">
    <div class="flex items-center gap-2 mb-4">
        <i class="fas fa-filter text-blue-400"></i>
        <span class="font-medium text-sm">ตัวกรอง</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- Date From -->
        <div>
            <label class="text-xs text-gray-500 mb-1 block">ตั้งแต่วันที่</label>
            <input type="date" id="filterDateFrom" onchange="applyFilters()"
                   class="w-full bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
        </div>

        <!-- Date To -->
        <div>
            <label class="text-xs text-gray-500 mb-1 block">ถึงวันที่</label>
            <input type="date" id="filterDateTo" onchange="applyFilters()"
                   class="w-full bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
        </div>

        <!-- Direction -->
        <div>
            <label class="text-xs text-gray-500 mb-1 block">ทิศทาง</label>
            <select id="filterDirection" onchange="applyFilters()" class="w-full bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                <option value="">ทั้งหมด</option>
                <option value="IN">เข้า</option>
                <option value="OUT">ออก</option>
            </select>
        </div>

        <!-- Authorization -->
        <div>
            <label class="text-xs text-gray-500 mb-1 block">สถานะ</label>
            <select id="filterAuth" onchange="applyFilters()" class="w-full bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                <option value="">ทั้งหมด</option>
                <option value="1">อนุญาต</option>
                <option value="0">ปฏิเสธ</option>
            </select>
        </div>

        <!-- Employee -->
        <div>
            <label class="text-xs text-gray-500 mb-1 block">พนักงาน</label>
            <select id="filterEmployee" onchange="applyFilters()" class="w-full bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                <option value="all">ทั้งหมด</option>
                <option value="unknown">ไม่รู้จัก</option>
                <?php foreach ($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= htmlspecialchars($emp['emp_code']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Search -->
        <div>
            <label class="text-xs text-gray-500 mb-1 block">ค้นหา</label>
            <div class="relative">
                <input type="text" id="filterSearch" placeholder="ชื่อ, รหัส..."
                       class="w-full bg-white/10 border border-white/10 rounded-lg px-3 py-2 pr-8 text-white text-sm focus:outline-none focus:border-blue-500">
                <button onclick="applyFilters()" class="absolute right-2 top-2.5 text-gray-500 hover:text-white">
                    <i class="fas fa-search text-xs"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Quick filters + Reset -->
    <div class="flex items-center justify-between mt-3">
        <div class="flex gap-2">
            <button onclick="quickFilter('today')" class="text-xs bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 px-2.5 py-1 rounded-lg transition">วันนี้</button>
            <button onclick="quickFilter('week')" class="text-xs bg-cyan-500/10 text-cyan-400 hover:bg-cyan-500/20 px-2.5 py-1 rounded-lg transition">7 วันล่าสุด</button>
            <button onclick="quickFilter('denied')" class="text-xs bg-red-500/10 text-red-400 hover:bg-red-500/20 px-2.5 py-1 rounded-lg transition">ปฏิเสธเท่านั้น</button>
            <button onclick="quickFilter('unknown')" class="text-xs bg-orange-500/10 text-orange-400 hover:bg-orange-500/20 px-2.5 py-1 rounded-lg transition">ไม่รู้จัก</button>
        </div>
        <button onclick="resetFilters()" class="text-xs text-gray-500 hover:text-white transition">
            <i class="fas fa-rotate-left mr-1"></i> รีเซ็ตตัวกรอง
        </button>
    </div>
</div>

<!-- Summary line -->
<div class="flex items-center justify-between mb-4">
    <p class="text-sm text-gray-500" id="logSummary">กำลังโหลด...</p>
</div>

<!-- Log Table -->
<div class="glass rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-gray-400 border-b border-white/10 bg-white/5">
                    <th class="text-left py-4 px-4">#</th>
                    <th class="text-left py-4 px-4">เวลา</th>
                    <th class="text-left py-4 px-4">พนักงาน</th>
                    <th class="text-left py-4 px-4">ทิศทาง</th>
                    <th class="text-left py-4 px-4">วิธี</th>
                    <th class="text-left py-4 px-4">ความมั่นใจ</th>
                    <th class="text-left py-4 px-4">กล้อง</th>
                    <th class="text-center py-4 px-4">กลอน</th>
                    <th class="text-center py-4 px-4">รูปถ่าย</th>
                </tr>
            </thead>
            <tbody id="logsTable">
                <tr><td colspan="9" class="text-center text-gray-500 py-8">กำลังโหลด...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<div class="flex items-center justify-between mt-6" id="paginationBar" style="display:none;">
    <p class="text-xs text-gray-500" id="pageInfo"></p>
    <div class="flex gap-2">
        <button onclick="goPage(-1)" id="btnPrev" class="bg-white/5 hover:bg-white/10 text-gray-400 px-4 py-2 rounded-lg text-sm transition disabled:opacity-30" disabled>
            <i class="fas fa-chevron-left mr-1"></i> ก่อนหน้า
        </button>
        <button onclick="goPage(1)" id="btnNext" class="bg-white/5 hover:bg-white/10 text-gray-400 px-4 py-2 rounded-lg text-sm transition disabled:opacity-30" disabled>
            ถัดไป <i class="fas fa-chevron-right ml-1"></i>
        </button>
    </div>
</div>

<!-- Snapshot Modal -->
<div id="snapshotModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm" style="display:none;" onclick="if(event.target===this)closeSnapshot()">
    <div class="glass rounded-2xl overflow-hidden max-w-2xl w-full mx-4 shadow-2xl">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <div>
                <h3 class="font-medium" id="snapTitle">รูปถ่ายขณะเข้า-ออก</h3>
                <p class="text-xs text-gray-400" id="snapSubtitle"></p>
            </div>
            <button onclick="closeSnapshot()" class="text-gray-400 hover:text-white transition">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-2 bg-black flex items-center justify-center" style="min-height: 300px;">
            <img id="snapImage" src="" alt="Snapshot" class="max-w-full max-h-[70vh] object-contain">
            <div id="snapLoading" class="text-center text-gray-500">
                <i class="fas fa-spinner fa-spin text-3xl mb-2"></i>
                <p class="text-sm">กำลังโหลดรูป...</p>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let totalPages = 1;
const perPage = 20;

function getFilterParams() {
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    const direction = document.getElementById('filterDirection').value;
    const auth = document.getElementById('filterAuth').value;
    const employee = document.getElementById('filterEmployee').value;
    const search = document.getElementById('filterSearch').value.trim();

    let params = `limit=${perPage}&page=${currentPage}`;
    if (dateFrom) params += `&date_from=${dateFrom}`;
    if (dateTo) params += `&date_to=${dateTo}`;
    if (direction) params += `&direction=${direction}`;
    if (auth !== '') params += `&authorized=${auth}`;
    if (employee !== 'all') params += `&employee=${employee}`;
    if (search) params += `&search=${encodeURIComponent(search)}`;
    return params;
}

async function loadLogs() {
    const tbody = document.getElementById('logsTable');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-gray-500 py-8"><i class="fas fa-spinner fa-spin text-xl"></i></td></tr>';

    const result = await fetchAPI('api/access_logs.php?' + getFilterParams());

    if (!result || !result.data) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-red-400 py-8">เกิดข้อผิดพลาด</td></tr>';
        return;
    }

    const data = result.data;
    const pag = result.pagination;
    totalPages = pag.total_pages;

    // Summary
    document.getElementById('logSummary').textContent = `แสดง ${data.length} จาก ${pag.total} รายการ (หน้า ${pag.page}/${pag.total_pages})`;

    // Stats
    let authorized = 0, denied = 0, unknown = 0;
    data.forEach(log => {
        if (log.is_authorized == 1) authorized++;
        else denied++;
        if (!log.employee_id) unknown++;
    });
    document.getElementById('statTotal').textContent = pag.total;
    document.getElementById('statAuthorized').textContent = authorized;
    document.getElementById('statDenied').textContent = denied;
    document.getElementById('statUnknown').textContent = unknown;

    // Pagination
    const pagBar = document.getElementById('paginationBar');
    if (pag.total_pages > 1) {
        pagBar.style.display = 'flex';
        document.getElementById('pageInfo').textContent = `หน้า ${pag.page} / ${pag.total_pages}`;
        document.getElementById('btnPrev').disabled = (pag.page <= 1);
        document.getElementById('btnNext').disabled = (pag.page >= pag.total_pages);
    } else {
        pagBar.style.display = pag.total > 0 ? 'flex' : 'none';
        document.getElementById('pageInfo').textContent = `ทั้งหมด ${pag.total} รายการ`;
        document.getElementById('btnPrev').disabled = true;
        document.getElementById('btnNext').disabled = true;
    }

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-gray-500 py-12"><i class="fas fa-inbox text-3xl mb-2"></i><br>ไม่มีข้อมูล</td></tr>';
        return;
    }

    const startNum = (pag.page - 1) * pag.limit;

    tbody.innerHTML = data.map((log, i) => `
        <tr class="border-b border-white/5 hover:bg-white/5 transition">
            <td class="py-3 px-4 text-gray-500">${startNum + i + 1}</td>
            <td class="py-3 px-4">
                <p class="text-gray-300">${formatDateTime(log.created_at)}</p>
            </td>
            <td class="py-3 px-4">
                ${log.first_name
                    ? `<span class="text-white">${esc(log.first_name)} ${esc(log.last_name)}</span><br><span class="text-xs text-gray-500">${esc(log.emp_code || '')}</span>`
                    : '<span class="text-red-400"><i class="fas fa-user-xmark mr-1"></i>ไม่รู้จัก</span>'}
            </td>
            <td class="py-3 px-4">
                ${log.direction === 'IN'
                    ? '<span class="bg-green-500/20 text-green-400 px-2 py-1 rounded text-xs"><i class="fas fa-arrow-right mr-1"></i>เข้า</span>'
                    : '<span class="bg-blue-500/20 text-blue-400 px-2 py-1 rounded text-xs"><i class="fas fa-arrow-left mr-1"></i>ออก</span>'}
            </td>
            <td class="py-3 px-4 text-gray-400 text-xs">${methodLabel(log.method)}</td>
            <td class="py-3 px-4">
                <div class="flex items-center gap-2">
                    <div class="w-16 bg-gray-700 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full ${parseFloat(log.confidence) > 70 ? 'bg-green-400' : parseFloat(log.confidence) > 40 ? 'bg-yellow-400' : 'bg-red-400'}"
                             style="width: ${Math.min(100, parseFloat(log.confidence) || 0)}%"></div>
                    </div>
                    <span class="text-xs text-gray-400">${parseFloat(log.confidence)?.toFixed(1) || '0'}%</span>
                </div>
            </td>
            <td class="py-3 px-4 text-gray-500 text-xs">${log.camera_id == 1 ? '<i class="fas fa-video mr-1"></i>นอก' : log.camera_id == 2 ? '<i class="fas fa-video mr-1"></i>ใน' : '-'}</td>
            <td class="py-3 px-4 text-center">
                ${log.is_authorized == 1
                    ? '<span class="bg-green-500/20 text-green-400 px-2 py-1 rounded text-xs"><i class="fas fa-lock-open mr-1"></i>เปิด</span>'
                    : '<span class="bg-red-500/20 text-red-400 px-2 py-1 rounded text-xs"><i class="fas fa-lock mr-1"></i>ล็อก</span>'}
            </td>
            <td class="py-3 px-4 text-center">
                ${log.snapshot_path
                    ? `<button onclick="showSnapshot('${esc(log.snapshot_path)}', '${esc(log.first_name || 'ไม่รู้จัก')}', '${formatDateTime(log.created_at)}')"
                         class="bg-indigo-500/20 text-indigo-400 hover:bg-indigo-500/30 px-2 py-1 rounded text-xs transition">
                         <i class="fas fa-image mr-1"></i>ดูรูป</button>`
                    : '<span class="text-gray-600 text-xs">-</span>'}
            </td>
        </tr>
    `).join('');
}

function methodLabel(method) {
    const labels = {
        'face_recognition': '<i class="fas fa-face-smile text-indigo-400 mr-1"></i>สแกนหน้า',
        'emergency_button': '<i class="fas fa-exclamation-triangle text-red-400 mr-1"></i>ฉุกเฉิน',
        'manual': '<i class="fas fa-hand text-yellow-400 mr-1"></i>กดปุ่ม',
        'remote': '<i class="fas fa-wifi text-blue-400 mr-1"></i>รีโมท',
    };
    return labels[method] || esc(method || '-');
}

function applyFilters() {
    currentPage = 1;
    loadLogs();
}

function goPage(delta) {
    currentPage = Math.max(1, Math.min(totalPages, currentPage + delta));
    loadLogs();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetFilters() {
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    document.getElementById('filterDirection').value = '';
    document.getElementById('filterAuth').value = '';
    document.getElementById('filterEmployee').value = 'all';
    document.getElementById('filterSearch').value = '';
    currentPage = 1;
    loadLogs();
}

function quickFilter(preset) {
    resetFilterValues();
    const today = new Date().toISOString().split('T')[0];

    if (preset === 'today') {
        document.getElementById('filterDateFrom').value = today;
        document.getElementById('filterDateTo').value = today;
    } else if (preset === 'week') {
        const week = new Date();
        week.setDate(week.getDate() - 7);
        document.getElementById('filterDateFrom').value = week.toISOString().split('T')[0];
        document.getElementById('filterDateTo').value = today;
    } else if (preset === 'denied') {
        document.getElementById('filterAuth').value = '0';
    } else if (preset === 'unknown') {
        document.getElementById('filterEmployee').value = 'unknown';
    }
    applyFilters();
}

function resetFilterValues() {
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    document.getElementById('filterDirection').value = '';
    document.getElementById('filterAuth').value = '';
    document.getElementById('filterEmployee').value = 'all';
    document.getElementById('filterSearch').value = '';
}

// Search on Enter
document.getElementById('filterSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') applyFilters();
});

// Snapshot modal
function showSnapshot(path, name, time) {
    const modal = document.getElementById('snapshotModal');
    const img = document.getElementById('snapImage');
    const loading = document.getElementById('snapLoading');

    document.getElementById('snapTitle').textContent = name || 'รูปถ่าย';
    document.getElementById('snapSubtitle').textContent = time || '';

    img.style.display = 'none';
    loading.style.display = '';
    modal.style.display = 'flex';

    // ลองโหลดรูปจาก Laragon ก่อน (path = "snapshots/2026-03/cam1_xxx.jpg")
    // ถ้ามี / แสดงว่าเป็น path บน Laragon
    let imgUrl;
    if (path.includes('/')) {
        imgUrl = path + '?t=' + Date.now();
    } else {
        // path เก่า (ชื่อไฟล์เดี่ยว) → ลองดึงจาก face_server
        imgUrl = FACE_SERVER + '/api/snapshots/' + path + '?t=' + Date.now();
    }

    const testImg = new Image();
    testImg.onload = () => {
        img.src = testImg.src;
        img.style.display = '';
        loading.style.display = 'none';
    };
    testImg.onerror = () => {
        // Fallback: ลองจาก face_server ถ้า path เดิม
        if (path.includes('/')) {
            loading.innerHTML = '<i class="fas fa-image-slash text-3xl text-red-400 mb-2"></i><p class="text-sm text-red-400">ไม่พบรูปภาพ</p>';
        } else {
            loading.innerHTML = '<i class="fas fa-image-slash text-3xl text-red-400 mb-2"></i><p class="text-sm text-red-400">ไม่พบรูปภาพ</p>';
        }
    };
    testImg.src = imgUrl;

    // ปิดด้วย ESC
    document.addEventListener('keydown', _snapEscHandler);
}

function _snapEscHandler(e) {
    if (e.key === 'Escape') closeSnapshot();
}

function closeSnapshot() {
    document.getElementById('snapshotModal').style.display = 'none';
    document.getElementById('snapImage').src = '';
    document.getElementById('snapLoading').innerHTML = '<i class="fas fa-spinner fa-spin text-3xl mb-2"></i><p class="text-sm">กำลังโหลดรูป...</p>';
    document.removeEventListener('keydown', _snapEscHandler);
}

document.addEventListener('DOMContentLoaded', () => loadLogs());
</script>

<?php include 'includes/footer.php'; ?>
