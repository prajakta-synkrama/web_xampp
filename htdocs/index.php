<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($path === '/' || $path === '/index.php') {
    header('Location: /panel/', true, 302);
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
echo 'PHP ' . PHP_VERSION . PHP_EOL;
echo 'SAPI ' . PHP_SAPI . PHP_EOL;
echo 'Loaded ini ' . (php_ini_loaded_file() ?: '(none)') . PHP_EOL;
echo 'Panel: http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/panel/' . PHP_EOL;
