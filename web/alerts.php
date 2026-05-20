<?php $pageTitle = 'การแจ้งเตือน - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<div class="page-head">
    <div>
        <h1>การแจ้งเตือนความผิดปกติ</h1>
        <div class="sub">ตรวจสอบเหตุการณ์ผิดปกติ — Tailgating, ใบหน้าไม่รู้จัก, MULTI_PERSON</div>
    </div>
    <div class="page-head-actions">
        <button class="btn success sm" onclick="resolveAllFiltered()"><?= ico('check', 14) ?> ดำเนินการทั้งหมด</button>
    </div>
</div>

<!-- Severity KPIs -->
<div class="grid kpis section">
    <div class="card kpi">
        <div class="label"><span class="dot danger"></span>วิกฤต</div>
        <div class="num" id="statCritical">—</div>
    </div>
    <div class="card kpi">
        <div class="label"><span class="dot warn"></span>สูง</div>
        <div class="num" id="statHigh">—</div>
    </div>
    <div class="card kpi">
        <div class="label"><span class="dot info"></span>ปานกลาง+ต่ำ</div>
        <div class="num" id="statMed">—</div>
    </div>
    <div class="card kpi">
        <div class="label"><span class="dot ok"></span>ดำเนินการแล้ว</div>
        <div class="num" id="statResolved">—</div>
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
            <label class="tiny muted" style="display:block; margin-bottom:4px;">สถานะ</label>
            <select id="filterStatus" onchange="applyFilters()" class="select">
                <option value="0">ยังไม่ดำเนินการ</option>
                <option value="">ทั้งหมด</option>
                <option value="1">ดำเนินการแล้ว</option>
            </select>
        </div>
        <div>
            <label class="tiny muted" style="display:block; margin-bottom:4px;">ประเภท</label>
            <select id="filterType" onchange="applyFilters()" class="select">
                <option value="all">ทั้งหมด</option>
                <option value="UNKNOWN_FACE">ใบหน้าไม่รู้จัก</option>
                <option value="TAILGATING">Tailgating</option>
                <option value="FORCED_ENTRY">บุกรุก</option>
                <option value="SENSOR_MISMATCH">เซ็นเซอร์ไม่ตรง</option>
                <option value="MULTI_PERSON">หลายคน</option>
                <option value="NO_FACE_DETECTED">ไม่พบใบหน้า</option>
            </select>
        </div>
        <div>
            <label class="tiny muted" style="display:block; margin-bottom:4px;">ระดับ</label>
            <select id="filterSeverity" onchange="applyFilters()" class="select">
                <option value="all">ทั้งหมด</option>
                <option value="CRITICAL">วิกฤต</option>
                <option value="HIGH">สูง</option>
                <option value="MEDIUM">ปานกลาง</option>
                <option value="LOW">ต่ำ</option>
            </select>
        </div>
        <div>
            <label class="tiny muted" style="display:block; margin-bottom:4px;">ตั้งแต่วันที่</label>
            <input type="date" id="filterDateFrom" onchange="applyFilters()" class="input">
        </div>
        <div>
            <label class="tiny muted" style="display:block; margin-bottom:4px;">ถึงวันที่</label>
            <input type="date" id="filterDateTo" onchange="applyFilters()" class="input">
        </div>
        <div>
            <label class="tiny muted" style="display:block; margin-bottom:4px;">ค้นหา</label>
            <div class="search-wrap"><?= ico('search', 14) ?><input type="text" id="filterSearch" placeholder="รายละเอียด..." class="input input-search" onkeypress="if(event.key==='Enter')applyFilters()"></div>
        </div>
    </div>
    <div class="row" style="margin-top: 14px; justify-content: space-between;">
        <div class="row gap-2">
            <button class="btn sm" onclick="quickFilter('CRITICAL')">วิกฤตเท่านั้น</button>
            <button class="btn sm" onclick="quickFilter('UNKNOWN_FACE')">ใบหน้าไม่รู้จัก</button>
            <button class="btn sm" onclick="quickFilter('today')">วันนี้</button>
        </div>
        <button class="btn sm ghost" onclick="resetFilters()"><?= ico('refresh', 12) ?> รีเซ็ต</button>
    </div>
</div>

<!-- Summary + delete actions -->
<div class="row spread" style="margin-bottom: 14px;">
    <span class="tiny muted" id="alertSummary">กำลังโหลด...</span>
    <div class="row gap-2" id="deleteActions" style="display:none;">
        <span class="tiny muted" id="selectedCount"></span>
        <button class="btn sm danger" onclick="deleteSelected()"><?= ico('trash', 12) ?> ลบที่เลือก</button>
        <button class="btn sm ghost" onclick="deleteAllAlerts()"><?= ico('trash', 12) ?> ลบทั้งหมด</button>
    </div>
