<?php $pageTitle = 'พนักงาน - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<div class="page-head">
    <div>
        <h1>จัดการพนักงาน</h1>
        <div class="sub">เพิ่ม แก้ไข ลบข้อมูลพนักงานและจัดการสิทธิ์การเข้า-ออก</div>
    </div>
    <div class="page-head-actions">
        <button class="btn primary" onclick="showAddModal()"><?= ico('plus', 14) ?> เพิ่มพนักงาน</button>
    </div>
</div>

<!-- Stats -->
<div class="grid kpis section">
    <div class="card kpi">
        <div class="label"><span class="dot"></span>พนักงานทั้งหมด</div>
        <div class="num" id="statTotal">—</div>
    </div>
    <div class="card kpi">
        <div class="label"><span class="dot ok"></span>อนุญาตเข้า-ออก</div>
        <div class="num" id="statAuthorized">—</div>
    </div>
    <div class="card kpi">
        <div class="label"><span class="dot accent"></span>มีรูปใบหน้า</div>
        <div class="num" id="statWithPhoto">—</div>
    </div>
    <div class="card kpi">
        <div class="label"><span class="dot danger"></span>ระงับสิทธิ์</div>
        <div class="num" id="statSuspended">—</div>
    </div>
</div>

<!-- Search & Filter -->
<div class="card section">
    <div class="row gap-3">
        <div class="search-wrap" style="flex: 1;">
            <?= ico('search', 14) ?>
            <input type="text" id="searchInput" placeholder="ค้นหาพนักงาน (ชื่อ, รหัส)" class="input input-search">
        </div>
        <select id="filterDept" class="select" style="width: 200px;">
            <option value="">ทุกแผนก</option>
        </select>
    </div>
</div>

<!-- Employee Table -->
<div class="card flush section">
    <div style="overflow-x: auto;">
        <table class="tbl">
            <thead>
                <tr>
                    <th>รหัส</th>
                    <th>พนักงาน</th>
                    <th>แผนก</th>
                    <th>ตำแหน่ง</th>
                    <th>รูปใบหน้า</th>
                    <th>สิทธิ์</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody id="employeeTable">
                <tr><td colspan="7" class="empty">กำลังโหลด...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="employeeModal" class="modal-backdrop" style="display:none;">
    <div class="modal" style="max-width: 680px;">
        <div class="modal-head">
            <h3 id="modalTitle">เพิ่มพนักงาน</h3>
            <button onclick="closeModal()" class="icon-btn"><?= ico('x', 14) ?></button>
        </div>
        <form id="employeeForm" onsubmit="saveEmployee(event)">
            <div class="modal-body">
                <input type="hidden" id="formId">
                <input type="hidden" id="formFaceImage">

                <div class="row gap-4" style="align-items: flex-start;">
                    <!-- Photo Upload Area -->
                    <div style="flex-shrink: 0;">
                        <div id="photoUploadArea"
                             style="width: 160px; height: 160px; border-radius: var(--radius-lg); border: 2px dashed var(--border-strong); display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; position: relative; overflow: hidden; background: var(--surface-2);"
                             onclick="document.getElementById('photoInput').click()">
                            <div id="photoPlaceholder" style="display:flex; flex-direction:column; align-items:center; color: var(--text-3);">
                                <?= ico('camera', 28) ?>
                                <span class="tiny" style="margin-top: 6px;">คลิกเพื่ออัพโหลด</span>
                                <span class="tiny muted" style="margin-top: 2px;">JPG, PNG, WEBP</span>
                            </div>
                            <img id="photoPreview" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:none;" alt="Preview">
                        </div>
                        <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="handlePhotoSelect(this)">

                        <button type="button" onclick="openCameraCapture()" class="btn sm" style="width: 160px; margin-top: 8px; justify-content: center; background: var(--info-soft); color: var(--info); border-color: transparent;">
                            <?= ico('video', 13) ?> ถ่ายจากกล้อง Pi
                        </button>

                        <div id="uploadProgress" style="display:none; margin-top: 10px; width: 160px;">
                            <div class="row spread" style="margin-bottom: 4px;">
                                <span class="tiny muted" id="uploadStatusText">กำลังอัพโหลด...</span>
                                <span class="tiny mono text-accent" id="uploadPercent">0%</span>
                            </div>
                            <div style="width:100%; height: 4px; background: var(--border); border-radius: 999px; overflow: hidden;">
                                <div id="uploadBar" style="height: 100%; background: var(--accent); width: 0%; transition: width 0.3s;"></div>
                            </div>
                        </div>
                        <div id="uploadResult" class="tiny text-ok" style="display:none; margin-top: 6px; text-align:center;"><?= ico('check', 12) ?> อัพโหลดสำเร็จ</div>
                        <div id="faceValidation" style="display:none; margin-top: 8px; width: 160px;">
                            <div id="faceValidIcon" style="text-align:center;"></div>
                            <p id="faceValidMsg" class="tiny" style="text-align:center; margin: 4px 0 0; line-height: 1.3;"></p>
                        </div>
                    </div>

                    <!-- Form Fields -->
                    <div style="flex: 1;">
                        <div class="grid cols-2" style="gap: 14px;">
                            <div>
                                <label class="tiny muted" style="display:block; margin-bottom: 4px;">รหัสพนักงาน <span class="text-danger">*</span></label>
                                <input type="text" id="formEmpCode" required class="input" placeholder="เช่น EMP005">
                            </div>
                            <div>
                                <label class="tiny muted" style="display:block; margin-bottom: 4px;">ชื่อ <span class="text-danger">*</span></label>
                                <input type="text" id="formFirstName" required class="input">
                            </div>
                            <div>
                                <label class="tiny muted" style="display:block; margin-bottom: 4px;">นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" id="formLastName" required class="input">
                            </div>
                            <div>
                                <label class="tiny muted" style="display:block; margin-bottom: 4px;">แผนก</label>
                                <input type="text" id="formDept" class="input">
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <label class="tiny muted" style="display:block; margin-bottom: 4px;">ตำแหน่ง</label>
                                <input type="text" id="formPosition" class="input">
                            </div>
                        </div>
                        <label class="row gap-2" style="margin-top: 14px; cursor: pointer;">
                            <input type="checkbox" id="formAuthorized" checked>
                            <span style="font-size: 13px;">อนุญาตเข้า-ออก</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" onclick="closeModal()" class="btn">ยกเลิก</button>
                <button type="submit" id="btnSave" class="btn primary"><?= ico('save', 14) ?> บันทึก</button>
            </div>
        </form>
    </div>
