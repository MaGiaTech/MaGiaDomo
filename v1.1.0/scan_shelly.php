<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ====================== CONFIGURA QUI ======================
$network     = "192.168.1.";
$start       = 1;
$end         = 254;
$timeout     = 0.6;     // secondi
$concurrency = 25;
// ===========================================================

function respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function isShellyResponse($data) {
    if (!$data) return false;
    $lower = strtolower($data);
    return (
        strpos($lower, 'shelly') !== false ||
        strpos($lower, 'apower') !== false ||
        strpos($lower, 'aenergy') !== false ||
        strpos($data, '"id"') !== false ||
        strpos($lower, 'output') !== false
    );
}

function parseDevice($ip, $data) {
    $json = json_decode($data, true);
    $lastOctet = substr(strrchr($ip, '.'), 1);

    if (!is_array($json)) {
        return [
            'ip'    => $ip,
            'name'  => 'Shelly-' . $lastOctet,
            'model' => 'Shelly'
        ];
    }

    // Prova vari campi possibili
    $name = $json['name']
         ?? $json['id']
         ?? $json['model']
         ?? $json['type']
         ?? ('Shelly-' . $lastOctet);

    $model = $json['model']
          ?? $json['type']
          ?? $json['app']
          ?? 'Shelly';

    return [
        'ip'    => $ip,
        'name'  => substr((string)$name, 0, 40),
        'model' => substr((string)$model, 0, 40)
    ];
}

/**
 * Controlla un singolo IP provando più endpoint
 */
function checkIp($ip, $timeout) {
    $endpoints = [
        "http://{$ip}/rpc/Switch.GetStatus?id=0",   // quello che ti funziona
        "http://{$ip}/rpc/Shelly.GetDeviceInfo",
        "http://{$ip}/shelly",
        "http://{$ip}/rpc/Shelly.GetStatus"
    ];

    foreach ($endpoints as $url) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'header'  => "User-Agent: ShellyScanner/3.0\r\n",
                'ignore_errors' => true
            ]
        ]);

        $data = @file_get_contents($url, false, $ctx);

        if ($data !== false && isShellyResponse($data)) {
            return parseDevice($ip, $data);
        }
    }

    return null;
}

/**
 * Scan con cURL multi (se disponibile)
 */
function scanWithCurl(array $ips, $timeout, $concurrency) {
    $found = [];
    $chunks = array_chunk($ips, $concurrency);

    foreach ($chunks as $chunk) {
        $mh = curl_multi_init();
        $handles = [];

        foreach ($chunk as $ip) {
            // Prima prova l'endpoint che sappiamo funzionare
            $url = "http://{$ip}/rpc/Switch.GetStatus?id=0";

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT_MS     => (int)($timeout * 1000),
                CURLOPT_CONNECTTIMEOUT_MS => (int)($timeout * 1000),
                CURLOPT_USERAGENT      => 'ShellyScanner/3.0',
                CURLOPT_NOSIGNAL       => true,
            ]);

            curl_multi_add_handle($mh, $ch);
            $handles[] = ['ip' => $ip, 'ch' => $ch];
        }

        $running = null;
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh, 0.1);
        } while ($running > 0 && $status === CURLM_OK);

        foreach ($handles as $item) {
            $ch   = $item['ch'];
            $ip   = $item['ip'];
            $data = curl_multi_getcontent($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);

            if ($code === 200 && isShellyResponse($data)) {
                $found[] = parseDevice($ip, $data);
            }
        }

        curl_multi_close($mh);
    }

    return $found;
}

/**
 * Scan sequenziale di sicurezza
 */
function scanSequential(array $ips, $timeout) {
    $found = [];
    foreach ($ips as $ip) {
        $result = checkIp($ip, $timeout);
        if ($result) {
            $found[] = $result;
        }
    }
    return $found;
}

// ------------------- ESECUZIONE -------------------
try {
    $allIps = [];
    for ($i = $start; $i <= $end; $i++) {
        $allIps[] = $network . $i;
    }

    if (function_exists('curl_multi_init')) {
        $found = scanWithCurl($allIps, $timeout, $concurrency);
        $method = 'curl';
    } else {
        // Senza curl limitiamo il range per evitare timeout
        $allIps = array_values(array_filter($allIps, function($ip) {
            $n = (int) substr(strrchr($ip, '.'), 1);
            return $n >= 100 && $n <= 180; // adatta se serve
        }));
        $found = scanSequential($allIps, $timeout);
        $method = 'sequential';
    }

    // Se curl non ha trovato nulla, prova un secondo passaggio sequenziale
    // solo sugli IP "probabili" (opzionale ma utile)
    if (count($found) === 0 && function_exists('curl_multi_init')) {
        $priority = [];
        foreach ([108, 165, 100, 101, 102, 110, 120, 150, 160, 170, 180] as $n) {
            $priority[] = $network . $n;
        }
        $extra = scanSequential($priority, $timeout);
        $found = array_merge($found, $extra);
        $method .= '+fallback';
    }

    // Rimuove eventuali duplicati
    $unique = [];
    foreach ($found as $dev) {
        $unique[$dev['ip']] = $dev;
    }
    $found = array_values($unique);

    usort($found, function ($a, $b) {
        return ip2long($a['ip']) <=> ip2long($b['ip']);
    });

    respond([
        'success' => true,
        'count'   => count($found),
        'method'  => $method,
        'devices' => $found
    ]);

} catch (Throwable $e) {
    respond([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}