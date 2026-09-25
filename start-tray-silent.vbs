' Launch Web Stack system tray icon (no console window).
Option Explicit
Dim WshShell, cmd
Set WshShell = CreateObject("WScript.Shell")
cmd = "powershell.exe -NoProfile -ExecutionPolicy Bypass -STA -WindowStyle Hidden -File ""C:\web\tray\WebStackTray.ps1"""
WshShell.Run cmd, 0, False