</div>

<!-- Alert List -->
<div class="card flush">
    <div id="alertsList">
        <div class="empty">กำลังโหลด...</div>
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
                <h3 id="snapTitle">รูปถ่าย</h3>
                <div class="tiny muted" id="snapSubtitle"></div>
            </div>
            <button onclick="closeSnapshot()" class="icon-btn"><?= ico('x', 14) ?></button>
        </div>
        <div style="background: #000; min-height: 320px; display: flex; align-items: center; justify-content: center; padding: 8px;">
            <img id="snapImage" src="" alt="Snapshot" style="max-width:100%; max-height:70vh; object-fit:contain;">
            <div id="snapLoading" class="empty">กำลังโหลดรูป...</div>
        </div>
    </div>
</div>

<script>
const severitySlug = { LOW: 'low', MEDIUM: 'medium', HIGH: 'high', CRITICAL: 'critical' };
const severityBadge = { LOW: 'info', MEDIUM: 'warn', HIGH: 'warn', CRITICAL: 'danger' };
const severityThai  = { LOW: 'ต่ำ', MEDIUM: 'ปานกลาง', HIGH: 'สูง', CRITICAL: 'วิกฤต' };
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

async function loadStats() {
    // นับจาก DB ทั้งหมด (ไม่ขึ้นกับ filter)
    const s = await fetchAPI('api/alerts.php?action=stats');
    if (!s) return;
    document.getElementById('statCritical').textContent = s.critical ?? 0;
    document.getElementById('statHigh').textContent     = s.high ?? 0;
    document.getElementById('statMed').textContent      = s.med_low ?? 0;
    document.getElementById('statResolved').textContent = s.resolved ?? 0;
}

async function loadAlerts() {
    const container = document.getElementById('alertsList');
    container.innerHTML = '<div class="empty">กำลังโหลด...</div>';
    const data = await fetchAPI('api/alerts.php?' + getFilterParams());
    if (!data || !data.data) { container.innerHTML = '<div class="empty text-danger">เกิดข้อผิดพลาด</div>'; return; }

    const alerts = data.data;
    const pag = data.pagination;
    totalPages = pag.total_pages;

    document.getElementById('alertSummary').textContent = `แสดง ${alerts.length} จาก ${pag.total} รายการ (หน้า ${pag.page}/${pag.total_pages})`;

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

    if (alerts.length === 0) {
        container.innerHTML = '<div class="empty"><div style="font-size:32px; color:var(--ok); margin-bottom:8px;">✓</div>ไม่มีการแจ้งเตือน</div>';
        document.getElementById('deleteActions').style.display = 'none';
        return;
    }

    document.getElementById('deleteActions').style.display = 'flex';
    updateSelectedCount();

    container.innerHTML = alerts.map(alert => {
        const sev = severitySlug[alert.severity] || 'low';
        const sevBadge = severityBadge[alert.severity] || 'info';
        const sevText  = severityThai[alert.severity] || alert.severity;
        const tName    = typeThai[alert.alert_type] || alert.alert_type;
        const resolved = alert.is_resolved == 1;
        const cam = alert.camera_id ? `<span>cam-${alert.camera_id == 1 ? 'out' : 'in'}</span>` : '';
        const snapBtn = alert.snapshot_path
            ? `<button class="btn sm" onclick="showSnapshot('${esc(alert.snapshot_path)}', '${esc(tName)}', '${formatDateTime(alert.created_at)}')"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></svg> ดูรูป</button>`
            : '';
        const actBtn = resolved
            ? '<span class="badge ok"><span class="dot"></span>แก้ไขแล้ว</span>'
            : `<button class="btn success sm" onclick="resolveAlert(${alert.id})"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-11"/></svg> ดำเนินการ</button>`;

        return `<div class="alert-row sev-${sev} ${resolved ? 'resolved' : ''}" data-alert-id="${alert.id}">
            <div class="strip"></div>
            <div>
                <div class="head">
                    <input type="checkbox" class="alert-check" value="${alert.id}" onchange="updateSelectedCount()">
                    <strong>${tName}</strong>
                    <span class="badge ${sevBadge}"><span class="dot"></span>${sevText}</span>
                    ${cam ? `<span class="mono tiny muted">${cam}</span>` : ''}
                </div>
                <div class="desc">${esc(alert.description) || '-'}</div>
                <div class="meta">
                    <span>#${alert.id}</span>
                    <span>${formatDateTime(alert.created_at)}</span>
                    ${resolved && alert.resolved_at ? '<span class="text-ok">แก้ไขเมื่อ ' + formatDateTime(alert.resolved_at) + '</span>' : ''}
                </div>
            </div>
            <div class="right">
                <span class="ts">${new Date(alert.created_at).toLocaleTimeString('th-TH', {hour:'2-digit',minute:'2-digit'})}</span>
                <div class="row gap-2">${snapBtn}${actBtn}</div>
            </div>
        </div>`;
    }).join('');
}

