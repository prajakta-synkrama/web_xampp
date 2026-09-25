@echo off
REM Start Mailpit silently (SMTP 1025, UI http://127.0.0.1:8025/)
wscript //B //Nologo "%~dp0start-mailpit-silent.vbs"
exit /b 0
