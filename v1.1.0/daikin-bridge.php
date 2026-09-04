<?php
$file_json = 'regole.json';

// Gestione dei comandi speciali per la pagina web (Salvataggio e Lettura JSON)
if (isset($_GET['cmd'])) {
    if ($_GET['cmd'] === 'leggi_config') {
        header('Content-Type: application/json');
        echo file_exists($file_json) ? file_get_contents($file_json) : '{}';
        exit;
    }
}

// Intercettazione del salvataggio in POST JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['cmd']) && $_GET['cmd'] === 'salva_config') {
    header('Content-Type: text/plain');
    $input_fresco = file_get_contents('php://input');
    
    // MODIFICA PROTETTA: Usiamo il flag LOCK_EX per svuotare e bloccare il file durante la scrittura
    // Questo impedisce i residui di codice e le corruzioni nel JSON
    if (file_put_contents($file_json, $input_fresco, LOCK_EX) !== FALSE) {
        echo "Automazione riscritta da zero con successo!";
    } else {
        echo "Errore: impossibile scrivere il file regole.json.";
    }
    exit;
}


// --- Logica originaria del vecchio bridge (Inoltro comandi condizionatore) ---
header('Content-Type: text/plain');
$config = json_decode(file_get_contents($file_json), true);
$ip_condizionatore = $config['ip_condizionatore'] ?? '192.168.1.150';
$comando = $_GET['cmd'] ?? 'get_control_info';

$queryParams = $_GET;
unset($queryParams['cmd']);

$url = "http://{$ip_condizionatore}/aircon/{$comando}";
if (!empty($queryParams)) {
    $url .= "?" . http_build_query($queryParams);
}

$risposta = @file_get_contents($url);
echo ($risposta === FALSE) ? "ret=ERROR,msg=Inconnesso" : $risposta;
?>
