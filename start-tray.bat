@echo off
REM Open the Web Stack tray icon (no console).
wscript //B //Nologo "%~dp0start-tray-silent.vbs"
exit /b 0
