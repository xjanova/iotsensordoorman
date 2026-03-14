<?php $pageTitle = 'จัดการพนักงาน - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h2 class="text-2xl font-bold">จัดการพนักงาน</h2>
        <p class="text-gray-400 text-sm mt-1">เพิ่ม แก้ไข ลบข้อมูลพนักงานและจัดการสิทธิ์การเข้า-ออก</p>
    </div>
    <button onclick="showAddModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition">
        <i class="fas fa-plus"></i> เพิ่มพนักงาน
    </button>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="glass rounded-xl p-4 stat-card">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-blue-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold" id="statTotal">-</p>
                <p class="text-xs text-gray-400">พนักงานทั้งหมด</p>
            </div>
        </div>
    </div>
    <div class="glass rounded-xl p-4 stat-card">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold" id="statAuthorized">-</p>
                <p class="text-xs text-gray-400">อนุญาตเข้า-ออก</p>
            </div>
        </div>
    </div>
    <div class="glass rounded-xl p-4 stat-card">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-image text-purple-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold" id="statWithPhoto">-</p>
                <p class="text-xs text-gray-400">มีรูปใบหน้า</p>
            </div>
        </div>
    </div>
    <div class="glass rounded-xl p-4 stat-card">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-red-500/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-ban text-red-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold" id="statSuspended">-</p>
                <p class="text-xs text-gray-400">ระงับสิทธิ์</p>
            </div>
        </div>
    </div>
</div>

<!-- Search -->
<div class="glass rounded-2xl p-4 mb-6">
    <div class="flex gap-4">
        <div class="flex-1 relative">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            <input type="text" id="searchInput" placeholder="ค้นหาพนักงาน..." class="w-full bg-white/5 border border-white/10 rounded-lg pl-10 pr-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
        </div>
        <select id="filterDept" class="bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none">
            <option value="">ทุกแผนก</option>
        </select>
    </div>
</div>

<!-- Employee Table -->
<div class="glass rounded-2xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-gray-400 border-b border-white/10 bg-white/5">
                <th class="text-left py-4 px-4">รหัส</th>
                <th class="text-left py-4 px-4">พนักงาน</th>
                <th class="text-left py-4 px-4">แผนก</th>
                <th class="text-left py-4 px-4">ตำแหน่ง</th>
                <th class="text-left py-4 px-4">รูปใบหน้า</th>
                <th class="text-center py-4 px-4">สิทธิ์</th>
                <th class="text-center py-4 px-4">จัดการ</th>
            </tr>
        </thead>
        <tbody id="employeeTable">
            <tr><td colspan="7" class="text-center text-gray-500 py-8">กำลังโหลด...</td></tr>
        </tbody>
    </table>
</div>

