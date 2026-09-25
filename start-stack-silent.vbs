' Fully silent stack start — PHP + Mailpit + Apache, no console windows.
Option Explicit

Dim WshShell, fso
Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

Const WEB = "C:\web"

StartPhp "C:\web\php7.4.33", 9074
StartPhp "C:\web\php8.0.30", 9080
StartPhp "C:\web\php8.4.26", 9084

' Local mail catcher (SMTP 1025 / UI 8025)
If fso.FileExists(WEB & "\start-mailpit-silent.vbs") Then
  WshShell.Run "wscript //B //Nologo """ & WEB & "\start-mailpit-silent.vbs""", 0, False
End If

WScript.Sleep 2000

If Not IsListening(80) Then
  If fso.FileExists(WEB & "\Apache24\bin\httpd.exe") Then
    WshShell.Run """" & WEB & "\Apache24\bin\httpd.exe""", 0, False
  End If
End If

WScript.Quit 0

Sub StartPhp(phpDir, port)
  Dim exe
  exe = phpDir & "\php-cgi.exe"
  If Not fso.FileExists(exe) Then Exit Sub
  If IsListening(port) Then Exit Sub
  WshShell.Run "cmd /c set PHPRC=" & phpDir & "&& set PHP_FCGI_CHILDREN=8&& set PHP_FCGI_MAX_REQUESTS=500&& """ & exe & """ -b 127.0.0.1:" & port & " -c """ & phpDir & """", 0, False
End Sub

Function IsListening(port)
  Dim rc
  rc = WshShell.Run("cmd /c netstat -ano | findstr "":" & port & " "" | findstr LISTENING >nul", 0, True)
  IsListening = (rc = 0)
End Function
