<?php $pageTitle = 'ตั้งค่าระบบ - Bunny Door System'; ?>
<?php include 'includes/header.php'; ?>

<div class="mb-8">
    <h2 class="text-2xl font-bold">ตั้งค่าระบบ</h2>
    <p class="text-gray-400 text-sm mt-1">กำหนดค่าต่างๆ ของระบบ Bunny Door</p>
</div>

<?php
$db = getDB();
$settings = $db->query("SELECT * FROM settings ORDER BY id")->fetchAll();
$systemStatus = $db->query("SELECT * FROM system_status ORDER BY id")->fetchAll();
?>

<!-- System Status -->
<div class="glass rounded-2xl p-6 mb-8">
    <h3 class="font-medium mb-4 flex items-center gap-2">
        <i class="fas fa-server text-blue-400"></i> สถานะอุปกรณ์
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach ($systemStatus as $s): ?>
        <div class="bg-white/5 rounded-lg p-4 flex items-center gap-3">
            <span class="w-3 h-3 rounded-full <?= $s['status'] === 'ONLINE' ? 'bg-green-400 pulse-dot' : ($s['status'] === 'ERROR' ? 'bg-red-400' : 'bg-gray-500') ?>"></span>
            <div>
                <p class="text-sm font-medium"><?= htmlspecialchars($s['component']) ?></p>
                <p class="text-xs text-gray-400"><?= $s['status'] ?> <?= $s['last_heartbeat'] ? '(' . $s['last_heartbeat'] . ')' : '' ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Settings -->
<div class="glass rounded-2xl p-6">
    <h3 class="font-medium mb-4 flex items-center gap-2">
        <i class="fas fa-sliders text-purple-400"></i> ค่าตั้งระบบ
    </h3>
    <form id="settingsForm" onsubmit="saveSettings(event)">
        <div class="space-y-4">
            <?php foreach ($settings as $s): ?>
            <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                <div>
                    <p class="text-sm font-medium"><?= htmlspecialchars($s['description']) ?></p>
                    <p class="text-xs text-gray-500 font-mono"><?= htmlspecialchars($s['setting_key']) ?></p>
                </div>
                <input type="text" name="<?= htmlspecialchars($s['setting_key']) ?>"
                       value="<?= htmlspecialchars($s['setting_value']) ?>"
                       class="bg-white/10 border border-white/10 rounded-lg px-3 py-2 text-white w-48 text-right focus:outline-none focus:border-blue-500">
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-6 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>

<script>
async function saveSettings(e) {
    e.preventDefault();
    const form = new FormData(e.target);
    const data = Object.fromEntries(form);
    const res = await postAPI('api/settings.php', data);
    if (res?.success) {
        alert('บันทึกสำเร็จ!');
    } else {
        alert(res?.error || 'เกิดข้อผิดพลาด');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
