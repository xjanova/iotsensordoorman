<?php $pageTitle = 'การแจ้งเตือน - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h2 class="text-2xl font-bold">การแจ้งเตือนความผิดปกติ</h2>
        <p class="text-gray-400 text-sm mt-1">ตรวจสอบเหตุการณ์ผิดปกติ เช่น Tailgating, ใบหน้าไม่รู้จัก</p>
    </div>
    <div class="flex gap-3">
        <button onclick="loadAlerts('unresolved')" class="bg-red-600/20 text-red-400 hover:bg-red-600/30 px-4 py-2 rounded-lg text-sm transition" id="btnUnresolved">ยังไม่ดำเนินการ</button>
        <button onclick="loadAlerts('all')" class="bg-white/5 text-gray-400 hover:bg-white/10 px-4 py-2 rounded-lg text-sm transition" id="btnAll">ทั้งหมด</button>
    </div>
</div>

<!-- Alert Type Legend -->
<div class="flex flex-wrap gap-3 mb-6">
    <span class="bg-red-500/20 text-red-400 px-3 py-1 rounded-full text-xs"><i class="fas fa-user-xmark mr-1"></i>UNKNOWN_FACE</span>
    <span class="bg-orange-500/20 text-orange-400 px-3 py-1 rounded-full text-xs"><i class="fas fa-people-group mr-1"></i>TAILGATING</span>
    <span class="bg-purple-500/20 text-purple-400 px-3 py-1 rounded-full text-xs"><i class="fas fa-door-open mr-1"></i>FORCED_ENTRY</span>
    <span class="bg-yellow-500/20 text-yellow-400 px-3 py-1 rounded-full text-xs"><i class="fas fa-triangle-exclamation mr-1"></i>SENSOR_MISMATCH</span>
    <span class="bg-pink-500/20 text-pink-400 px-3 py-1 rounded-full text-xs"><i class="fas fa-users mr-1"></i>MULTI_PERSON</span>
    <span class="bg-gray-500/20 text-gray-400 px-3 py-1 rounded-full text-xs"><i class="fas fa-face-meh mr-1"></i>NO_FACE_DETECTED</span>
</div>

<div class="space-y-4" id="alertsList">
    <div class="text-center text-gray-500 py-8">กำลังโหลด...</div>
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
const typeIcons = {
    UNKNOWN_FACE: 'fa-user-xmark text-red-400',
    TAILGATING: 'fa-people-group text-orange-400',
    FORCED_ENTRY: 'fa-door-open text-purple-400',
    SENSOR_MISMATCH: 'fa-triangle-exclamation text-yellow-400',
    MULTI_PERSON: 'fa-users text-pink-400',
    NO_FACE_DETECTED: 'fa-face-meh text-gray-400',
};

async function loadAlerts(filter = 'unresolved') {
    let url = 'api/alerts.php?limit=100';
    if (filter === 'unresolved') url += '&resolved=0';
    const data = await fetchAPI(url);
    const container = document.getElementById('alertsList');

    if (!data || data.length === 0) {
        container.innerHTML = '<div class="text-center text-gray-500 py-8"><i class="fas fa-check-circle text-3xl mb-2 text-green-400"></i><p>ไม่มีการแจ้งเตือน</p></div>';
        return;
    }

    container.innerHTML = data.map(alert => `
        <div class="glass rounded-xl p-5 border ${severityColors[alert.severity] || ''} card-hover">
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center mt-1">
                        <i class="fas ${typeIcons[alert.alert_type] || 'fa-bell text-gray-400'}"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-medium">${alert.alert_type.replace(/_/g, ' ')}</span>
                            <span class="px-2 py-0.5 rounded text-xs ${severityBadge[alert.severity]}">${alert.severity}</span>
                            ${alert.camera_id ? '<span class="text-xs text-gray-500">Cam ' + alert.camera_id + '</span>' : ''}
                        </div>
                        <p class="text-sm text-gray-400">${esc(alert.description) || '-'}</p>
                        <p class="text-xs text-gray-500 mt-1">${formatDateTime(alert.created_at)}</p>
                    </div>
                </div>
                <div>
                    ${alert.is_resolved == 0
                        ? '<button onclick="resolveAlert(' + alert.id + ')" class="bg-green-600/20 text-green-400 hover:bg-green-600/30 px-3 py-1 rounded text-xs transition"><i class="fas fa-check mr-1"></i>ดำเนินการแล้ว</button>'
                        : '<span class="text-green-400 text-xs"><i class="fas fa-check-circle mr-1"></i>แก้ไขแล้ว</span>'}
                </div>
            </div>
        </div>
    `).join('');
}

async function resolveAlert(id) {
    await postAPI('api/alerts.php', { resolve_id: id });
    loadAlerts('unresolved');
}

loadAlerts('unresolved');
</script>

<?php include 'includes/footer.php'; ?>
