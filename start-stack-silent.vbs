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

' Apache after PHP/Mail; retry — at login port 80 can be briefly busy,
' and a naive findstr ":80 " falsely matches Mailpit :8025.
StartApacheWithRetry

WScript.Quit 0

Sub StartPhp(phpDir, port)
  Dim exe
  exe = phpDir & "\php-cgi.exe"
  If Not fso.FileExists(exe) Then Exit Sub
  If IsListening(port) Then Exit Sub
  WshShell.Run "cmd /c set PHPRC=" & phpDir & "&& set PHP_FCGI_CHILDREN=8&& set PHP_FCGI_MAX_REQUESTS=500&& """ & exe & """ -b 127.0.0.1:" & port & " -c """ & phpDir & """", 0, False
End Sub

Sub StartApacheWithRetry
  Dim httpd, i
  httpd = WEB & "\Apache24\bin\httpd.exe"
  If Not fso.FileExists(httpd) Then Exit Sub
  For i = 1 To 6
    If IsListening(80) Then Exit Sub
    ' -d sets ServerRoot even when cwd is Startup / System32
    WshShell.Run """" & httpd & """ -d """ & WEB & "\Apache24""", 0, False
    WScript.Sleep 2500
    If IsListening(80) Then Exit Sub
  Next
End Sub

Function IsListening(port)
  Dim rc
  ' /C: = literal phrase. Without it, findstr splits on spaces and
  ' ":80 " becomes ":80", which falsely matches Mailpit :8025.
  rc = WshShell.Run("cmd /c netstat -ano | findstr /C:"":" & port & " "" | findstr LISTENING >nul", 0, True)
  IsListening = (rc = 0)
End Function