async function resolveAlert(id) {
    const res = await postAPI('api/alerts.php', { resolve_id: id });
    if (res?.success) showToast('ดำเนินการสำเร็จ', 'success');
    loadAlerts(); loadStats();
}
async function resolveAllFiltered() {
    const type = document.getElementById('filterType').value;
    showConfirm('ยืนยันดำเนินการทั้งหมด?', 'จะทำเครื่องหมายว่าแก้ไขแล้วทั้งหมดที่ยังไม่ดำเนินการ', async () => {
        const res = await postAPI('api/alerts.php', { resolve_all: true, type: type });
        if (res?.success) showToast(`ดำเนินการ ${res.resolved} รายการ`, 'success');
        loadAlerts(); loadStats();
    });
}
function applyFilters() { currentPage = 1; loadAlerts(); }
function goPage(delta) { currentPage = Math.max(1, Math.min(totalPages, currentPage + delta)); loadAlerts(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
function resetFilters() {
    document.getElementById('filterStatus').value = '0';
    document.getElementById('filterType').value = 'all';
    document.getElementById('filterSeverity').value = 'all';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    document.getElementById('filterSearch').value = '';
    currentPage = 1; loadAlerts();
}
function quickFilter(preset) {
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterType').value = 'all';
    document.getElementById('filterSeverity').value = 'all';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    document.getElementById('filterSearch').value = '';
    if (preset === 'CRITICAL') document.getElementById('filterSeverity').value = 'CRITICAL';
    else if (preset === 'UNKNOWN_FACE') document.getElementById('filterType').value = 'UNKNOWN_FACE';
    else if (preset === 'today') {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('filterDateFrom').value = today;
        document.getElementById('filterDateTo').value = today;
    }
    applyFilters();
}

// Snapshot Modal
function showSnapshot(path, title, time) {
    const modal = document.getElementById('snapshotModal');
    const img = document.getElementById('snapImage');
    const loading = document.getElementById('snapLoading');
    document.getElementById('snapTitle').textContent = title || 'รูปถ่าย';
    document.getElementById('snapSubtitle').textContent = time || '';
    img.style.display = 'none'; loading.style.display = '';
    modal.style.display = 'flex';
    const ts = Date.now();
    if (path.includes('/')) tryLoadImage(path + '?t=' + ts, img, loading, null);
    else tryLoadImage('snapshots/' + path + '?t=' + ts, img, loading, FACE_SERVER + '/api/snapshots/' + path + '?t=' + ts);
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
        } else { loadingEl.innerHTML = '<div class="text-danger">ไม่พบรูปภาพ</div>'; }
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
function updateSelectedCount() {
    const c = document.querySelectorAll('.alert-check:checked').length;
    document.getElementById('selectedCount').textContent = c > 0 ? c + ' รายการ' : '';
}
async function deleteSelected() {
    const ids = Array.from(document.querySelectorAll('.alert-check:checked')).map(cb => parseInt(cb.value));
    if (ids.length === 0) return;
    showConfirm('ลบรายการที่เลือก', `ต้องการลบ ${ids.length} รายการ?`, async () => {
        const r = await postAPI('api/alerts.php?action=delete', { ids });
        if (r?.success) { showToast(`ลบ ${r.deleted} รายการ`, 'success'); loadAlerts(); loadStats(); }
    });
}
async function deleteAllAlerts() {
    showConfirm('ลบทั้งหมด', 'ต้องการลบการแจ้งเตือนทั้งหมด?', async () => {
        const r = await postAPI('api/alerts.php?action=delete', { all: true });
        if (r?.success) { showToast(`ลบ ${r.deleted} รายการ`, 'success'); loadAlerts(); loadStats(); }
    });
}

document.addEventListener('DOMContentLoaded', () => { loadStats(); loadAlerts(); });
</script>

<?php include 'includes/footer.php'; ?>
