' Start Apache httpd with no console window.
Option Explicit
Dim WshShell, fso, httpd
Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
httpd = "C:\web\Apache24\bin\httpd.exe"
If fso.FileExists(httpd) Then
  WshShell.Run """" & httpd & """", 0, False
End If
