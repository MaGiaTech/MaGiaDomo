<?php
header('Content-Type: text/plain; charset=utf-8');

$file = __DIR__ . '/devices.json';
$test = [['name' => 'Test', 'ip' => '192.168.1.1', 'model' => 'Shelly', 'note' => '']];

$result = @file_put_contents($file, json_encode($test, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($result === false) {
    echo "ERRORE: non riesco a scrivere devices.json\n";
    echo "Cartella: " . __DIR__ . "\n";
    echo "Scrivibile: " . (is_writable(__DIR__) ? "SI" : "NO") . "\n";
    echo "Utente PHP: " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user()) . "\n";
} else {
    echo "OK: devices.json creato correttamente\n";
    echo "Bytes scritti: $result\n";
}