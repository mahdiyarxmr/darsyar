@echo off
setlocal EnableExtensions

echo ========================================================
echo   Stopping DarsYar PHP Local Server on 127.0.0.1:8000
echo ========================================================

powershell -NoProfile -Command "$procs = Get-CimInstance Win32_Process -Filter \"Name='php.exe'\" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like '*127.0.0.1:8000*' }; if ($procs) { foreach ($p in $procs) { Stop-Process -Id $p.ProcessId -Force; Write-Output ('Stopped DarsYar PHP server PID: ' + $p.ProcessId) } } else { Write-Output 'No DarsYar PHP server running on 127.0.0.1:8000.' }"

echo Note: Shared XAMPP MariaDB/Apache services were left untouched.
