    </main>
</div>

<script>
// ============================================================
// Global Utilities
// ============================================================
const FACE_SERVER = '<?= FACE_SERVER_URL ?>';

async function fetchAPI(url) {
    try {
        const res = await fetch(url);
        return await res.json();
    } catch(e) {
        console.error('API Error:', e);
        return null;
    }
}

async function postAPI(url, data = {}) {
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return await res.json();
    } catch(e) {
        console.error('API Error:', e);
        return null;
    }
}

// Check server status periodically
async function checkServerStatus() {
    const dot = document.getElementById('serverStatus');
    const text = document.getElementById('serverStatusText');
    try {
        const res = await fetch(FACE_SERVER + '/api/status', { signal: AbortSignal.timeout(3000) });
        if (res.ok) {
            dot.className = 'pulse-dot w-2 h-2 bg-green-400 rounded-full';
            text.textContent = 'System Online';
        } else {
            dot.className = 'w-2 h-2 bg-yellow-400 rounded-full';
            text.textContent = 'Partial Online';
        }
    } catch {
        dot.className = 'w-2 h-2 bg-red-400 rounded-full';
        text.textContent = 'Server Offline';
    }
}
setInterval(checkServerStatus, 10000);
checkServerStatus();

// Format datetime
function formatDateTime(iso) {
    if (!iso) return '-';
    const d = new Date(iso);
    return d.toLocaleDateString('th-TH') + ' ' + d.toLocaleTimeString('th-TH');
}

function formatTime(iso) {
    if (!iso) return '-';
    return new Date(iso).toLocaleTimeString('th-TH');
}
</script>
</body>
</html>
