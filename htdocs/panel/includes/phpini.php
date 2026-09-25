<?php
declare(strict_types=1);

/** User-facing php.ini keys the panel can read/write. */
const PHPINI_KEYS = [
    'display_errors',
    'display_startup_errors',
    'log_errors',
    'html_errors',
    'error_reporting',
    'error_log',
    'expose_php',
    'short_open_tag',
    'file_uploads',
    'allow_url_fopen',
    'memory_limit',
    'max_execution_time',
    'post_max_size',
    'upload_max_filesize',
    'date.timezone',
];

const PHPINI_BOOL_KEYS = [
    'display_errors',
    'display_startup_errors',
    'log_errors',
    'html_errors',
    'expose_php',
    'short_open_tag',
    'file_uploads',
    'allow_url_fopen',
];

/** Bit flags used for error-level UI (stable across PHP 7.4–8.x). */
function phpini_error_bits(): array
{
    return [
        'error' => E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR,
        'warning' => E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING,
        'notice' => E_NOTICE | E_USER_NOTICE,
        'deprecated' => E_DEPRECATED | E_USER_DEPRECATED,
        'strict' => 2048,
    ];
}
const PHPINI_PRESETS = [
    'development' => [
        'label' => 'Development',
        'hint' => 'Show everything (E_ALL)',
        'expression' => 'E_ALL',
        'levels' => ['error' => true, 'warning' => true, 'notice' => true, 'deprecated' => true, 'strict' => false],
    ],
    'production' => [
        'label' => 'Production',
        'hint' => 'All except deprecated & strict',
        'expression' => 'E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_STRICT',
        'levels' => ['error' => true, 'warning' => true, 'notice' => true, 'deprecated' => false, 'strict' => false],
    ],
    'errors_warnings' => [
        'label' => 'Errors + Warnings',
        'hint' => 'Hide notices & deprecated',
        'expression' => 'E_ALL & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_STRICT',
        'levels' => ['error' => true, 'warning' => true, 'notice' => false, 'deprecated' => false, 'strict' => false],
    ],
    'errors_only' => [
        'label' => 'Errors only',
        'hint' => 'Fatal / parse / core errors',
        'expression' => 'E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR',
        'levels' => ['error' => true, 'warning' => false, 'notice' => false, 'deprecated' => false, 'strict' => false],
    ],
];

function phpini_config_id(string $version): ?string
{
    $map = ['7.4' => 'php74', '8.0' => 'php80', '8.4' => 'php84'];
    return $map[$version] ?? null;
}

function phpini_path_for_version(string $version): ?string
{
    $id = phpini_config_id($version);
    if ($id === null || !isset(CONFIG_FILES[$id])) {
        return null;
    }
    return CONFIG_FILES[$id]['path'];
}

function phpini_parse_bool(string $value): bool
{
    $v = strtolower(trim($value, " \t\"'"));
    return in_array($v, ['1', 'on', 'true', 'yes'], true);
}

function phpini_format_bool(bool $on): string
{
    return $on ? 'On' : 'Off';
}

/**
 * Extract active (non-commented) directive values from php.ini text.
 *
 * @return array<string, string>
 */
function phpini_read_values(string $content): array
{
    $found = [];
    $lines = preg_split('/\R/', $content) ?: [];
    foreach ($lines as $line) {
        $trim = ltrim($line);
        if ($trim === '' || $trim[0] === ';' || $trim[0] === '[') {
            continue;
        }
        if (!preg_match('/^([A-Za-z0-9._-]+)\s*=\s*(.*)$/', $trim, $m)) {
            continue;
        }
        $key = $m[1];
        if (!in_array($key, PHPINI_KEYS, true)) {
            continue;
        }
        if (isset($found[$key])) {
            continue; // first active wins
        }
        $val = trim($m[2]);
        // Strip inline comment when not inside quotes
        if ($val !== '' && ($val[0] === '"' || $val[0] === "'")) {
            $q = $val[0];
            if (preg_match('/^' . preg_quote($q, '/') . '([^' . preg_quote($q, '/') . ']*)' . preg_quote($q, '/') . '/', $val, $qm)) {
                $val = $qm[1];
            }
        } else {
            $val = preg_replace('/\s*;.*$/', '', $val) ?? $val;
            $val = trim($val);
        }
        $found[$key] = $val;
    }
    return $found;
}

