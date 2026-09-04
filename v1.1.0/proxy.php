<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$ip        = $_GET['ip']        ?? '';
$action    = $_GET['action']    ?? 'status'; // status | on | off | reset | info
$channel   = isset($_GET['channel']) ? (int)$_GET['channel'] : 0;
$component = strtolower($_GET['component'] ?? 'switch'); // switch | pm1 | em

if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    http_response_code(400);
    echo json_encode(['error' => 'IP non valido']);
    exit;
}

if ($channel < 0 || $channel > 3) {
    http_response_code(400);
    echo json_encode(['error' => 'Canale non valido']);
    exit;
}

function httpGet($url, $timeout = 2.5) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'header'  => "User-Agent: ShellyProxy/2.0\r\n",
            'ignore_errors' => true
        ]
    ]);
    $data = @file_get_contents($url, false, $ctx);
    return ($data === false) ? null : $data;
}

if ($action === 'info') {
    $data = httpGet("http://{$ip}/rpc/Shelly.GetDeviceInfo");
    if ($data === null) {
        http_response_code(502);
        echo json_encode(['error' => 'Dispositivo non raggiungibile', 'ip' => $ip]);
        exit;
    }
    echo $data;
    exit;
}

$url = null;

if ($component === 'switch') {
    if ($action === 'on') {
        $url = "http://{$ip}/rpc/Switch.Set?id={$channel}&on=true";
    } elseif ($action === 'off') {
        $url = "http://{$ip}/rpc/Switch.Set?id={$channel}&on=false";
    } elseif ($action === 'reset') {
        $url = "http://{$ip}/rpc/Switch.ResetCounters?id={$channel}&type=%5B%22aenergy%22%5D";
    } else {
        $url = "http://{$ip}/rpc/Switch.GetStatus?id={$channel}";
    }
} elseif ($component === 'pm1') {
    if ($action === 'on' || $action === 'off') {
        http_response_code(400);
        echo json_encode(['error' => 'PM1 non supporta on/off']);
        exit;
    } elseif ($action === 'reset') {
        $url = "http://{$ip}/rpc/PM1.ResetCounters?id={$channel}&type=%5B%22aenergy%22%5D";
    } else {
        $url = "http://{$ip}/rpc/PM1.GetStatus?id={$channel}";
    }
} elseif ($component === 'em') {
    if ($action === 'on' || $action === 'off' || $action === 'reset') {
        http_response_code(400);
        echo json_encode(['error' => 'Azione non supportata su EM in questa versione']);
        exit;
    }
    $url = "http://{$ip}/rpc/EM.GetStatus?id={$channel}";
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Componente non supportato']);
    exit;
}

$data = httpGet($url);
if ($data === null) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Dispositivo non raggiungibile',
        'ip' => $ip,
        'channel' => $channel,
        'component' => $component
    ]);
    exit;
}

if ($component === 'em' && $action === 'status') {
    $j = json_decode($data, true);
    if (is_array($j)) {
        echo json_encode([
            'id' => $channel,
            'output' => null,
            'apower' => $j['total_act_power'] ?? $j['a_act_power'] ?? 0,
            'voltage' => $j['a_voltage'] ?? null,
            'current' => $j['total_current'] ?? $j['a_current'] ?? null,
            'aenergy' => [
                'total' => $j['total_act'] ?? $j['a_total_act_energy'] ?? 0
            ],
            'temperature' => null,
            'component' => 'em',
            'raw_em' => $j
        ]);
        exit;
    }
}

echo $data;