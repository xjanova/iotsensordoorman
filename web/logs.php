<?php $pageTitle = 'ประวัติเข้า-ออก - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<?php
$db = getDB();
$employees = $db->query("SELECT id, emp_code, first_name, last_name FROM employees ORDER BY first_name")->fetchAll();
?>

<div class="page-head">
    <div>
        <h1>ประวัติการเข้า-ออก</h1>
        <div class="sub">บันทึกจากการเปิดกลอนโซลินอย (Solenoid Lock)</div>
    </div>
</div>

<!-- Summary KPIs -->
<div class="grid kpis section">
    <div class="card kpi">
        <div class="label"><span class="dot"></span>ทั้งหมด</div>
        <div class="num" id="statTotal">—</div>
    </div>
    <div class="card kpi">
        <div class="label"><span class="dot ok"></span>อนุญาต</div>
        <div class="num" id="statAuthorized">—</div>
    </div>
    <div class="card kpi">
        <div class="label"><span class="dot danger"></span>ปฏิเสธ</div>
        <div class="num" id="statDenied">—</div>
    </div>
    <div class="card kpi">
        <div class="label"><span class="dot warn"></span>ไม่รู้จัก</div>
        <div class="num" id="statUnknown">—</div>
    </div>
</div>

<!-- Filters -->
<div class="card section">
    <div class="row" style="margin-bottom: 14px;">
        <?= ico('filter', 14) ?>
        <strong style="font-size: 13px;">ตัวกรอง</strong>
    </div>
    <div class="grid cols-3" style="gap: 14px;">
        <div>
            <label class="tiny muted" style="display:block; margin-bottom:4px;">ตั้งแต่วันที่</label>
            <input type="date" id="filterDateFrom" onchange="applyFilters()" class="input">
        </div>
        <div>
            <label class="tiny muted" style="display:block; margin-bottom:4px;">ถึงวันที่</label>
            <input type="date" id="filterDateTo" onchange="applyFilters()" class="input">
        </div>
        <div>
            <label class="tiny muted" style="display:block; margin-bottom:4px;">ทิศทาง</label>
            <select id="filterDirection" onchange="applyFilters()" class="select">
                <option value="">ทั้งหมด</option>
                <option value="IN">เข้า</option>
                <option value="OUT">ออก</option>
            </select>
        </div>
        <div>
            <label class="tiny muted" style="display:block; margin-bottom:4px;">สถานะ</label>
            <select id="filterAuth" onchange="applyFilters()" class="select">
                <option value="">ทั้งหมด</option>
                <option value="1">อนุญาต</option>
                <option value="0">ปฏิเสธ</option>
            </select>
        </div>
        <div>
            <label class="tiny muted" style="display:block; margin-bottom:4px;">พนักงาน</label>
            <select id="filterEmployee" onchange="applyFilters()" class="select">
                <option value="all">ทั้งหมด</option>
                <option value="unknown">ไม่รู้จัก</option>
                <?php foreach ($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= htmlspecialchars($emp['emp_code']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="tiny muted" style="display:block; margin-bottom:4px;">ค้นหา</label>
            <div class="search-wrap"><?= ico('search', 14) ?><input type="text" id="filterSearch" placeholder="ชื่อ, รหัส..." class="input input-search" onkeypress="if(event.key==='Enter')applyFilters()"></div>
        </div>
    </div>
    <div class="row" style="margin-top: 14px; justify-content: space-between;">
        <div class="row gap-2">
            <button class="btn sm" onclick="quickFilter('today')">วันนี้</button>
            <button class="btn sm" onclick="quickFilter('week')">7 วันล่าสุด</button>
            <button class="btn sm" onclick="quickFilter('denied')">ปฏิเสธเท่านั้น</button>
            <button class="btn sm" onclick="quickFilter('unknown')">ไม่รู้จัก</button>
        </div>
        <button class="btn sm ghost" onclick="resetFilters()"><?= ico('refresh', 12) ?> รีเซ็ต</button>
    </div>
</div>

<!-- Summary + delete actions -->
<div class="row spread" style="margin-bottom: 14px;">
    <span class="tiny muted" id="logSummary">กำลังโหลด...</span>
    <div class="row gap-2" id="deleteActions" style="display:none;">
        <span class="tiny muted" id="selectedCount"></span>
        <button class="btn sm danger" onclick="deleteSelected()"><?= ico('trash', 12) ?> ลบที่เลือก</button>
        <button class="btn sm ghost" onclick="deleteAll()"><?= ico('trash', 12) ?> ลบทั้งหมด</button>
    </div>
</div>

<!-- Log Table -->
<div class="card flush">
    <div style="overflow-x: auto;">
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width: 36px;"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                    <th>#</th>
                    <th>เวลา</th>
                    <th>พนักงาน</th>
                    <th>ทิศทาง</th>
                    <th>วิธี</th>
                    <th>ความมั่นใจ</th>
                    <th>กล้อง</th>
                    <th>กลอน</th>
                    <th>รูป</th>
                </tr>
            </thead>
            <tbody id="logsTable">
                <tr><td colspan="10" class="empty">กำลังโหลด...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<div class="row spread" id="paginationBar" style="margin-top: 18px; display:none;">
    <span class="tiny muted" id="pageInfo"></span>
    <div class="row gap-2">
        <button class="btn sm" id="btnPrev" onclick="goPage(-1)" disabled><?= ico('chev-l', 12) ?> ก่อนหน้า</button>
        <button class="btn sm" id="btnNext" onclick="goPage(1)" disabled>ถัดไป <?= ico('chev-r', 12) ?></button>
    </div>
</div>

<!-- Snapshot Modal -->
<div id="snapshotModal" class="modal-backdrop" style="display:none;">
    <div class="modal">
        <div class="modal-head">
            <div>
                <h3 id="snapTitle">รูปถ่ายขณะเข้า-ออก</h3>
                <div class="tiny muted" id="snapSubtitle"></div>
            </div>
            <button onclick="closeSnapshot()" class="icon-btn"><?= ico('x', 14) ?></button>
        </div>
        <div style="background: #000; min-height: 320px; display: flex; align-items: center; justify-content: center; padding: 8px;">
            <img id="snapImage" src="" alt="Snapshot" style="max-width:100%; max-height:70vh; object-fit:contain;">
            <div id="snapLoading" class="empty">
                <?= ico('refresh', 22) ?>
                <p>กำลังโหลดรูป...</p>
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
    tbody.innerHTML = '<tr><td colspan="10" class="empty"><div style="padding:24px;">กำลังโหลด...</div></td></tr>';

    const result = await fetchAPI('api/access_logs.php?' + getFilterParams());

    if (!result || !result.data) {
        tbody.innerHTML = '<tr><td colspan="10" class="empty text-danger">เกิดข้อผิดพลาด</td></tr>';
        return;
    }

    const data = result.data;
    const pag = result.pagination;
    totalPages = pag.total_pages;

    document.getElementById('logSummary').textContent = `แสดง ${data.length} จาก ${pag.total} รายการ (หน้า ${pag.page}/${pag.total_pages})`;

    let authorized = 0, denied = 0, unknown = 0;
    data.forEach(log => {
        if (log.is_authorized == 1) authorized++; else denied++;
        if (!log.employee_id) unknown++;
    });
    document.getElementById('statTotal').textContent = pag.total;
    document.getElementById('statAuthorized').textContent = authorized;
    document.getElementById('statDenied').textContent = denied;
    document.getElementById('statUnknown').textContent = unknown;

    const pagBar = document.getElementById('paginationBar');
    if (pag.total_pages > 1) {
        pagBar.style.display = 'flex';
        document.getElementById('pageInfo').textContent = `หน้า ${pag.page} / ${pag.total_pages}`;
        document.getElementById('btnPrev').disabled = (pag.page <= 1);
        document.getElementById('btnNext').disabled = (pag.page >= pag.total_pages);
    } else {
        pagBar.style.display = pag.total > 0 ? 'flex' : 'none';
        document.getElementById('pageInfo').textContent = `ทั้งหมด ${pag.total} รายการ`;
    }

    document.getElementById('deleteActions').style.display = pag.total > 0 ? 'flex' : 'none';
    document.getElementById('selectAll').checked = false;
    updateSelectedCount();

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="empty"><div style="padding: 32px;">ไม่มีรายการในตัวกรองนี้</div></td></tr>';
        return;
    }

    const startNum = (pag.page - 1) * pag.limit;
    tbody.innerHTML = data.map((log, i) => {
        const conf = parseFloat(log.confidence) || 0;
        const confClass = conf > 70 ? '' : conf > 40 ? 'warn' : 'danger';
        const name = log.first_name
            ? `<div style="line-height:1.2;"><div>${esc(log.first_name)} ${esc(log.last_name)}</div><div class="tbl-row-id">${esc(log.emp_code || '')}</div></div>`
            : '<span class="text-danger">ไม่รู้จัก</span>';
        const dir = log.direction === 'IN'
            ? '<span class="badge ok"><span class="dot"></span>เข้า</span>'
            : '<span class="badge info"><span class="dot"></span>ออก</span>';
        const cam = log.camera_id == 1 ? '<span class="mono tiny muted">cam-out</span>' : log.camera_id == 2 ? '<span class="mono tiny muted">cam-in</span>' : '<span class="muted">-</span>';
        const door = log.is_authorized == 1
            ? '<span class="badge ok">เปิด</span>'
            : '<span class="badge danger">ล็อก</span>';
        const snap = log.snapshot_path
            ? `<button class="btn sm ghost btn-snap"
                       data-path="${esc(log.snapshot_path)}"
                       data-name="${esc(log.first_name || 'ไม่รู้จัก')}"
                       data-time="${esc(formatDateTime(log.created_at))}"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></svg> ดู</button>`
            : '<span class="muted">-</span>';

        return `<tr data-log-id="${log.id}">
            <td><input type="checkbox" class="log-check" value="${log.id}" onchange="updateSelectedCount()"></td>
            <td class="muted mono tiny">${startNum + i + 1}</td>
            <td class="mono tiny">${formatDateTime(log.created_at)}</td>
            <td>${name}</td>
            <td>${dir}</td>
            <td>${methodLabel(log.method)}</td>
            <td>
                <div class="row gap-2">
                    <div class="conf-bar ${confClass}" style="width:64px; height:4px; background:var(--border); border-radius:4px; overflow:hidden;">
                        <div style="height:100%; width:${Math.min(100, conf)}%; background:${conf > 70 ? 'var(--ok)' : conf > 40 ? 'var(--warn)' : 'var(--danger)'};"></div>
                    </div>
                    <span class="mono tiny muted">${conf.toFixed(1)}%</span>
                </div>
            </td>
            <td>${cam}</td>
            <td>${door}</td>
            <td>${snap}</td>
        </tr>`;
    }).join('');
}

