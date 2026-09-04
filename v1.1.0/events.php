<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$eventsFile = __DIR__ . '/events.json';
$maxEvents = 300;

function loadEvents($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function saveEvents($file, $events, $max) {
    if (count($events) > $max) {
        $events = array_slice($events, -$max);
    }
    file_put_contents($file, json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? 'Sconosciuto';
        $ip = $input['ip'] ?? '';
        $status = $input['status'] ?? 'offline';

        $events = loadEvents($eventsFile);
        $events[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'name' => $name,
            'ip' => $ip,
            'status' => $status
        ];
        saveEvents($eventsFile, $events, $maxEvents);

        echo json_encode(['success' => true, 'count' => count($events)]);
    } else {
        $events = array_reverse(loadEvents($eventsFile));
        echo json_encode(['success' => true, 'events' => $events], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}