<!-- Add/Edit Modal -->
<div id="employeeModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center">
    <div class="glass bg-brand-800 rounded-2xl p-8 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold" id="modalTitle">เพิ่มพนักงาน</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form id="employeeForm" onsubmit="saveEmployee(event)">
            <input type="hidden" id="formId">
            <input type="hidden" id="formFaceImage">

            <div class="flex gap-6">
                <!-- Photo Upload Area -->
                <div class="flex-shrink-0">
                    <div id="photoUploadArea"
                         class="w-40 h-40 rounded-2xl border-2 border-dashed border-white/20 flex flex-col items-center justify-center cursor-pointer hover:border-blue-500/50 hover:bg-blue-500/5 transition-all group relative overflow-hidden"
                         onclick="document.getElementById('photoInput').click()">
                        <!-- Default state -->
                        <div id="photoPlaceholder" class="flex flex-col items-center">
                            <div class="w-14 h-14 bg-white/5 rounded-full flex items-center justify-center mb-2 group-hover:bg-blue-500/10 transition">
                                <i class="fas fa-camera text-2xl text-gray-500 group-hover:text-blue-400 transition"></i>
                            </div>
                            <span class="text-xs text-gray-500 group-hover:text-blue-400 transition">คลิกเพื่ออัพโหลด</span>
                            <span class="text-xs text-gray-600 mt-1">JPG, PNG, WEBP</span>
                        </div>
                        <!-- Preview state -->
                        <img id="photoPreview" class="absolute inset-0 w-full h-full object-cover hidden" alt="Preview">
                        <!-- Overlay on hover when has image -->
                        <div id="photoOverlay" class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 hover:opacity-100 transition hidden">
                            <span class="text-white text-xs font-medium"><i class="fas fa-camera mr-1"></i> เปลี่ยนรูป</span>
                        </div>
                    </div>
                    <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="handlePhotoSelect(this)">

                    <!-- Progress Bar -->
                    <div id="uploadProgress" class="hidden mt-3">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-400" id="uploadStatusText">กำลังอัพโหลด...</span>
                            <span class="text-blue-400 font-mono" id="uploadPercent">0%</span>
                        </div>
                        <div class="w-40 h-1.5 bg-white/10 rounded-full overflow-hidden">
                            <div id="uploadBar" class="h-full bg-gradient-to-r from-blue-500 to-cyan-400 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                    <!-- Upload Result -->
                    <div id="uploadResult" class="hidden mt-2 text-center">
                        <span class="text-xs text-green-400"><i class="fas fa-check-circle mr-1"></i>อัพโหลดสำเร็จ</span>
                    </div>
                    <!-- Face Validation Result -->
                    <div id="faceValidation" class="hidden mt-2 w-40">
                        <div id="faceValidIcon" class="text-center"></div>
                        <p id="faceValidMsg" class="text-xs text-center mt-1 leading-tight"></p>
                    </div>
                </div>

                <!-- Form Fields -->
                <div class="flex-1">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-400 block mb-1">รหัสพนักงาน <span class="text-red-400">*</span></label>
                            <input type="text" id="formEmpCode" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500" placeholder="เช่น EMP005">
                        </div>
                        <div>
                            <label class="text-sm text-gray-400 block mb-1">ชื่อ <span class="text-red-400">*</span></label>
                            <input type="text" id="formFirstName" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="text-sm text-gray-400 block mb-1">นามสกุล <span class="text-red-400">*</span></label>
                            <input type="text" id="formLastName" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="text-sm text-gray-400 block mb-1">แผนก</label>
                            <input type="text" id="formDept" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div class="col-span-2">
                            <label class="text-sm text-gray-400 block mb-1">ตำแหน่ง</label>
                            <input type="text" id="formPosition" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="formAuthorized" checked class="sr-only peer">
                            <div class="w-9 h-5 bg-white/10 rounded-full peer peer-checked:bg-green-500/80 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"></div>
                        </label>
                        <span class="text-sm text-gray-300">อนุญาตเข้า-ออก</span>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" id="btnSave" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg transition font-medium flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i> บันทึก
                </button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-white/10 hover:bg-white/20 text-white py-2.5 rounded-lg transition">ยกเลิก</button>
            </div>
        </form>
    </div>
</div>

<!-- Photo View Modal -->
<div id="photoViewModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center" onclick="closePhotoView()">
    <div class="max-w-lg max-h-[80vh] p-2" onclick="event.stopPropagation()">
        <img id="photoViewImg" class="max-w-full max-h-[75vh] rounded-2xl shadow-2xl" alt="Employee Photo">
        <p class="text-center text-gray-400 text-sm mt-3" id="photoViewName"></p>
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
    select.innerHTML = '<option value="">ทุกแผนก</option>' +
        depts.map(d => `<option value="${esc(d)}">${esc(d)}</option>`).join('');
    select.value = current;
}

