<?php
///bin/php -f /volume1/web/ARGO/daikin-automations.php
// 1. Configurazione percorsi e fuso orario
$file_json = __DIR__ . '/regole.json';
date_default_timezone_set('Europe/Rome');

if (!file_exists($file_json)) {
    die("Errore: file regole.json non trovato.\n");
}

// 2. Carica il database multi-regola
$db = json_decode(file_get_contents($file_json), true);
$ip_clima = $db['ip_condizionatore'] ?? '';
$lista_regole = $db['lista_regole'] ?? [];

if (empty($ip_clima)) {
    die("Errore: IP condizionatore non configurato nel JSON.\n");
}

// 3. Interroga lo stato reale del condizionatore
$context = stream_context_create(['http' => ['timeout' => 4.0]]);
$stato_testo = @file_get_contents("http://{$ip_clima}/aircon/get_control_info", false, $context);
$sensori_testo = @file_get_contents("http://{$ip_clima}/aircon/get_sensor_info", false, $context);

if ($stato_testo === FALSE || $sensori_testo === FALSE) {
    die("[" . date("Y-m-d H:i:s") . "] Errore: Condizionatore irraggiungibile.\n");
}

function parseDaikin($text) {
    $obj = [];
    foreach (explode(',', $text) as $coppia) {
        $parti = explode('=', $coppia);
        if (count($parti) == 2) $obj[trim($parti[0])] = trim($parti[1]);
    }
    return $obj;
}

$stato = parseDaikin($stato_testo);
$sensori = parseDaikin($sensori_testo);

// 4. Elaborazione variabili correnti
$temp_stanza = isset($sensori['htemp']) ? (float)$sensori['htemp'] : 0.0;
$oggi = date('Y-m-d');
$ora_minuti = date('H:i');

echo "[" . date("Y-m-d H:i:s") . "] Scansione avviata su " . count($lista_regole) . " regole.\n";

$regola_attiva = null;
$azione_fine = 'nulla'; // Default di sicurezza

// 5. CICLO DI SCANSIONE: Cerchiamo se una regola corrisponde a questo minuto
foreach ($lista_regole as $regola) {
    $p_valido = ($oggi >= $regola['data_inizio'] && $oggi <= $regola['data_fine']);
    $o_valido = ($ora_minuti >= $regola['ora_inizio'] && $ora_minuti <= $regola['ora_fine']);
    $c_valido = ($temp_stanza >= (float)$regola['temperatura_soglia']);

    if ($p_valido && $o_valido) {
        // Se siamo nell'orario giusto, questa regola gestisce la fine finestra attuale
        $azione_fine = $regola['azione_fine'];
        
        if ($c_valido) {
            $regola_attiva = $regola;
            break; // Trovata la regola prioritaria, usciamo dal ciclo
        }
    }
}

// 6. APPLICAZIONE DELLA LOGICA DI COMANDO
if ($regola_attiva !== null) {
    echo "-> CONDIZIONE ATTIVA: Applico i parametri dello scenario.\n";
    
    // Controlliamo se lo stato attuale è già allineato per non mandare comandi inutili
    $differisce = ($stato['pow'] === '0' || 
                   $stato['mode'] !== $regola_attiva['modalita'] || 
                   $stato['stemp'] !== $regola_attiva['temperatura_desiderata'] || 
                   $stato['f_rate'] !== $regola_attiva['f_rate'] || 
                   $stato['f_dir'] !== $regola_attiva['f_dir']);

    if ($differisce) {
        $stato['pow'] = '1';
        $stato['mode'] = $regola_attiva['modalita'];
        $stato['stemp'] = $regola_attiva['temperatura_desiderata'];
        $stato['f_rate'] = $regola_attiva['f_rate'];
        $stato['f_dir'] = $regola_attiva['f_dir'];
        
        unset($stato['ret']);
        $url_set = "http://{$ip_clima}/aircon/set_control_info?" . http_build_query($stato);
        $res = @file_get_contents($url_set, false, $context);
        echo "-> Risposta Daikin: " . $res . "\n";
    } else {
        echo "-> Il condizionatore sta già eseguendo i parametri corretti.\n";
    }
} else {
    echo "-> Nessuna regola attiva in questo intervallo.\n";
    
    // Gestione Fine Finestra: Se è acceso, verifichiamo cosa fare
    if ($stato['pow'] === '1') {
        if ($azione_fine === 'spegni') {
            echo "-> Fine finestra: Eseguo lo SPEGNIMENTO automatico.\n";
            $stato['pow'] = '0';
            unset($stato['ret']);
            @file_get_contents("http://{$ip_clima}/aircon/set_control_info?" . http_build_query($stato), false, $context);
        } 
        elseif ($azione_fine === 'ripristina') {
            echo "-> Fine finestra: RIPRISTINO lo stato di backup originale.\n";
            $stato['pow'] = '1';
            $stato['mode'] = $stato['b_mode'] ?? '3';
            $stato['stemp'] = $stato['b_stemp'] ?? '24.0';
            $stato['f_rate'] = $stato['b_f_rate'] ?? 'A';
            $stato['f_dir'] = $stato['b_f_dir'] ?? '0';
            unset($stato['ret']);
            @file_get_contents("http://{$ip_clima}/aircon/set_control_info?" . http_build_query($stato), false, $context);
        } 
        else {
            echo "-> Fine finestra: NESSUNA AZIONE. Lascio il clima nello stato corrente.\n";
        }
    }
}
?>