function methodLabel(method) {
    const labels = {
        'face_recognition': '<span class="mono tiny muted">face</span>',
        'emergency_button': '<span class="mono tiny text-danger">emergency</span>',
        'manual':           '<span class="mono tiny text-warn">manual</span>',
        'remote':           '<span class="mono tiny text-info">remote</span>',
    };
    return labels[method] || '<span class="mono tiny muted">' + esc(method || '-') + '</span>';
}

function applyFilters() { currentPage = 1; loadLogs(); }
function goPage(delta) { currentPage = Math.max(1, Math.min(totalPages, currentPage + delta)); loadLogs(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
function resetFilterValues() {
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    document.getElementById('filterDirection').value = '';
    document.getElementById('filterAuth').value = '';
    document.getElementById('filterEmployee').value = 'all';
    document.getElementById('filterSearch').value = '';
}
function resetFilters() { resetFilterValues(); currentPage = 1; loadLogs(); }
function quickFilter(preset) {
    resetFilterValues();
    const today = new Date().toISOString().split('T')[0];
    if (preset === 'today') {
        document.getElementById('filterDateFrom').value = today;
        document.getElementById('filterDateTo').value = today;
    } else if (preset === 'week') {
        const week = new Date(); week.setDate(week.getDate() - 7);
        document.getElementById('filterDateFrom').value = week.toISOString().split('T')[0];
        document.getElementById('filterDateTo').value = today;
    } else if (preset === 'denied') {
        document.getElementById('filterAuth').value = '0';
    } else if (preset === 'unknown') {
        document.getElementById('filterEmployee').value = 'unknown';
    }
    applyFilters();
}

// Snapshot modal
function showSnapshot(path, name, time) {
    const modal = document.getElementById('snapshotModal');
    const img = document.getElementById('snapImage');
    const loading = document.getElementById('snapLoading');
    document.getElementById('snapTitle').textContent = name || 'รูปถ่าย';
    document.getElementById('snapSubtitle').textContent = time || '';
    img.style.display = 'none'; loading.style.display = '';
    modal.style.display = 'flex';
    const ts = Date.now();
    if (path.includes('/')) {
        tryLoadImage(path + '?t=' + ts, img, loading, null);
    } else {
        tryLoadImage('snapshots/' + path + '?t=' + ts, img, loading, FACE_SERVER + '/api/snapshots/' + path + '?t=' + ts);
    }
    document.addEventListener('keydown', _snapEscHandler);
}
function tryLoadImage(url, imgEl, loadingEl, fallbackUrl) {
    const t = new Image();
    t.onload = () => { imgEl.src = t.src; imgEl.style.display = ''; loadingEl.style.display = 'none'; };
    t.onerror = () => {
        if (fallbackUrl) {
            const f = new Image();
            f.onload = () => { imgEl.src = f.src; imgEl.style.display = ''; loadingEl.style.display = 'none'; };
            f.onerror = () => { loadingEl.innerHTML = '<div class="text-danger">ไม่พบรูปภาพ</div>'; };
            f.src = fallbackUrl;
        } else {
            loadingEl.innerHTML = '<div class="text-danger">ไม่พบรูปภาพ</div>';
        }
    };
    t.src = url;
}
function _snapEscHandler(e) { if (e.key === 'Escape') closeSnapshot(); }
function closeSnapshot() {
    document.getElementById('snapshotModal').style.display = 'none';
    document.getElementById('snapImage').src = '';
    document.removeEventListener('keydown', _snapEscHandler);
}
document.getElementById('snapshotModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeSnapshot(); });

// Select & Delete
function toggleSelectAll() {
    const checked = document.getElementById('selectAll').checked;
    document.querySelectorAll('.log-check').forEach(cb => cb.checked = checked);
    updateSelectedCount();
}
function updateSelectedCount() {
    const c = document.querySelectorAll('.log-check:checked').length;
    document.getElementById('selectedCount').textContent = c > 0 ? c + ' รายการ' : '';
}
function getSelectedIds() { return Array.from(document.querySelectorAll('.log-check:checked')).map(cb => parseInt(cb.value)); }

async function deleteSelected() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    showConfirm('ลบรายการที่เลือก', `ต้องการลบ ${ids.length} รายการที่เลือก?`, async () => {
        const r = await postAPI('api/access_logs.php?action=delete', { ids });
        if (r?.success) { showToast(`ลบ ${ids.length} รายการ`, 'success'); loadLogs(); }
        else { showToast('ลบไม่สำเร็จ: ' + (r?.error || 'Unknown'), 'error'); }
    });
}
async function deleteAll() {
    showConfirm('ลบประวัติทั้งหมด', 'ต้องการลบประวัติเข้า-ออกทั้งหมด? ดำเนินการนี้ไม่สามารถย้อนกลับได้!', async () => {
        const r = await postAPI('api/access_logs.php?action=delete', { all: true });
        if (r?.success) { showToast('ลบประวัติทั้งหมดแล้ว', 'success'); loadLogs(); }
        else { showToast('ลบไม่สำเร็จ: ' + (r?.error || 'Unknown'), 'error'); }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadLogs();
    // Event delegation สำหรับปุ่ม snapshot — กัน XSS ที่อาจเกิดจาก inline onclick
    const tbody = document.querySelector('tbody');
    if (tbody) {
        tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-snap');
            if (btn) showSnapshot(btn.dataset.path, btn.dataset.name, btn.dataset.time);
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
