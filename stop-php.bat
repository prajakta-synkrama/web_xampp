@echo off
REM Stop non-default PHP listeners only. Default (localhost) stays up.
cd /d C:\web
REM Prefer PHP 8.4 CLI for the helper; fall back through versions.
set PHPCLI=
if exist "C:\web\php8.4.26\php.exe" set PHPCLI=C:\web\php8.4.26\php.exe
if "%PHPCLI%"=="" if exist "C:\web\php8.0.30\php.exe" set PHPCLI=C:\web\php8.0.30\php.exe
if "%PHPCLI%"=="" if exist "C:\web\php7.4.33\php.exe" set PHPCLI=C:\web\php7.4.33\php.exe
if "%PHPCLI%"=="" (
  echo No PHP CLI found.
  exit /b 1
)
"%PHPCLI%" "C:\web\htdocs\panel\cli\stop-php.php"
exit /b %ERRORLEVEL%
