<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Web Stack Panel</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/app.css?v=8">
</head>
<body>
  <div class="app">
    <aside class="sidebar">
      <div class="brand">
        <strong>Stack Panel</strong>
        <span>Apache · PHP · MySQL</span>
      </div>
      <nav class="nav">
        <button type="button" data-view="overview" class="active">Overview</button>
        <button type="button" data-view="sites">Sites</button>
        <button type="button" data-view="config">Config editsor</button>
        <button type="button" data-view="logs">Logs</button>
        <button type="button" data-view="database">Database</button>
        <button type="button" onclick="window.open('http://localhost/phpmyadmin/', '_blank')">Phpmyadmin</button>
      </nav>
    </aside>

    <main class="main">
      <div class="topbar">
        <div>
          <h1 id="page-title">Stack overview</h1>
          <p id="page-sub">Live status, restarts, and quick links</p>
        </div>
        <button class="btn" type="button" id="refresh-status">Refresh</button>
      </div>

      <section class="section active" id="view-overview">
        <div class="grid stats">
          <div class="card stat" id="stat-apache"></div>
          <div class="card stat" id="stat-mysql"></div>
          <div class="card stat" id="stat-runtime"></div>
          <div class="card stat">
            <div class="label">Controls</div>
            <div class="btn-row" style="margin-top:12px">
              <button class="btn primary" type="button" data-action="restart_all">Restart all</button>
              <button class="btn warn" type="button" data-action="restart_apache">Restart Apache</button>
            </div>
            <div class="btn-row" style="margin-top:8px">
              <button class="btn" type="button" data-action="start_apache">Start Apache</button>
              <button class="btn danger" type="button" data-action="stop_apache">Stop Apache</button>
            </div>
            <div class="btn-row" style="margin-top:8px">
              <button class="btn" type="button" data-action="start_php">Start all PHP</button>
              <button class="btn danger" type="button" data-action="stop_php" title="Keeps default PHP for localhost">Stop others</button>
            </div>
            <div class="meta" style="margin-top:10px">Default for localhost</div>
            <div class="btn-row" style="margin-top:6px">
              <select id="default-php-select">
                <option value="7.4">PHP 7.4</option>
                <option value="8.0">PHP 8.0</option>
                <option value="8.4" selected>PHP 8.4</option>
              </select>
              <button class="btn primary" type="button" id="save-default-php">Apply</button>
            </div>
          </div>
        </div>

        <div class="grid three" style="margin-top:14px" id="php-cards"></div>

        <div class="card" style="margin-top:14px">
          <h2>Quick open</h2>
          <div class="link-list" id="site-links"></div>
        </div>
      </section>

      <section class="section" id="view-sites">
        <div class="sites-layout">
          <div class="stack">
            <div class="card">
              <div class="row-between">
                <div>
                  <h2>Your websites</h2>
                  <p class="muted">Custom domains with their own folder and PHP version.</p>
                </div>
                <div class="btn-row">
                  <button class="btn" type="button" id="site-sync-hosts-btn">Sync hosts</button>
                  <button class="btn primary" type="button" id="site-new-btn">Add site</button>
                </div>
              </div>
              <div id="custom-sites-table" class="sites-table-wrap"></div>
              <div id="hosts-hint" class="hosts-hint hidden"></div>
            </div>

            <div class="card">
              <h2>Built-in hosts</h2>
              <p class="muted">System PHP routers — document root <code>C:/web/htdocs</code>.</p>
              <div id="system-sites" class="stack" style="margin-top:12px"></div>
            </div>
          </div>

          <div class="card site-form-card" id="site-form-card">
            <h2 id="site-form-title">Add site</h2>
            <form id="site-form" class="site-form" autocomplete="off">
              <input type="hidden" id="site-id" value="">
              <label>Name
                <input type="text" id="site-name" placeholder="My project" required>
              </label>
              <label>Domain
                <input type="text" id="site-domain" placeholder="myapp.test" required>
              </label>
              <label>Aliases <span class="muted">(optional, spaces or commas)</span>
                <input type="text" id="site-aliases" placeholder="www.myapp.test myapp.local">
              </label>
              <label>Local path
                <div class="path-row">
                  <input type="text" id="site-root" placeholder="C:/web/htdocs/myapp" required>
                  <button class="btn" type="button" id="site-suggest-path">Use htdocs</button>
                </div>
              </label>
              <label>PHP version
                <select id="site-php"></select>
              </label>
              <label class="check-row">
                <input type="checkbox" id="site-enabled" checked>
                Enabled (serve over Apache)
              </label>
              <label class="check-row">
                <input type="checkbox" id="site-create-folder" checked>
                Create folder + index.php if missing
              </label>
              <div class="btn-row">
                <button class="btn primary" type="submit" id="site-save-btn">Save site</button>
                <button class="btn" type="button" id="site-reset-btn">Clear</button>
              </div>
              <p class="meta">Saving updates Apache vhosts, reloads Apache, and tries to write the Windows hosts file.</p>
            </form>
          </div>
        </div>
      </section>

      <section class="section" id="view-config">
        <div class="card">
          <div class="toolbar">
            <select id="config-select">
              <option value="php-fpm">Apache PHP vhosts</option>
              <option value="httpd">Apache httpd.conf</option>
              <option value="php74">php.ini (7.4)</option>
              <option value="php80">php.ini (8.0)</option>
              <option value="php84">php.ini (8.4)</option>
            </select>
            <button class="btn" type="button" id="reload-config">Reload</button>
            <button class="btn primary" type="button" id="save-config">Save</button>
            <span class="muted" id="config-path"></span>
          </div>
          <textarea class="editor code" id="config-editor" spellcheck="false"></textarea>
          <p class="meta">Saves create a timestamped <code>.bak-*</code> beside the file. Apache configs are syntax-checked after save.</p>
        </div>
      </section>

      <section class="section" id="view-logs">
        <div class="card">
          <div class="toolbar log-toolbar">
            <div class="log-source-tabs" id="log-source-tabs" role="tablist">
              <button type="button" class="log-tab active" data-source="apache">Apache</button>
              <button type="button" class="log-tab" data-source="default">localhost</button>
              <button type="button" class="log-tab" data-source="php74">PHP 7.4</button>
              <button type="button" class="log-tab" data-source="php80">PHP 8.0</button>
              <button type="button" class="log-tab" data-source="php84">PHP 8.4</button>
            </div>
            <div class="log-kind-tabs" id="log-kind-tabs">
              <button type="button" class="log-kind active" data-kind="error">Error</button>
              <button type="button" class="log-kind" data-kind="access">Access</button>
              <button type="button" class="log-kind hidden" data-kind="php">PHP runtime</button>
            </div>
          </div>
          <div class="toolbar log-toolbar">
            <input type="search" id="log-search" placeholder="Search message, code, client, path…" autocomplete="off">
            <select id="log-level-filter">
              <option value="all">All types</option>
              <option value="error">Error</option>
              <option value="warn">Warn</option>
              <option value="notice">Notice</option>
              <option value="info">Info</option>
            </select>
            <select id="log-category-filter">
              <option value="all">All categories</option>
            </select>
            <select id="log-domain-filter">
              <option value="all">All domains</option>
            </select>
            <button class="btn" type="button" id="reload-logs">Reload</button>
            <button class="btn" type="button" id="toggle-raw-logs">Raw</button>
          </div>
          <div class="log-domains" id="log-domains"></div>
          <div class="log-meta row-between">
            <span class="muted" id="log-path"></span>
            <div class="log-counts" id="log-counts"></div>
          </div>
          <div class="log-table-wrap" id="log-table-wrap">
            <table class="log-table" id="log-table">
              <thead>
                <tr>
                  <th>Time</th>
                  <th>Type</th>
                  <th>Category</th>
                  <th>Code</th>
                  <th>Domain</th>
                  <th>Client</th>
                  <th>Message</th>
                </tr>
              </thead>
              <tbody id="log-rows"></tbody>
            </table>
            <p class="muted hidden" id="log-empty">No log entries for this filter.</p>
          </div>
          <pre class="logs code hidden" id="log-view"></pre>
        </div>
      </section>

      <section class="section" id="view-database">
        <div class="db-layout">
          <div class="db-lists">
            <div class="db-side" id="db-list"></div>
            <div class="db-side tall" id="table-list"></div>
          </div>
          <div class="stack">
            <div class="card">
              <div class="row-between">
                <h2>SQL · <span id="db-current" class="muted">—</span></h2>
                <button class="btn primary" type="button" id="run-sql">Run query</button>
              </div>
              <textarea class="sql-box" id="sql-input" spellcheck="false" placeholder="SELECT * FROM table LIMIT 50;"></textarea>
              <div id="query-result"></div>
            </div>
            <div class="card">
              <h2>Browse</h2>
              <div id="browse-result"><p class="muted">Select a table.</p></div>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <div class="toast" id="toast"></div>
  <script src="assets/app.js?v=8"></script>
</body>
</html>
