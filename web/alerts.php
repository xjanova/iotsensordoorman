<?php $pageTitle = 'การแจ้งเตือน - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold">การแจ้งเตือนความผิดปกติ</h2>
        <p class="text-gray-400 text-sm mt-1">ตรวจสอบเหตุการณ์ผิดปกติ เช่น Tailgating, ใบหน้าไม่รู้จัก</p>
    </div>
    <div class="flex gap-2">
        <button onclick="resolveAllFiltered()" class="bg-green-600/20 text-green-400 hover:bg-green-600/30 px-4 py-2 rounded-lg text-sm transition">
            <i class="fas fa-check-double mr-1"></i> ดำเนินการทั้งหมด
        </button>
    </div>
</div>

<!-- Filters -->
<div class="glass rounded-2xl p-5 mb-6">
    <div class="flex items-center gap-2 mb-4">
        <i class="fas fa-filter text-blue-400"></i>
        <span class="font-medium text-sm">ตัวกรอง</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- Status -->
        <div>
            <label class="text-xs text-gray-500 mb-1 block">สถานะ</label>
            <select id="filterStatus" onchange="applyFilters()" class="w-full bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                <option value="0">ยังไม่ดำเนินการ</option>
                <option value="">ทั้งหมด</option>
                <option value="1">ดำเนินการแล้ว</option>
            </select>
        </div>

        <!-- Type -->
        <div>
            <label class="text-xs text-gray-500 mb-1 block">ประเภท</label>
            <select id="filterType" onchange="applyFilters()" class="w-full bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                <option value="all">ทั้งหมด</option>
                <option value="UNKNOWN_FACE">ใบหน้าไม่รู้จัก</option>
                <option value="TAILGATING">Tailgating</option>
                <option value="FORCED_ENTRY">บุกรุก</option>
                <option value="SENSOR_MISMATCH">เซ็นเซอร์ไม่ตรง</option>
                <option value="MULTI_PERSON">หลายคน</option>
                <option value="NO_FACE_DETECTED">ไม่พบใบหน้า</option>
            </select>
        </div>

        <!-- Severity -->
        <div>
            <label class="text-xs text-gray-500 mb-1 block">ระดับความรุนแรง</label>
            <select id="filterSeverity" onchange="applyFilters()" class="w-full bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                <option value="all">ทั้งหมด</option>
                <option value="CRITICAL">วิกฤต</option>
                <option value="HIGH">สูง</option>
                <option value="MEDIUM">ปานกลาง</option>
                <option value="LOW">ต่ำ</option>
            </select>
        </div>

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

        <!-- Search -->
        <div>
            <label class="text-xs text-gray-500 mb-1 block">ค้นหา</label>
            <div class="relative">
                <input type="text" id="filterSearch" placeholder="รายละเอียด..."
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
            <button onclick="quickFilter('CRITICAL')" class="text-xs bg-red-500/10 text-red-400 hover:bg-red-500/20 px-2.5 py-1 rounded-lg transition">วิกฤตเท่านั้น</button>
            <button onclick="quickFilter('UNKNOWN_FACE')" class="text-xs bg-orange-500/10 text-orange-400 hover:bg-orange-500/20 px-2.5 py-1 rounded-lg transition">ใบหน้าไม่รู้จัก</button>
            <button onclick="quickFilter('today')" class="text-xs bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 px-2.5 py-1 rounded-lg transition">วันนี้</button>
        </div>
        <button onclick="resetFilters()" class="text-xs text-gray-500 hover:text-white transition">
            <i class="fas fa-rotate-left mr-1"></i> รีเซ็ตตัวกรอง
        </button>
    </div>
</div>

<!-- Summary -->
<div class="flex items-center justify-between mb-4">
    <p class="text-sm text-gray-500" id="alertSummary">กำลังโหลด...</p>
</div>

<!-- Alert List -->
<div class="space-y-3" id="alertsList">
    <div class="text-center text-gray-500 py-8">กำลังโหลด...</div>
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

