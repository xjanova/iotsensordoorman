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
                <th class="text-left py-4 px-4">ชื่อ-นามสกุล</th>
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
    <div class="glass bg-brand-800 rounded-2xl p-8 w-full max-w-lg mx-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold" id="modalTitle">เพิ่มพนักงาน</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form id="employeeForm" onsubmit="saveEmployee(event)">
            <input type="hidden" id="formId">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-gray-400 block mb-1">รหัสพนักงาน</label>
                    <input type="text" id="formEmpCode" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="text-sm text-gray-400 block mb-1">ชื่อ</label>
                    <input type="text" id="formFirstName" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="text-sm text-gray-400 block mb-1">นามสกุล</label>
                    <input type="text" id="formLastName" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="text-sm text-gray-400 block mb-1">แผนก</label>
                    <input type="text" id="formDept" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="text-sm text-gray-400 block mb-1">ตำแหน่ง</label>
                    <input type="text" id="formPosition" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="text-sm text-gray-400 block mb-1">ชื่อไฟล์รูป</label>
                    <input type="text" id="formFaceImage" placeholder="name.jpg" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
            </div>
            <div class="flex items-center gap-2 mt-4">
                <input type="checkbox" id="formAuthorized" checked class="rounded">
                <label for="formAuthorized" class="text-sm text-gray-300">อนุญาตเข้า-ออก</label>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition">บันทึก</button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg transition">ยกเลิก</button>
            </div>
        </form>
    </div>
</div>

<script>
let allEmployees = [];

async function loadEmployees() {
    const data = await fetchAPI('api/employees.php');
    if (!data) return;
    allEmployees = data;
    renderTable(data);
}

function renderTable(employees) {
    const tbody = document.getElementById('employeeTable');
    if (employees.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-gray-500 py-8">ไม่มีข้อมูล</td></tr>';
        return;
    }
    tbody.innerHTML = employees.map(emp => `
        <tr class="border-b border-white/5 hover:bg-white/5">
            <td class="py-3 px-4 font-mono text-blue-400">${esc(emp.emp_code)}</td>
            <td class="py-3 px-4">${esc(emp.first_name)} ${esc(emp.last_name)}</td>
            <td class="py-3 px-4 text-gray-400">${esc(emp.department) || '-'}</td>
            <td class="py-3 px-4 text-gray-400">${esc(emp.position) || '-'}</td>
            <td class="py-3 px-4">
                ${emp.face_image
                    ? '<span class="text-green-400 text-xs"><i class="fas fa-image mr-1"></i>' + esc(emp.face_image) + '</span>'
                    : '<span class="text-gray-500 text-xs">ไม่มีรูป</span>'}
            </td>
            <td class="py-3 px-4 text-center">
                ${emp.is_authorized
                    ? '<span class="bg-green-500/20 text-green-400 px-2 py-1 rounded text-xs">อนุญาต</span>'
                    : '<span class="bg-red-500/20 text-red-400 px-2 py-1 rounded text-xs">ระงับ</span>'}
            </td>
            <td class="py-3 px-4 text-center">
                <button onclick="editEmployee(${parseInt(emp.id)})" class="text-blue-400 hover:text-blue-300 mr-2"><i class="fas fa-edit"></i></button>
                <button onclick="deleteEmployee(${parseInt(emp.id)})" class="text-red-400 hover:text-red-300"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

function showAddModal() {
    document.getElementById('modalTitle').textContent = 'เพิ่มพนักงาน';
    document.getElementById('employeeForm').reset();
    document.getElementById('formId').value = '';
    document.getElementById('formAuthorized').checked = true;
    document.getElementById('employeeModal').classList.remove('hidden');
}

function editEmployee(id) {
    const emp = allEmployees.find(e => e.id === id);
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
    document.getElementById('employeeModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('employeeModal').classList.add('hidden');
}

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

    const result = await postAPI('api/employees.php', data);
    if (result?.success) {
        closeModal();
        showToast(id ? 'แก้ไขข้อมูลพนักงานสำเร็จ' : 'เพิ่มพนักงานสำเร็จ', 'success');
        loadEmployees();
    } else {
        showToast(result?.error || 'เกิดข้อผิดพลาด', 'error');
    }
}

function deleteEmployee(id) {
    showConfirm('ลบพนักงาน', 'ต้องการลบพนักงานนี้หรือไม่? การดำเนินการนี้ไม่สามารถย้อนกลับได้', async () => {
        const res = await fetch('api/employees.php?id=' + id, { method: 'DELETE' });
        const result = await res.json();
        if (result?.success) {
            showToast('ลบพนักงานสำเร็จ', 'success');
            loadEmployees();
        } else {
            showToast(result?.error || 'เกิดข้อผิดพลาด', 'error');
        }
    });
}

// Search
document.getElementById('searchInput').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    const filtered = allEmployees.filter(e =>
        e.emp_code.toLowerCase().includes(q) ||
        e.first_name.toLowerCase().includes(q) ||
        e.last_name.toLowerCase().includes(q)
    );
    renderTable(filtered);
});

loadEmployees();
</script>

<?php include 'includes/footer.php'; ?>