/**
 * Evaluate a common error_reporting expression to an integer bitmask.
 */
function phpini_eval_error_reporting(string $expr): ?int
{
    $expr = trim($expr);
    if ($expr === '') {
        return null;
    }
    if (preg_match('/^-?\d+$/', $expr)) {
        return (int)$expr;
    }

    $map = [
        'E_ERROR' => E_ERROR,
        'E_WARNING' => E_WARNING,
        'E_PARSE' => E_PARSE,
        'E_NOTICE' => E_NOTICE,
        'E_CORE_ERROR' => E_CORE_ERROR,
        'E_CORE_WARNING' => E_CORE_WARNING,
        'E_COMPILE_ERROR' => E_COMPILE_ERROR,
        'E_COMPILE_WARNING' => E_COMPILE_WARNING,
        'E_USER_ERROR' => E_USER_ERROR,
        'E_USER_WARNING' => E_USER_WARNING,
        'E_USER_NOTICE' => E_USER_NOTICE,
        'E_STRICT' => 2048, // numeric — avoids E_STRICT deprecation on PHP 8.4+
        'E_RECOVERABLE_ERROR' => E_RECOVERABLE_ERROR,
        'E_DEPRECATED' => E_DEPRECATED,
        'E_USER_DEPRECATED' => E_USER_DEPRECATED,
        'E_ALL' => E_ALL,
    ];

    // Only allow safe tokens
    if (!preg_match('/^[E_A-Z0-9\s|&~()^-]+$/i', $expr)) {
        return null;
    }

    $php = $expr;
    foreach ($map as $name => $bit) {
        $php = preg_replace('/\b' . $name . '\b/', (string)(int)$bit, $php);
    }
    if (preg_match('/[A-Za-z_]/', $php)) {
        return null; // unresolved names
    }

    try {
        // phpcs:ignore
        $result = @eval('return (int)(' . $php . ');');
        return is_int($result) ? $result : null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * @return array{levels: array<string,bool>, preset: string, expression: string, mask: int|null}
 */
function phpini_decode_error_reporting(string $expr): array
{
    $mask = phpini_eval_error_reporting($expr);
    $levels = [];
    foreach (phpini_error_bits() as $name => $bits) {
        $levels[$name] = $mask !== null ? (($mask & $bits) !== 0) : false;
    }

    $preset = 'custom';
    if ($mask !== null) {
        foreach (PHPINI_PRESETS as $id => $meta) {
            $pMask = phpini_eval_error_reporting($meta['expression']);
            if ($pMask !== null && $pMask === $mask) {
                $preset = $id;
                break;
            }
        }
        // Fuzzy match by level chips when expression text differs
        if ($preset === 'custom') {
            foreach (PHPINI_PRESETS as $id => $meta) {
                if ($meta['levels'] === $levels) {
                    $preset = $id;
                    break;
                }
            }
        }
    } else {
        // Fall back to string match
        $norm = preg_replace('/\s+/', '', strtoupper($expr));
        foreach (PHPINI_PRESETS as $id => $meta) {
            if (preg_replace('/\s+/', '', strtoupper($meta['expression'])) === $norm) {
                $preset = $id;
                $levels = $meta['levels'];
                break;
            }
        }
    }

    return [
        'levels' => $levels,
        'preset' => $preset,
        'expression' => $expr,
        'mask' => $mask,
    ];
}

function phpini_build_error_reporting(array $levels): string
{
    // Match a preset first
    foreach (PHPINI_PRESETS as $meta) {
        if ($meta['levels'] === [
            'error' => !empty($levels['error']),
            'warning' => !empty($levels['warning']),
            'notice' => !empty($levels['notice']),
            'deprecated' => !empty($levels['deprecated']),
            'strict' => !empty($levels['strict']),
        ]) {
            return $meta['expression'];
        }
    }

    $parts = [];
    if (!empty($levels['error'])) {
        $parts[] = 'E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR';
    }
    if (!empty($levels['warning'])) {
        $parts[] = 'E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING';
    }
    if (!empty($levels['notice'])) {
        $parts[] = 'E_NOTICE | E_USER_NOTICE';
    }
    if (!empty($levels['deprecated'])) {
        $parts[] = 'E_DEPRECATED | E_USER_DEPRECATED';
    }
    if (!empty($levels['strict'])) {
        $parts[] = 'E_STRICT';
    }
    if (!$parts) {
        return '0';
    }
    return implode(' | ', $parts);
}

/**
 * Replace or append directive values while preserving comments and order.
 */
function phpini_write_values(string $content, array $updates): string
{
    $lines = preg_split('/\R/', $content) ?: [];
    $done = [];
    $out = [];

    foreach ($lines as $line) {
        $trim = ltrim($line);
        if ($trim !== '' && $trim[0] !== ';' && $trim[0] !== '['
            && preg_match('/^([A-Za-z0-9._-]+)\s*=\s*(.*)$/', $trim, $m)
        ) {
            $key = $m[1];
            if (array_key_exists($key, $updates) && !isset($done[$key])) {
                $indent = '';
                if (preg_match('/^(\s*)/', $line, $im)) {
                    $indent = $im[1];
                }
                $out[] = $indent . $key . ' = ' . $updates[$key];
                $done[$key] = true;
                continue;
            }
        }
        $out[] = $line;
    }

    foreach ($updates as $key => $value) {
        if (!isset($done[$key])) {
            $out[] = $key . ' = ' . $value;
            $done[$key] = true;
        }
    }

    $joined = implode("\n", $out);
    // Preserve trailing newline if original had one
    if (substr($content, -1) === "\n" || substr($content, -1) === "\r") {
        if (substr($joined, -1) !== "\n") {
            $joined .= "\n";
        }
    }
    return $joined;
}

/**
 * @return array{ok:bool,version?:string,path?:string,label?:string,settings?:array,error_ui?:array,message?:string}
 */
function phpini_get(string $version): array
{
    if (!isset(PHP_VERSIONS[$version])) {
        return ['ok' => false, 'message' => 'Unknown PHP version'];
    }
    $path = phpini_path_for_version($version);
    if ($path === null || !is_file($path)) {
        return ['ok' => false, 'message' => 'php.ini not found for PHP ' . $version];
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        return ['ok' => false, 'message' => 'Cannot read php.ini'];
    }

    $values = phpini_read_values($raw);
    $settings = [];
    foreach (PHPINI_KEYS as $key) {
        $val = $values[$key] ?? '';
        if (in_array($key, PHPINI_BOOL_KEYS, true)) {
            $settings[$key] = [
                'type' => 'bool',
                'value' => $val !== '' ? phpini_parse_bool($val) : false,
                'raw' => $val,
            ];
        } else {
            $settings[$key] = [
                'type' => 'text',
                'value' => $val,
                'raw' => $val,
            ];
        }
    }

    $errExpr = $values['error_reporting'] ?? 'E_ALL';
    $errorUi = phpini_decode_error_reporting($errExpr);
    $errorUi['presets'] = [];
    foreach (PHPINI_PRESETS as $id => $meta) {
        $errorUi['presets'][$id] = [
            'label' => $meta['label'],
            'hint' => $meta['hint'],
            'expression' => $meta['expression'],
            'levels' => $meta['levels'],
        ];
    }
    $errorUi['level_labels'] = [
        'error' => ['label' => 'Error', 'tone' => 'error', 'hint' => 'Fatal, parse, recoverable'],
        'warning' => ['label' => 'Warning', 'tone' => 'warn', 'hint' => 'Runtime warnings'],
        'notice' => ['label' => 'Notice', 'tone' => 'notice', 'hint' => 'Undefined vars, etc.'],
        'deprecated' => ['label' => 'Deprecated', 'tone' => 'notice', 'hint' => 'Legacy APIs'],
        'strict' => ['label' => 'Strict', 'tone' => 'info', 'hint' => 'E_STRICT'],
    ];

    return [
        'ok' => true,
        'version' => $version,
        'label' => PHP_VERSIONS[$version]['label'],
        'path' => $path,
        'settings' => $settings,
        'error_ui' => $errorUi,
        'restart_hint' => 'Restart this PHP version (or Restart all) for changes to apply to FastCGI workers.',
    ];
}

/**
 * @param array<string,mixed> $payload
 * @return array{ok:bool,message?:string,backup?:string,data?:array}
 */
function phpini_save(string $version, array $payload): array
{
    if (!isset(PHP_VERSIONS[$version])) {
        return ['ok' => false, 'message' => 'Unknown PHP version'];
    }
    $path = phpini_path_for_version($version);
    if ($path === null || !is_file($path)) {
        return ['ok' => false, 'message' => 'php.ini not found'];
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        return ['ok' => false, 'message' => 'Cannot read php.ini'];
    }

    $updates = [];

    // Booleans
    foreach (PHPINI_BOOL_KEYS as $key) {
        if (array_key_exists($key, $payload)) {
            $updates[$key] = phpini_format_bool((bool)$payload[$key]);
        }
    }

    // Text fields
    foreach (['memory_limit', 'max_execution_time', 'post_max_size', 'upload_max_filesize', 'date.timezone', 'error_log'] as $key) {
        if (array_key_exists($key, $payload) && is_scalar($payload[$key])) {
            $val = trim((string)$payload[$key]);
            if ($key === 'max_execution_time' && $val !== '' && !preg_match('/^-?\d+$/', $val)) {
                return ['ok' => false, 'message' => 'max_execution_time must be an integer'];
            }
            if (in_array($key, ['memory_limit', 'post_max_size', 'upload_max_filesize'], true)
                && $val !== '' && !preg_match('/^-?\d+[KMG]?$/i', $val)
            ) {
                return ['ok' => false, 'message' => $key . ' looks invalid (e.g. 128M)'];
            }
            $updates[$key] = $val;
        }
    }

    // Error reporting from levels or expression
    if (isset($payload['error_reporting']) && is_string($payload['error_reporting']) && trim($payload['error_reporting']) !== '') {
        $expr = trim($payload['error_reporting']);
        if (phpini_eval_error_reporting($expr) === null && !preg_match('/^-?\d+$/', $expr)) {
            return ['ok' => false, 'message' => 'Invalid error_reporting expression'];
        }
        $updates['error_reporting'] = $expr;
    } elseif (isset($payload['error_levels']) && is_array($payload['error_levels'])) {
        $levels = [
            'error' => !empty($payload['error_levels']['error']),
            'warning' => !empty($payload['error_levels']['warning']),
            'notice' => !empty($payload['error_levels']['notice']),
            'deprecated' => !empty($payload['error_levels']['deprecated']),
            'strict' => !empty($payload['error_levels']['strict']),
        ];
        $updates['error_reporting'] = phpini_build_error_reporting($levels);
    } elseif (isset($payload['error_preset']) && is_string($payload['error_preset'])
        && isset(PHPINI_PRESETS[$payload['error_preset']])
    ) {
        $updates['error_reporting'] = PHPINI_PRESETS[$payload['error_preset']]['expression'];
    }

    if (!$updates) {
        return ['ok' => false, 'message' => 'No settings to save'];
    }

    $newContent = phpini_write_values($raw, $updates);
    $backup = $path . '.bak-' . date('Ymd-His');
    if (!@copy($path, $backup)) {
        return ['ok' => false, 'message' => 'Failed to create backup'];
    }
    if (file_put_contents($path, $newContent) === false) {
        return ['ok' => false, 'message' => 'Write failed'];
    }

    $data = phpini_get($version);
    return [
        'ok' => true,
        'message' => 'Saved php.ini for ' . PHP_VERSIONS[$version]['label'],
        'backup' => $backup,
        'data' => $data,
        'restart_hint' => $data['restart_hint'] ?? '',
    ];
}
