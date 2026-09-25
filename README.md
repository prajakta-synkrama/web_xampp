# Web Stack (Apache · PHP · MySQL)

Local Windows web stack under `C:\web` with a control panel for sites, PHP config, logs, and database tools.

| Component | Location | Notes |
|-----------|----------|--------|
| Apache 2.4 | `C:\web\Apache24\` | [Apache Lounge](https://www.apachelounge.com/download/) Win32 VS18 |
| PHP 7.4.33 | `C:\web\php7.4.33\` | FastCGI `:9074` |
| PHP 8.0.30 | `C:\web\php8.0.30\` | FastCGI `:9080` |
| PHP 8.4.26 | `C:\web\php8.4.26\` | FastCGI `:9084` |
| Site files | `C:\web\htdocs\` | Document root + panel |
| Control panel | [http://localhost/panel/](http://localhost/panel/) | Localhost only |

---

## Prerequisites

1. **Visual C++ Redistributable (x86)** — required by Apache Lounge Win32 and PHP x86 builds  
   https://aka.ms/vc14/vc_redist.x86.exe
2. Windows 8+ (or supported Server editions).
3. Optional: MySQL/MariaDB on `127.0.0.1:3306` (panel DB tools / phpMyAdmin).

---

## 1. Download & extract Apache

**Package (Win32 / VS18):**  
https://www.apachelounge.com/download/VS18/binaries/httpd-2.4.68-260920-win32-vs18.zip

**Steps**

1. Download the zip above (or the current Win32 VS18 build from [Apache Lounge Download](https://www.apachelounge.com/download/)).
2. Extract the archive.
3. Move / rename the inner `Apache24` folder to:

   ```text
   C:\web\Apache24
   ```

4. Keep the repo’s Apache config under `Apache24\conf\` (especially `httpd.conf` and `conf\extra\httpd-php-fpm.conf`). If you extract a fresh Apache over an existing install, **back up `conf\` first**, then restore those files.
5. Confirm `C:\web\Apache24\bin\httpd.exe` exists.

> Default Apache Lounge docs use `C:\Apache24`. This stack uses **`C:\web\Apache24`** — `Define SRVROOT` in `httpd.conf` must match that path.

---

## 2. Download & extract PHP (Thread-Safe · x86 / 32-bit)

Use **Thread Safe (TS)** · **x86 (32-bit)** Windows builds so they match **Apache Lounge Win32**.

### Where to get builds

| Version | Page |
|---------|------|
| Current releases | [php.net Downloads](https://www.php.net/downloads.php) → Windows downloads / [windows.php.net](https://windows.php.net/download/) |
| PHP 7.4.33 (EOL) | [PHP.Watch 7.4.33](https://php.watch/versions/7.4/releases/7.4.33) → Windows → **Thread-Safe** → **x86** |
| Older archives | [windows.php.net/downloads/releases/archives](https://windows.php.net/downloads/releases/archives/) |

On each release page pick:

- **Thread Safe** (not NTS)
- **x86** / **32-bit** (not x64)
- Zip that includes `php-cgi.exe` (VS16 / VS17 / VS18 builds as listed for that PHP version)

Example zip names:

```text
php-7.4.33-Win32-vc15-x86.zip
php-8.0.30-Win32-vs16-x86.zip
php-8.4.xx-Win32-vs17-x86.zip   (version/compiler tag may vary)
```

### Extract into these folders

| PHP | Extract to |
|-----|------------|
| 7.4.33 | `C:\web\php7.4.33\` |
| 8.0.30 | `C:\web\php8.0.30\` |
| 8.4.x  | `C:\web\php8.4.26\` (or update paths in the panel / start scripts if the folder name differs) |

After extract, each folder should contain at least:

```text
php.exe
php-cgi.exe
php.ini          (copy from php.ini-development if missing)
ext\
```

### Minimal php.ini setup (each version)

1. Copy `php.ini-development` → `php.ini` if needed.
2. Set useful defaults (also editable later in the panel → **PHP Config**):

   ```ini
   extension_dir = "ext"
   date.timezone = Asia/Kolkata
   display_errors = On
   log_errors = On
   error_log = C:/web/phpX.Y.Z/logs/php_errors.log
   ```

3. Create `logs\` under each PHP folder if missing.
4. Enable common extensions as needed (`mysqli`, `pdo_mysql`, `mbstring`, `openssl`, `curl`, …).

> **Note:** This stack runs PHP as **FastCGI** (`php-cgi.exe` on ports 9074 / 9080 / 9084), not as an Apache module. Folder names and ports are wired in `htdocs\panel\includes\bootstrap.php` and `start-php-silent.vbs`.

---

## 3. Layout after install

```text
C:\web\
  Apache24\          ← from Apache Lounge zip
  php7.4.33\         ← PHP TS x86
  php8.0.30\
  php8.4.26\
  htdocs\            ← sites + panel + phpMyAdmin
  start-stack.bat
  start-tray.bat
  …
```

Apache, PHP zips, and logs are gitignored; the panel, scripts, and config templates stay in the repo.

---

## 4. Start the stack

| Action | Command |
|--------|---------|
| Start Apache + PHP (silent) | `start-stack.bat` or `start-stack-silent.vbs` |
| System tray | `start-tray.bat` |
| PHP only | `start-php.bat` |
| Stop non-default PHP | `stop-php.bat` |

Then open:

- Sites: http://localhost/
- Panel: http://localhost/panel/
- phpMyAdmin: http://localhost/phpmyadmin/

---

## 5. Control panel

http://localhost/panel/ (localhost only)

- **Overview** — Apache / PHP / MySQL status, start/stop, default PHP for localhost  
- **Sites** — custom domains, document roots, per-site PHP version, hosts sync  
- **PHP Config** — Simple / Advanced UI: On/Off toggles, error / warning / notice levels, limits  
- **Config editor** — raw `httpd.conf` / `php.ini`  
- **Logs** — Apache & PHP logs with filters  
- **Database** — browse / run SQL  

Saving `php.ini` creates a `.bak-*` backup. Use **Restart PHP** in PHP Config so FastCGI workers reload the file.

---

## 6. Quick checklist

- [ ] VC++ x86 redistributable installed  
- [ ] `C:\web\Apache24\bin\httpd.exe` present  
- [ ] `C:\web\php7.4.33\php-cgi.exe` (and 8.0 / 8.4) present — **TS · x86**  
- [ ] Each PHP folder has a working `php.ini`  
- [ ] `start-stack.bat` → http://localhost/panel/ loads  

---

## Links

- Apache Lounge: https://www.apachelounge.com/download/  
- Apache 2.4.68 Win32 VS18 zip: https://www.apachelounge.com/download/VS18/binaries/httpd-2.4.68-260920-win32-vs18.zip  
- PHP downloads: https://www.php.net/downloads.php  
- PHP 7.4.33 Windows builds: https://php.watch/versions/7.4/releases/7.4.33  
- VC++ redistributable (x86): https://aka.ms/vc14/vc_redist.x86.exe  