<script>
const severityColors = {
    LOW: 'border-gray-500/30 bg-gray-500/5',
    MEDIUM: 'border-yellow-500/30 bg-yellow-500/5',
    HIGH: 'border-orange-500/30 bg-orange-500/5',
    CRITICAL: 'border-red-500/30 bg-red-500/5',
};
const severityBadge = {
    LOW: 'bg-gray-500/20 text-gray-400',
    MEDIUM: 'bg-yellow-500/20 text-yellow-400',
    HIGH: 'bg-orange-500/20 text-orange-400',
    CRITICAL: 'bg-red-500/20 text-red-400',
};
const severityThai = { LOW: 'ต่ำ', MEDIUM: 'ปานกลาง', HIGH: 'สูง', CRITICAL: 'วิกฤต' };
const typeIcons = {
    UNKNOWN_FACE: 'fa-user-xmark text-red-400',
    TAILGATING: 'fa-people-group text-orange-400',
    FORCED_ENTRY: 'fa-door-open text-purple-400',
    SENSOR_MISMATCH: 'fa-triangle-exclamation text-yellow-400',
    MULTI_PERSON: 'fa-users text-pink-400',
    NO_FACE_DETECTED: 'fa-face-meh text-gray-400',
};
const typeThai = {
    UNKNOWN_FACE: 'ใบหน้าไม่รู้จัก',
    TAILGATING: 'Tailgating',
    FORCED_ENTRY: 'บุกรุก',
    SENSOR_MISMATCH: 'เซ็นเซอร์ไม่ตรง',
    MULTI_PERSON: 'หลายคนพร้อมกัน',
    NO_FACE_DETECTED: 'ไม่พบใบหน้า',
};

let currentPage = 1;
let totalPages = 1;
const perPage = 20;

function getFilterParams() {
    const status = document.getElementById('filterStatus').value;
    const type = document.getElementById('filterType').value;
    const severity = document.getElementById('filterSeverity').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    const search = document.getElementById('filterSearch').value.trim();

    let params = `limit=${perPage}&page=${currentPage}`;
    if (status !== '') params += `&resolved=${status}`;
    if (type !== 'all') params += `&type=${type}`;
    if (severity !== 'all') params += `&severity=${severity}`;
    if (dateFrom) params += `&date_from=${dateFrom}`;
    if (dateTo) params += `&date_to=${dateTo}`;
    if (search) params += `&search=${encodeURIComponent(search)}`;
    return params;
}

