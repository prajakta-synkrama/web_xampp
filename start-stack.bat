@echo off
REM Silent launcher (no console). Prefer this over start-stack.bat for background use.
wscript //B //Nologo "%~dp0start-stack-silent.vbs"
exit /b 0
