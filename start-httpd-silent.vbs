' Start Apache httpd with no console window.
Option Explicit
Dim WshShell, fso, httpd, web
Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
web = "C:\web"
httpd = web & "\Apache24\bin\httpd.exe"
If fso.FileExists(httpd) Then
  ' -d ServerRoot so conf/modules resolve when launched from Startup
  WshShell.Run """" & httpd & """ -d """ & web & "\Apache24""", 0, False
End If
