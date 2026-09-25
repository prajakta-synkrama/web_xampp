<?php
declare(strict_types=1);

// CLI: stop all PHP-CGI listeners except the configured default.
require dirname(__DIR__) . '/includes/bootstrap.php';

$result = stop_php_except_default();
echo $result['message'] . PHP_EOL;
exit($result['ok'] ? 0 : 1);