function renderTable(employees) {
    const tbody = document.getElementById('employeeTable');
    if (employees.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-gray-500 py-12"><i class="fas fa-users text-3xl mb-3 block opacity-30"></i>ไม่มีข้อมูลพนักงาน</td></tr>';
        return;
    }
    tbody.innerHTML = employees.map(emp => `
        <tr class="border-b border-white/5 hover:bg-white/5 transition">
            <td class="py-3 px-4 font-mono text-blue-400 font-medium">${esc(emp.emp_code)}</td>
            <td class="py-3 px-4">
                <div class="flex items-center gap-3">
                    ${emp.face_image
                        ? `<img src="uploads/faces/${esc(emp.face_image)}" class="w-8 h-8 rounded-full object-cover cursor-pointer hover:ring-2 ring-blue-500 transition" onclick="viewPhoto('uploads/faces/${esc(emp.face_image)}', '${esc(emp.first_name)} ${esc(emp.last_name)}')" onerror="this.outerHTML='<div class=\\'w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400 text-xs font-bold\\'>${esc(emp.first_name).charAt(0)}</div>'">`
                        : `<div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-gray-400 text-xs font-bold">${esc(emp.first_name).charAt(0)}</div>`
                    }
                    <span>${esc(emp.first_name)} ${esc(emp.last_name)}</span>
                </div>
            </td>
            <td class="py-3 px-4 text-gray-400">${esc(emp.department) || '-'}</td>
            <td class="py-3 px-4 text-gray-400">${esc(emp.position) || '-'}</td>
            <td class="py-3 px-4">
                ${emp.face_image
                    ? `<span class="inline-flex items-center gap-1.5 bg-green-500/10 text-green-400 px-2 py-1 rounded-lg text-xs cursor-pointer hover:bg-green-500/20 transition" onclick="viewPhoto('uploads/faces/${esc(emp.face_image)}', '${esc(emp.first_name)} ${esc(emp.last_name)}')"><i class="fas fa-image"></i>${esc(emp.face_image)}</span>`
                    : '<span class="text-gray-600 text-xs"><i class="fas fa-image-slash mr-1"></i>ไม่มีรูป</span>'}
            </td>
            <td class="py-3 px-4 text-center">
                ${emp.is_authorized
                    ? '<span class="bg-green-500/20 text-green-400 px-2.5 py-1 rounded-lg text-xs font-medium">อนุญาต</span>'
                    : '<span class="bg-red-500/20 text-red-400 px-2.5 py-1 rounded-lg text-xs font-medium">ระงับ</span>'}
            </td>
            <td class="py-3 px-4 text-center">
                <div class="flex items-center justify-center gap-1">
                    <button onclick="editEmployee(${parseInt(emp.id)})" class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 transition flex items-center justify-center" title="แก้ไข"><i class="fas fa-edit text-xs"></i></button>
                    <button onclick="deleteEmployee(${parseInt(emp.id)})" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 transition flex items-center justify-center" title="ลบ"><i class="fas fa-trash text-xs"></i></button>
                </div>
            </td>
        </tr>
    `).join('');
}

// ============================================================
// Photo Upload
// ============================================================
function handlePhotoSelect(input) {
    const file = input.files[0];
    if (!file) return;

    // Validate
    const maxSize = 5 * 1024 * 1024;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!allowedTypes.includes(file.type)) {
        showToast('รองรับเฉพาะไฟล์ JPG, PNG, WEBP เท่านั้น', 'error');
        input.value = '';
        return;
    }
    if (file.size > maxSize) {
        showToast('ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 5MB)', 'error');
        input.value = '';
        return;
    }

    // Show preview
    const reader = new FileReader();
    reader.onload = (e) => {
        document.getElementById('photoPreview').src = e.target.result;
        document.getElementById('photoPreview').classList.remove('hidden');
        document.getElementById('photoOverlay').classList.remove('hidden');
        document.getElementById('photoPlaceholder').classList.add('hidden');
    };
    reader.readAsDataURL(file);

    // Upload
    uploadPhoto(file);
}

function uploadPhoto(file) {
    const progressEl = document.getElementById('uploadProgress');
    const barEl = document.getElementById('uploadBar');
    const percentEl = document.getElementById('uploadPercent');
    const statusEl = document.getElementById('uploadStatusText');
    const resultEl = document.getElementById('uploadResult');

    progressEl.classList.remove('hidden');
    resultEl.classList.add('hidden');

    const formData = new FormData();
    formData.append('photo', file);
    formData.append('emp_code', document.getElementById('formEmpCode').value || 'temp');

    const xhr = new XMLHttpRequest();

    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const pct = Math.round((e.loaded / e.total) * 100);
            barEl.style.width = pct + '%';
            percentEl.textContent = pct + '%';
            if (pct < 100) {
                statusEl.textContent = 'กำลังอัพโหลด...';
            } else {
                statusEl.textContent = 'กำลังประมวลผล...';
            }
        }
    });

    xhr.addEventListener('load', () => {
        try {
            const res = JSON.parse(xhr.responseText);
            if (res.success) {
                barEl.style.width = '100%';
                percentEl.textContent = '100%';
                barEl.classList.remove('from-blue-500', 'to-cyan-400');
                barEl.classList.add('from-green-500', 'to-emerald-400');
                statusEl.textContent = 'เสร็จสิ้น';

                setTimeout(() => {
                    progressEl.classList.add('hidden');
                    resultEl.classList.remove('hidden');
                }, 800);

                document.getElementById('formFaceImage').value = res.filename;
                currentUploadedFile = res.filename;

                // Show face validation result
                showFaceValidation(res.face_validation);
            } else {
                barEl.classList.remove('from-blue-500', 'to-cyan-400');
                barEl.classList.add('from-red-500', 'to-red-400');
                statusEl.textContent = res.error || 'อัพโหลดล้มเหลว';
                showToast(res.error || 'อัพโหลดล้มเหลว', 'error');
            }
        } catch {
            statusEl.textContent = 'เกิดข้อผิดพลาด';
            showToast('เกิดข้อผิดพลาดในการอัพโหลด', 'error');
        }
    });

    xhr.addEventListener('error', () => {
        barEl.classList.remove('from-blue-500', 'to-cyan-400');
        barEl.classList.add('from-red-500', 'to-red-400');
        statusEl.textContent = 'การเชื่อมต่อล้มเหลว';
        showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์', 'error');
    });

    xhr.open('POST', 'api/upload.php');
    xhr.send(formData);
}

function showFaceValidation(data) {
    const container = document.getElementById('faceValidation');
    const icon = document.getElementById('faceValidIcon');
    const msg = document.getElementById('faceValidMsg');

    container.classList.remove('hidden');

    if (!data) {
        // Face server offline — show warning
        icon.innerHTML = '<span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-yellow-500/20"><i class="fas fa-exclamation-triangle text-yellow-400 text-xs"></i></span>';
        msg.className = 'text-xs text-center mt-1 leading-tight text-yellow-400';
        msg.textContent = 'ไม่สามารถตรวจสอบใบหน้าได้ (Face Server ออฟไลน์)';
        return;
    }

    if (data.valid) {
        // Face detected successfully
        icon.innerHTML = '<span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-500/20"><i class="fas fa-face-smile text-green-400 text-xs"></i></span>';
        msg.className = 'text-xs text-center mt-1 leading-tight text-green-400';
        let text = 'ตรวจพบใบหน้า ใช้งานได้';
        if (data.face_ratio) text += ` (${data.face_ratio}%)`;
        if (data.quality_notes && data.quality_notes.length > 0) {
            msg.className = 'text-xs text-center mt-1 leading-tight text-yellow-400';
            text = data.quality_notes[0];
            icon.innerHTML = '<span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-yellow-500/20"><i class="fas fa-face-meh text-yellow-400 text-xs"></i></span>';
        }
        msg.textContent = text;
        showToast('ตรวจพบใบหน้าในรูปภาพ สามารถใช้งานได้', 'success');
    } else {
        // No face or multiple faces
        icon.innerHTML = '<span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-500/20"><i class="fas fa-face-frown text-red-400 text-xs"></i></span>';
        msg.className = 'text-xs text-center mt-1 leading-tight text-red-400';
        msg.textContent = data.message || 'ไม่พบใบหน้าในรูปภาพ';
        showToast(data.message || 'ไม่พบใบหน้าในรูปภาพ', 'warning');
    }
}

function viewPhoto(src, name) {
    document.getElementById('photoViewImg').src = src;
    document.getElementById('photoViewName').textContent = name;
    document.getElementById('photoViewModal').classList.remove('hidden');
    document.getElementById('photoViewModal').classList.add('flex');
}

function closePhotoView() {
    document.getElementById('photoViewModal').classList.add('hidden');
    document.getElementById('photoViewModal').classList.remove('flex');
}

// ============================================================
// Modal
// ============================================================
function resetPhotoArea() {
    document.getElementById('photoPreview').classList.add('hidden');
    document.getElementById('photoOverlay').classList.add('hidden');
    document.getElementById('photoPlaceholder').classList.remove('hidden');
    document.getElementById('uploadProgress').classList.add('hidden');
    document.getElementById('uploadResult').classList.add('hidden');
    document.getElementById('uploadBar').style.width = '0%';
    document.getElementById('uploadBar').className = 'h-full bg-gradient-to-r from-blue-500 to-cyan-400 rounded-full transition-all duration-300';
    document.getElementById('photoInput').value = '';
    document.getElementById('faceValidation').classList.add('hidden');
    currentUploadedFile = null;
}

function showAddModal() {
    document.getElementById('modalTitle').textContent = 'เพิ่มพนักงาน';
    document.getElementById('employeeForm').reset();
    document.getElementById('formId').value = '';
    document.getElementById('formFaceImage').value = '';
    document.getElementById('formAuthorized').checked = true;
    resetPhotoArea();
    document.getElementById('employeeModal').classList.remove('hidden');
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

    // Show existing photo
    if (emp.face_image) {
        const preview = document.getElementById('photoPreview');
        preview.src = 'uploads/faces/' + emp.face_image;
        preview.classList.remove('hidden');
        document.getElementById('photoOverlay').classList.remove('hidden');
        document.getElementById('photoPlaceholder').classList.add('hidden');
        document.getElementById('uploadResult').classList.remove('hidden');
    }

    document.getElementById('employeeModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('employeeModal').classList.add('hidden');
}

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
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';

    const result = await postAPI('api/employees.php', data);

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> บันทึก';

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
        `ต้องการลบ "${emp ? emp.first_name + ' ' + emp.last_name : 'พนักงาน'}" หรือไม่? การดำเนินการนี้ไม่สามารถย้อนกลับได้`,
        async () => {
            const res = await fetch('api/employees.php?id=' + id, { method: 'DELETE' });
            const result = await res.json();
            if (result?.success) {
                showToast('ลบพนักงานสำเร็จ', 'success');
                loadEmployees();
            } else {
                showToast(result?.error || 'เกิดข้อผิดพลาด', 'error');
            }
        }
    );
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

// Close modal on backdrop click
document.getElementById('employeeModal').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) closeModal();
});

// Keyboard shortcut
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeModal();
        closePhotoView();
    }
});

loadEmployees();
</script>

<?php include 'includes/footer.php'; ?>