async function loadAlerts() {
    const container = document.getElementById('alertsList');
    container.innerHTML = '<div class="text-center text-gray-500 py-8"><i class="fas fa-spinner fa-spin text-2xl"></i></div>';

    const data = await fetchAPI('api/alerts.php?' + getFilterParams());

    if (!data || !data.data) {
        container.innerHTML = '<div class="text-center text-gray-500 py-8"><i class="fas fa-exclamation-circle text-2xl mb-2 text-red-400"></i><p>เกิดข้อผิดพลาด</p></div>';
        return;
    }

    const alerts = data.data;
    const pag = data.pagination;
    totalPages = pag.total_pages;

    // Summary
    document.getElementById('alertSummary').textContent = `แสดง ${alerts.length} จาก ${pag.total} รายการ (หน้า ${pag.page}/${pag.total_pages})`;

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

    if (alerts.length === 0) {
        container.innerHTML = '<div class="text-center text-gray-500 py-12"><i class="fas fa-check-circle text-4xl mb-3 text-green-400"></i><p class="text-lg">ไม่มีการแจ้งเตือน</p><p class="text-xs text-gray-600 mt-1">ไม่พบรายการที่ตรงกับตัวกรอง</p></div>';
        return;
    }

    container.innerHTML = alerts.map(alert => `
        <div class="glass rounded-xl p-5 border ${severityColors[alert.severity] || ''} card-hover">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4 flex-1">
                    <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center mt-1 flex-shrink-0">
                        <i class="fas ${typeIcons[alert.alert_type] || 'fa-bell text-gray-400'}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="font-medium">${typeThai[alert.alert_type] || alert.alert_type}</span>
                            <span class="px-2 py-0.5 rounded text-xs ${severityBadge[alert.severity]}">${severityThai[alert.severity] || alert.severity}</span>
                            ${alert.camera_id ? '<span class="text-xs text-gray-500"><i class="fas fa-video mr-0.5"></i>กล้อง ' + (alert.camera_id == 1 ? 'นอก' : 'ใน') + '</span>' : ''}
                        </div>
                        <p class="text-sm text-gray-400">${esc(alert.description) || '-'}</p>
                        <div class="flex items-center gap-3 mt-1.5">
                            <p class="text-xs text-gray-500"><i class="fas fa-clock mr-1"></i>${formatDateTime(alert.created_at)}</p>
                            ${alert.is_resolved == 1 && alert.resolved_at ? '<p class="text-xs text-green-600"><i class="fas fa-check mr-1"></i>แก้ไขเมื่อ ' + formatDateTime(alert.resolved_at) + '</p>' : ''}
                        </div>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    ${alert.is_resolved == 0
                        ? '<button onclick="resolveAlert(' + alert.id + ')" class="bg-green-600/20 text-green-400 hover:bg-green-600/30 px-3 py-1.5 rounded-lg text-xs transition whitespace-nowrap"><i class="fas fa-check mr-1"></i>ดำเนินการแล้ว</button>'
                        : '<span class="text-green-400 text-xs whitespace-nowrap"><i class="fas fa-check-circle mr-1"></i>แก้ไขแล้ว</span>'}
                </div>
            </div>
        </div>
    `).join('');
}

async function resolveAlert(id) {
    const res = await postAPI('api/alerts.php', { resolve_id: id });
    if (res?.success) {
        showToast('ดำเนินการแจ้งเตือนสำเร็จ', 'success');
    }
    loadAlerts();
}

async function resolveAllFiltered() {
    const type = document.getElementById('filterType').value;
    const confirmed = await showConfirm('ยืนยันดำเนินการทั้งหมด?', 'จะทำเครื่องหมายว่าแก้ไขแล้วทั้งหมดที่ยังไม่ดำเนินการ');
    if (!confirmed) return;

    const res = await postAPI('api/alerts.php', { resolve_all: true, type: type });
    if (res?.success) {
        showToast(`ดำเนินการแล้ว ${res.resolved} รายการ`, 'success');
    }
    loadAlerts();
}

function applyFilters() {
    currentPage = 1;
    loadAlerts();
}

function goPage(delta) {
    currentPage = Math.max(1, Math.min(totalPages, currentPage + delta));
    loadAlerts();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetFilters() {
    document.getElementById('filterStatus').value = '0';
    document.getElementById('filterType').value = 'all';
    document.getElementById('filterSeverity').value = 'all';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    document.getElementById('filterSearch').value = '';
    currentPage = 1;
    loadAlerts();
}

function quickFilter(preset) {
    resetFilterValues();
    if (preset === 'CRITICAL') {
        document.getElementById('filterSeverity').value = 'CRITICAL';
    } else if (preset === 'UNKNOWN_FACE') {
        document.getElementById('filterType').value = 'UNKNOWN_FACE';
    } else if (preset === 'today') {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('filterDateFrom').value = today;
        document.getElementById('filterDateTo').value = today;
    }
    applyFilters();
}

function resetFilterValues() {
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterType').value = 'all';
    document.getElementById('filterSeverity').value = 'all';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    document.getElementById('filterSearch').value = '';
}

// Search on Enter
document.getElementById('filterSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') applyFilters();
});

document.addEventListener('DOMContentLoaded', () => loadAlerts());
</script>

<?php include 'includes/footer.php'; ?>
