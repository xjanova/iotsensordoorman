#!/usr/bin/env bash
# Debug: ทดสอบ discovery หา web server
cd ~/bunny-door/src/python
source ~/bunny-door/venv/bin/activate
python3 <<'PYEOF'
from discovery import discover_web_url, get_local_ip, _ping_web

ip = get_local_ip()
print(f"\nPi IP: {ip}")
print(f"PC IP (target): 192.168.1.132\n")

print("─── Test ping แต่ละ path ตรงๆ ───")
paths = [
    "/bunny-door/web",
    "/bunny-door",
    "/iotsensordoorman/web",
    "/iotsensordoorman",
    "/web",
    "",
]
for path in paths:
    url = f"http://192.168.1.132{path}"
    r = _ping_web(url, timeout=3)
    status = "✔ FOUND" if r else "✘"
    print(f"  {status:10s} {url}")
    if r:
        print(f"             response: {r}")

print("\n─── Subnet scan (จะใช้เวลา ~10-30s) ───")
result = discover_web_url(ip)
print(f"\nผลลัพธ์: {result}")
PYEOF