</div>

<!-- Camera Capture Modal -->
<div id="cameraModal" class="modal-backdrop" style="display:none;">
    <div class="modal" style="max-width: 540px;">
        <div class="modal-head">
            <h3>ถ่ายภาพจากกล้อง Pi</h3>
            <button onclick="closeCameraCapture()" class="icon-btn"><?= ico('x', 14) ?></button>
        </div>
        <div class="modal-body">
            <div class="row gap-2" style="margin-bottom: 12px;">
                <button id="camBtnOutside" onclick="switchCaptureCamera('outside')" class="btn primary sm" style="flex: 1; justify-content: center;">กล้องนอก</button>
                <button id="camBtnInside" onclick="switchCaptureCamera('inside')" class="btn sm" style="flex: 1; justify-content: center;">กล้องใน</button>
            </div>
            <div class="cam" style="margin-bottom: 12px;">
                <img id="capturePreview" alt="Camera Preview" style="display:block;">
                <div id="captureLoading" class="cam-feed">CONNECTING...</div>
                <div class="cam-overlay">
                    <div></div>
                    <div class="row" style="justify-content: center;">
                        <span class="cam-chip">หันหน้าตรง · 30-50 ซม.</span>
                    </div>
                </div>
            </div>
            <div id="captureResult" style="display:none; padding: 10px; border-radius: var(--radius-md); margin-bottom: 12px; text-align: center;"></div>
        </div>
        <div class="modal-foot">
            <button onclick="closeCameraCapture()" class="btn">ยกเลิก</button>
            <button id="btnCapture" onclick="capturePhoto()" class="btn primary"><?= ico('camera', 14) ?> ถ่ายภาพ</button>
        </div>
    </div>
</div>

<!-- Photo View Modal -->
<div id="photoViewModal" class="modal-backdrop" style="display:none;">
    <div class="modal" style="max-width: 600px; background: transparent; border: 0; box-shadow: none;">
        <img id="photoViewImg" style="max-width: 100%; max-height: 75vh; border-radius: var(--radius-lg); display: block; margin: 0 auto;" alt="Employee Photo">
        <p id="photoViewName" class="tiny muted" style="text-align: center; margin-top: 12px;"></p>
    </div>
</div>

