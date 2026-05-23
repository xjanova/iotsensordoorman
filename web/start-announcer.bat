@echo off
REM ============================================================
REM  Bunny Door Web Announcer - Windows launcher
REM ============================================================
REM  Double-click to start ที่ Windows
REM  หรือใส่ใน startup folder เพื่อ autostart:
REM    Win+R → shell:startup → คัดลอก shortcut ของไฟล์นี้
REM ============================================================

cd /d "%~dp0"

echo.
echo === Bunny Door Web Announcer ===
echo.
echo broadcasting UDP discovery to LAN every 5s
echo (กดปิดหน้าต่างนี้เพื่อหยุด)
echo.

REM ลอง python หรือ py launcher
where python >nul 2>&1
if %ERRORLEVEL%==0 (
    python announcer.py
    goto :end
)

where py >nul 2>&1
if %ERRORLEVEL%==0 (
    py announcer.py
    goto :end
)

echo [ERROR] ไม่พบ Python — ติดตั้งจาก https://python.org แล้วลองใหม่
pause

:end
echo.
echo [Announcer] หยุดทำงานแล้ว
pause
