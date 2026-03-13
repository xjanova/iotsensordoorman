<?php $pageTitle = 'ประวัติเข้า-ออก - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h2 class="text-2xl font-bold">ประวัติการเข้า-ออก</h2>
        <p class="text-gray-400 text-sm mt-1">ดูประวัติการเข้า-ออกห้องสโตร์ทั้งหมด</p>
    </div>
    <div class="flex gap-3">
        <input type="date" id="filterDate" class="bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none">
        <select id="filterDirection" class="bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none">
            <option value="">ทั้งหมด</option>
            <option value="IN">เข้า</option>
            <option value="OUT">ออก</option>
        </select>
    </div>
</div>

<div class="glass rounded-2xl overflow-hidden">
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
                <th class="text-center py-4 px-4">สถานะ</th>
            </tr>
        </thead>
        <tbody id="logsTable">
            <tr><td colspan="8" class="text-center text-gray-500 py-8">กำลังโหลด...</td></tr>
        </tbody>
    </table>
</div>

<script>
async function loadLogs() {
    const date = document.getElementById('filterDate').value;
    const dir = document.getElementById('filterDirection').value;
    let url = 'api/access_logs.php?limit=200';
    if (date) url += '&date=' + date;

    let data = await fetchAPI(url);
    if (!data) return;

    if (dir) data = data.filter(l => l.direction === dir);

    const tbody = document.getElementById('logsTable');
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-gray-500 py-8">ไม่มีข้อมูล</td></tr>';
        return;
    }

    tbody.innerHTML = data.map((log, i) => `
        <tr class="border-b border-white/5 hover:bg-white/5">
            <td class="py-3 px-4 text-gray-500">${i + 1}</td>
            <td class="py-3 px-4 text-gray-300">${formatDateTime(log.created_at)}</td>
            <td class="py-3 px-4">
                ${log.first_name
                    ? '<span class="text-white">' + log.first_name + ' ' + log.last_name + '</span><br><span class="text-xs text-gray-500">' + (log.emp_code || '') + '</span>'
                    : '<span class="text-red-400">ไม่รู้จัก</span>'}
            </td>
            <td class="py-3 px-4">
                ${log.direction === 'IN'
                    ? '<span class="bg-green-500/20 text-green-400 px-2 py-1 rounded text-xs"><i class="fas fa-arrow-right mr-1"></i>เข้า</span>'
                    : '<span class="bg-blue-500/20 text-blue-400 px-2 py-1 rounded text-xs"><i class="fas fa-arrow-left mr-1"></i>ออก</span>'}
            </td>
            <td class="py-3 px-4 text-gray-400">${log.method || '-'}</td>
            <td class="py-3 px-4">
                <div class="flex items-center gap-2">
                    <div class="w-20 bg-gray-700 rounded-full h-2">
                        <div class="h-2 rounded-full ${parseFloat(log.confidence) > 70 ? 'bg-green-400' : parseFloat(log.confidence) > 40 ? 'bg-yellow-400' : 'bg-red-400'}"
                             style="width: ${log.confidence || 0}%"></div>
                    </div>
                    <span class="text-xs">${log.confidence || 0}%</span>
                </div>
            </td>
            <td class="py-3 px-4 text-gray-400">Cam ${log.camera_id || '-'}</td>
            <td class="py-3 px-4 text-center">
                ${log.is_authorized == 1
                    ? '<span class="text-green-400"><i class="fas fa-check-circle"></i> อนุญาต</span>'
                    : '<span class="text-red-400"><i class="fas fa-times-circle"></i> ปฏิเสธ</span>'}
            </td>
        </tr>
    `).join('');
}

document.getElementById('filterDate').addEventListener('change', loadLogs);
document.getElementById('filterDirection').addEventListener('change', loadLogs);
loadLogs();
</script>

<?php include 'includes/footer.php'; ?>