<script>
let allEmployees = [];
let currentUploadedFile = null;

// ============================================================
// Load & Render
// ============================================================
async function loadEmployees() {
    const data = await fetchAPI('api/employees.php');
    if (!data) return;
    allEmployees = data;
    updateStats(data);
    renderTable(data);
    updateDeptFilter(data);
}
function updateStats(employees) {
    document.getElementById('statTotal').textContent = employees.length;
    document.getElementById('statAuthorized').textContent = employees.filter(e => e.is_authorized).length;
    document.getElementById('statWithPhoto').textContent = employees.filter(e => e.face_image).length;
    document.getElementById('statSuspended').textContent = employees.filter(e => !e.is_authorized).length;
}
function updateDeptFilter(employees) {
    const depts = [...new Set(employees.map(e => e.department).filter(Boolean))];
    const select = document.getElementById('filterDept');
    const current = select.value;
    select.innerHTML = '<option value="">ทุกแผนก</option>' + depts.map(d => `<option value="${esc(d)}">${esc(d)}</option>`).join('');
    select.value = current;
}
function renderTable(employees) {
    const tbody = document.getElementById('employeeTable');
    if (employees.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty"><div style="padding: 28px;">ไม่มีข้อมูลพนักงาน</div></td></tr>';
        return;
    }
    tbody.innerHTML = employees.map(emp => {
        const initials = esc(emp.first_name).charAt(0);
        const fullName = esc(emp.first_name) + ' ' + esc(emp.last_name);
        const photoData = emp.face_image
            ? `data-photo="uploads/faces/${esc(emp.face_image)}" data-name="${fullName}"`
            : '';
        const avatarHtml = emp.face_image
            ? `<img src="uploads/faces/${esc(emp.face_image)}" class="emp-photo" ${photoData} style="width:34px; height:34px; border-radius:50%; object-fit:cover; cursor:pointer;" onerror="this.outerHTML='<div class=\\'avatar\\'>${initials}</div>'">`
            : `<div class="avatar">${initials}</div>`;
        const auth = emp.is_authorized
            ? '<span class="badge ok"><span class="dot"></span>อนุญาต</span>'
            : '<span class="badge danger"><span class="dot"></span>ระงับ</span>';
        const faceCol = emp.face_image
            ? `<span class="badge accent emp-photo" ${photoData} style="cursor:pointer;">${esc(emp.face_image)}</span>`
            : '<span class="muted tiny">ไม่มีรูป</span>';
        return `<tr>
            <td class="mono text-accent">${esc(emp.emp_code)}</td>
            <td>
                <div class="row gap-2" style="align-items: center;">${avatarHtml}<span>${fullName}</span></div>
            </td>
            <td class="muted">${esc(emp.department) || '-'}</td>
            <td class="muted">${esc(emp.position) || '-'}</td>
            <td>${faceCol}</td>
            <td>${auth}</td>
            <td>
                <div class="row gap-2">
                    <button class="icon-btn btn-edit-emp" data-id="${parseInt(emp.id)}" title="แก้ไข"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                    <button class="icon-btn danger btn-del-emp" data-id="${parseInt(emp.id)}" title="ลบ"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ============================================================
// Photo Upload
// ============================================================
function handlePhotoSelect(input) {
    const file = input.files[0];
    if (!file) return;
    const maxSize = 5 * 1024 * 1024;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) { showToast('รองรับเฉพาะ JPG, PNG, WEBP', 'error'); input.value = ''; return; }
    if (file.size > maxSize) { showToast('ไฟล์ใหญ่เกิน 5MB', 'error'); input.value = ''; return; }
    const reader = new FileReader();
    reader.onload = (e) => {
        document.getElementById('photoPreview').src = e.target.result;
        document.getElementById('photoPreview').style.display = '';
        document.getElementById('photoPlaceholder').style.display = 'none';
    };
    reader.readAsDataURL(file);
    uploadPhoto(file);
}
function uploadPhoto(file) {
    const progressEl = document.getElementById('uploadProgress');
    const barEl = document.getElementById('uploadBar');
    const percentEl = document.getElementById('uploadPercent');
    const statusEl = document.getElementById('uploadStatusText');
    const resultEl = document.getElementById('uploadResult');
    progressEl.style.display = '';
    resultEl.style.display = 'none';

    const formData = new FormData();
    formData.append('photo', file);
    formData.append('emp_code', document.getElementById('formEmpCode').value || 'temp');

    const xhr = new XMLHttpRequest();
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const pct = Math.round((e.loaded / e.total) * 100);
            barEl.style.width = pct + '%';
            percentEl.textContent = pct + '%';
            statusEl.textContent = pct < 100 ? 'กำลังอัพโหลด...' : 'กำลังประมวลผล...';
        }
    });
    xhr.addEventListener('load', () => {
        try {
            const res = JSON.parse(xhr.responseText);
            if (res.success) {
                barEl.style.width = '100%'; percentEl.textContent = '100%';
                statusEl.textContent = 'เสร็จสิ้น';
                barEl.style.background = 'var(--ok)';
                setTimeout(() => { progressEl.style.display = 'none'; resultEl.style.display = ''; }, 800);
                document.getElementById('formFaceImage').value = res.filename;
                currentUploadedFile = res.filename;
                showFaceValidation(res.face_validation);
            } else {
                barEl.style.background = 'var(--danger)';
                statusEl.textContent = res.error || 'อัพโหลดล้มเหลว';
                showToast(res.error || 'อัพโหลดล้มเหลว', 'error');
            }
        } catch { statusEl.textContent = 'เกิดข้อผิดพลาด'; showToast('เกิดข้อผิดพลาด', 'error'); }
    });
    xhr.addEventListener('error', () => {
        barEl.style.background = 'var(--danger)';
        statusEl.textContent = 'การเชื่อมต่อล้มเหลว';
        showToast('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้', 'error');
    });
    xhr.open('POST', 'api/upload.php');
    xhr.send(formData);
}
function showFaceValidation(data) {
    const container = document.getElementById('faceValidation');
    const icon = document.getElementById('faceValidIcon');
    const msg = document.getElementById('faceValidMsg');
    container.style.display = '';
    if (!data) {
        icon.innerHTML = '<span class="badge warn">!</span>';
        msg.className = 'tiny text-warn';
        msg.textContent = 'ไม่สามารถตรวจสอบใบหน้าได้';
        return;
    }
    if (data.valid) {
        const ratio = data.face_ratio || 0;
        if (ratio < 3) {
            icon.innerHTML = '<span class="badge danger">×</span>';
            msg.className = 'tiny text-danger';
            msg.textContent = `ใบหน้าเล็กเกินไป (${ratio}%) ถ่ายใกล้กว่านี้`;
            showToast('รูปใช้ไม่ได้ — ใบหน้าเล็ก', 'error');
        } else if (ratio < 8 || (data.quality_notes && data.quality_notes.length > 0)) {
            icon.innerHTML = '<span class="badge warn">!</span>';
            msg.className = 'tiny text-warn';
            msg.textContent = data.quality_notes?.[0] || `ใบหน้าค่อนข้างเล็ก (${ratio}%)`;
            showToast('รูปใช้ได้ แต่คุณภาพไม่ดีนัก', 'warning');
        } else {
            icon.innerHTML = '<span class="badge ok">✓</span>';
            msg.className = 'tiny text-ok';
            msg.textContent = `ใบหน้าชัดเจน (${ratio}%)`;
            showToast('รูปใบหน้าคุณภาพดี!', 'success');
        }
    } else {
        icon.innerHTML = '<span class="badge danger">×</span>';
        msg.className = 'tiny text-danger';
        msg.textContent = data.message || 'ไม่พบใบหน้าในรูปภาพ';
        showToast(data.message || 'ไม่พบใบหน้า', 'warning');
    }
}
function viewPhoto(src, name) {
    document.getElementById('photoViewImg').src = src;
    document.getElementById('photoViewName').textContent = name;
    document.getElementById('photoViewModal').style.display = 'flex';
}
document.getElementById('photoViewModal').addEventListener('click', e => { if (e.target === e.currentTarget) closePhotoView(); });
function closePhotoView() { document.getElementById('photoViewModal').style.display = 'none'; }

// ============================================================
// Modal
// ============================================================
function resetPhotoArea() {
    document.getElementById('photoPreview').style.display = 'none';
    document.getElementById('photoPlaceholder').style.display = '';
    document.getElementById('uploadProgress').style.display = 'none';
    document.getElementById('uploadResult').style.display = 'none';
    document.getElementById('uploadBar').style.width = '0%';
    document.getElementById('uploadBar').style.background = 'var(--accent)';
    document.getElementById('photoInput').value = '';
    document.getElementById('faceValidation').style.display = 'none';
    currentUploadedFile = null;
}
async function showAddModal() {
    document.getElementById('modalTitle').textContent = 'เพิ่มพนักงาน';
    document.getElementById('employeeForm').reset();
    document.getElementById('formId').value = '';
    document.getElementById('formFaceImage').value = '';
    document.getElementById('formAuthorized').checked = true;
    resetPhotoArea();
    try { const res = await fetchAPI('api/employees.php?next_code=1'); if (res?.next_code) document.getElementById('formEmpCode').value = res.next_code; } catch {}
    document.getElementById('employeeModal').style.display = 'flex';
}
function editEmployee(id) {
    const emp = allEmployees.find(e => e.id == id);
    if (!emp) return;
    document.getElementById('modalTitle').textContent = 'แก้ไขพนักงาน';
    document.getElementById('formId').value = emp.id;
    document.getElementById('formEmpCode').value = emp.emp_code;
    document.getElementById('formFirstName').value = emp.first_name;
    document.getElementById('formLastName').value = emp.last_name;
    document.getElementById('formDept').value = emp.department || '';
    document.getElementById('formPosition').value = emp.position || '';
    document.getElementById('formFaceImage').value = emp.face_image || '';
    document.getElementById('formAuthorized').checked = !!emp.is_authorized;
    resetPhotoArea();
    if (emp.face_image) {
        const preview = document.getElementById('photoPreview');
        preview.src = 'uploads/faces/' + emp.face_image;
        preview.style.display = '';
        document.getElementById('photoPlaceholder').style.display = 'none';
        document.getElementById('uploadResult').style.display = '';
    }
    document.getElementById('employeeModal').style.display = 'flex';
}
function closeModal() { document.getElementById('employeeModal').style.display = 'none'; }
document.getElementById('employeeModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });

// ============================================================
// CRUD
// ============================================================
async function saveEmployee(e) {
    e.preventDefault();
    const id = document.getElementById('formId').value;
    const data = {
        emp_code: document.getElementById('formEmpCode').value,
        first_name: document.getElementById('formFirstName').value,
        last_name: document.getElementById('formLastName').value,
        department: document.getElementById('formDept').value,
        position: document.getElementById('formPosition').value,
        face_image: document.getElementById('formFaceImage').value,
        is_authorized: document.getElementById('formAuthorized').checked ? 1 : 0,
    };
    if (id) data.id = id;
    const btn = document.getElementById('btnSave');
    btn.disabled = true;
    const result = await postAPI('api/employees.php', data);
    btn.disabled = false;
    if (result?.success) {
        closeModal();
        showToast(id ? 'แก้ไขข้อมูลพนักงานสำเร็จ' : 'เพิ่มพนักงานสำเร็จ', 'success');
        loadEmployees();
    } else {
        showToast(result?.error || 'เกิดข้อผิดพลาด', 'error');
    }
}
function deleteEmployee(id) {
    const emp = allEmployees.find(e => e.id == id);
    showConfirm(
        'ลบพนักงาน',
        `ต้องการลบ "${emp ? emp.first_name + ' ' + emp.last_name : 'พนักงาน'}" หรือไม่?`,
        async () => {
            const res = await fetch('api/employees.php?id=' + id, { method: 'DELETE' });
            const result = await res.json();
            if (result?.success) { showToast('ลบพนักงานสำเร็จ', 'success'); loadEmployees(); }
            else { showToast(result?.error || 'เกิดข้อผิดพลาด', 'error'); }
        }
    );
}

// ============================================================
// Camera Capture
// ============================================================
let _captureCamera = 'outside';
let _captureInterval = null;
function openCameraCapture() {
    document.getElementById('cameraModal').style.display = 'flex';
    document.getElementById('captureResult').style.display = 'none';
    _captureCamera = 'outside';
    switchCaptureCamera('outside');
    startCapturePreview();
}
function closeCameraCapture() {
    document.getElementById('cameraModal').style.display = 'none';
    stopCapturePreview();
}
document.getElementById('cameraModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeCameraCapture(); });
function switchCaptureCamera(cam) {
    _captureCamera = cam;
    const btnOut = document.getElementById('camBtnOutside');
    const btnIn = document.getElementById('camBtnInside');
    btnOut.className = 'btn sm' + (cam === 'outside' ? ' primary' : '');
    btnIn.className  = 'btn sm' + (cam === 'inside'  ? ' primary' : '');
    btnOut.style.flex = '1'; btnIn.style.flex = '1';
    btnOut.style.justifyContent = 'center'; btnIn.style.justifyContent = 'center';
    document.getElementById('captureLoading').style.display = '';
}
function startCapturePreview() { stopCapturePreview(); refreshCapturePreview(); _captureInterval = setInterval(refreshCapturePreview, 2000); }
function stopCapturePreview() { if (_captureInterval) { clearInterval(_captureInterval); _captureInterval = null; } }
function refreshCapturePreview() {
    const img = new Image();
    img.onload = () => {
        document.getElementById('capturePreview').src = img.src;
        document.getElementById('captureLoading').style.display = 'none';
    };
    img.onerror = () => {
        document.getElementById('captureLoading').style.display = '';
        document.getElementById('captureLoading').textContent = 'CAMERA NOT READY';
    };
    img.src = 'api/capture.php?camera=' + _captureCamera + '&t=' + Date.now();
}
async function capturePhoto() {
    const btn = document.getElementById('btnCapture');
    const result = document.getElementById('captureResult');
    btn.disabled = true;
    btn.innerHTML = 'กำลังถ่ายภาพ...';
    result.style.display = 'none';
    try {
        const empCode = document.getElementById('formEmpCode').value || 'capture';
        const resp = await fetch('api/capture.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ camera: _captureCamera, emp_code: empCode }) });
        const data = await resp.json();
        if (data.success && data.filename) {
            document.getElementById('formFaceImage').value = data.filename;
            currentUploadedFile = data.filename;
            const preview = document.getElementById('photoPreview');
            preview.src = 'uploads/faces/' + data.filename + '?t=' + Date.now();
            preview.style.display = '';
            document.getElementById('photoPlaceholder').style.display = 'none';
            document.getElementById('uploadResult').style.display = '';
            showFaceValidation({ valid: true, face_ratio: data.face_ratio, quality_notes: data.quality_notes || [] });
            result.innerHTML = '<span class="text-ok">ถ่ายภาพสำเร็จ! ใบหน้า ' + data.face_ratio + '%</span>';
            result.style.background = 'var(--ok-soft)';
            result.style.display = '';
            showToast('ถ่ายภาพจากกล้องสำเร็จ!', 'success');
            setTimeout(() => closeCameraCapture(), 1500);
        } else {
            result.innerHTML = '<span class="text-danger">' + (data.error || 'เกิดข้อผิดพลาด') + '</span>';
            result.style.background = 'var(--danger-soft)';
            result.style.display = '';
            showToast(data.error || 'ถ่ายภาพไม่สำเร็จ', 'error');
        }
    } catch (e) {
        result.innerHTML = '<span class="text-danger">ไม่สามารถเชื่อมต่อ Face Server</span>';
        result.style.background = 'var(--danger-soft)';
        result.style.display = '';
        showToast('เชื่อมต่อ Face Server ไม่ได้', 'error');
    }
    btn.disabled = false;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h4l2-2h6l2 2h4v12H3z"/><circle cx="12" cy="13" r="3.5"/></svg> ถ่ายภาพ';
}

// ============================================================
// Search & Filter
// ============================================================
function applyFilters() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const dept = document.getElementById('filterDept').value;
    const filtered = allEmployees.filter(e => {
        const matchSearch = !q || e.emp_code.toLowerCase().includes(q) || e.first_name.toLowerCase().includes(q) || e.last_name.toLowerCase().includes(q);
        const matchDept = !dept || e.department === dept;
        return matchSearch && matchDept;
    });
    renderTable(filtered);
}
document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('filterDept').addEventListener('change', applyFilters);

document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { closeModal(); closePhotoView(); closeCameraCapture(); }});
document.addEventListener('DOMContentLoaded', () => {
    loadEmployees();
    // Event delegation — กัน XSS จาก inline onclick (face_image/name มาจาก DB)
    const tbody = document.getElementById('employeeTable');
    if (tbody) {
        tbody.addEventListener('click', (e) => {
            const photo = e.target.closest('.emp-photo');
            if (photo) { viewPhoto(photo.dataset.photo, photo.dataset.name); return; }
            const ed = e.target.closest('.btn-edit-emp');
            if (ed) { editEmployee(parseInt(ed.dataset.id, 10)); return; }
            const del = e.target.closest('.btn-del-emp');
            if (del) { deleteEmployee(parseInt(del.dataset.id, 10)); }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
