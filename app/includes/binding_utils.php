<?php
// File di utility per il binding di parametri
// Utilizzare questa funzione come alternativa quando il binding standard fallisce

/**
 * Effettua il binding di parametri ad un prepared statement in modo più robusto
 * usando un array di riferimenti per superare i limiti del binding standard.
 *
 * @param mysqli_stmt $stmt   Lo statement preparato
 * @param string      $types  Stringa con i tipi di binding (i=integer, s=string, d=double, b=blob)
 * @param array       $params Array di parametri da bindare
 * @return bool              True se il binding ha successo, False in caso contrario
 */
function bindParameters($stmt, $types, $params) {
    // Verificare che il numero di tipi corrisponda al numero di parametri
    if (strlen($types) != count($params)) {
        error_log("ERRORE bind: il numero di tipi (" . strlen($types) . ") non corrisponde al numero di parametri (" . count($params) . ")");
        return false;
    }
    
    // Assicuriamoci che tutti i parametri siano del tipo corretto
    for ($i = 0; $i < strlen($types); $i++) {
        $type = $types[$i];
        
        // Assicuriamoci che il parametro sia del tipo corretto
        switch ($type) {
            case 'i': // integer
                $params[$i] = (int)$params[$i];
                break;
            case 'd': // double
                $params[$i] = (float)$params[$i];
                break;
            case 's': // string
                $params[$i] = (string)($params[$i] ?? '');
                break;
            case 'b': // blob
                if (!is_string($params[$i]) && !is_null($params[$i])) {
                    $params[$i] = (string)$params[$i];
                }
                break;
        }
    }
    
    // Creiamo un array di riferimenti per il binding
    $bind_params = [];
    $bind_params[] = $types; // Il primo elemento è sempre la stringa dei tipi
    
    // Per ogni parametro, creiamo un riferimento 
    $refs = []; // Manteniamo un array separato di riferimenti per evitare problemi di scope
    for ($i = 0; $i < count($params); $i++) {
        $refs[$i] = $params[$i]; // Copia il valore in una variabile referenziabile
        $bind_params[] = &$refs[$i]; // Usa il riferimento nel binding
    }
    
    // Eseguiamo il binding con call_user_func_array
    return call_user_func_array([$stmt, 'bind_param'], $bind_params);
}

/**
 * Controlla il numero di segnaposto (?) in una query SQL
 *
 * @param string $sql  La query SQL da controllare
 * @return int        Il numero di segnaposto (?) trovati
 */
function countPlaceholders($sql) {
    return substr_count($sql, '?');
}

/**
 * Utility per diagnostica di binding
 *
 * @param string      $types   Stringa con i tipi di binding
 * @param array       $params  Array di parametri da bindare
 * @return string             HTML con tabella di diagnostica
 */
function diagnosticaBinding($types, $params) {
    $output = "<table border='1' cellpadding='5'>";
    $output .= "<tr><th>Indice</th><th>Tipo</th><th>Valore</th><th>Tipo PHP</th></tr>";
    
    for ($i = 0; $i < strlen($types); $i++) {
        $type = $types[$i];
        $value = isset($params[$i]) ? $params[$i] : 'NON DEFINITO';
        $php_type = isset($params[$i]) ? gettype($params[$i]) : 'N/A';
        
        $output .= "<tr>";
        $output .= "<td>$i</td>";
        $output .= "<td>$type</td>";
        $output .= "<td>" . htmlspecialchars(var_export($value, true)) . "</td>";
        $output .= "<td>$php_type</td>";
        $output .= "</tr>";
    }
    
    $output .= "</table>";
    return $output;
}

/**
 * Gestisce l'intero processo di binding in modo sicuro, con vari fallback
 * 
 * @param mysqli_stmt $stmt   Lo statement preparato
 * @param string      $types  Stringa con i tipi di binding (i=integer, s=string, d=double, b=blob)
 * @param array       $params Array di valori dei parametri 
 * @return bool              True se il binding ha successo, False in caso contrario
 */
function secureBind($stmt, $types, $params) {
    if (!$stmt instanceof mysqli_stmt) {
        error_log("ERRORE: secureBind richiede un oggetto mysqli_stmt valido");
        return false;
    }
    
    // Normalizza e verifica i tipi
    if (strlen($types) != count($params)) {
        error_log("ERRORE: Mismatch tra tipi (" . strlen($types) . ") e parametri (" . count($params) . ")");
        return false;
    }
    
    error_log("SECURE BIND: " . count($params) . " parametri con pattern '$types'");
    
    // APPROCCIO 1: Binding diretto
    try {
        $refs = []; // Array temporaneo per mantenere i riferimenti
        $bind_params = [$types]; // Inizia con il tipo
        
        // Prepara riferimenti ai parametri
        for ($i = 0; $i < count($params); $i++) {
            $refs[$i] = $params[$i];
            $bind_params[] = &$refs[$i];
            error_log("BIND PARAM[$i]: " . (is_string($params[$i]) ? "'" . $params[$i] . "'" : $params[$i]) . " - tipo: " . gettype($params[$i]));
        }
        
        error_log("BIND APPROCCIO 1: call_user_func_array");
        if (call_user_func_array([$stmt, 'bind_param'], $bind_params)) {
            error_log("BIND APPROCCIO 1: Successo!");
            return true;
        }
        
        error_log("BIND APPROCCIO 1 fallito: " . $stmt->error);
    } catch (Exception $e) {
        error_log("BIND APPROCCIO 1 eccezione: " . $e->getMessage());
    }
    
    // APPROCCIO 2: Binding con reflection API
    try {
        error_log("BIND APPROCCIO 2: Reflection API");
        $method = new ReflectionMethod('mysqli_stmt', 'bind_param');
        $refs = []; // Reset dell'array
        $bind_params = [$types]; // Inizia con il tipo
        
        // Prepara nuovamente i riferimenti
        for ($i = 0; $i < count($params); $i++) {
            $refs[$i] = $params[$i];
            $bind_params[] = &$refs[$i];
        }
        
        if ($method->invokeArgs($stmt, $bind_params)) {
            error_log("BIND APPROCCIO 2: Successo!");
            return true;
        }
        
        error_log("BIND APPROCCIO 2 fallito: " . $stmt->error);
    } catch (Exception $e) {
        error_log("BIND APPROCCIO 2 eccezione: " . $e->getMessage());
    }
    
    // APPROCCIO 3: Lega i parametri uno alla volta (solo per debug/diagnostica)
    // Questo non funzionerà per il binding, ma ci aiuta a identificare quale parametro causa problemi
    try {
        error_log("BIND APPROCCIO 3: Test parametri individuali");
        for ($i = 0; $i < count($params); $i++) {
            $type = $types[$i];
            $param = $params[$i];
            error_log("Test parametro[$i]: " . (is_string($param) ? "'" . $param . "'" : $param) . " ($type)");
            
            // Questo è solo per logging, non un binding reale
            switch ($type) {
                case 'i':
                    if (!is_int($param)) {
                        error_log("ATTENZIONE: Il parametro[$i] dovrebbe essere un intero ma è " . gettype($param));
                    }
                    break;
                case 's':
                    if (!is_string($param)) {
                        error_log("ATTENZIONE: Il parametro[$i] dovrebbe essere una stringa ma è " . gettype($param));
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        error_log("BIND APPROCCIO 3 eccezione: " . $e->getMessage());
    }
    
    error_log("ERRORE CRITICO: Tutti i tentativi di binding sono falliti!");
    return false;
}

/**
 * Prepara un array di parametri per un prepared statement applicando
 * le conversioni di tipo appropriate in base al pattern di tipi
 * 
 * @param string $types   Stringa dei tipi ('i', 's', 'd', 'b')
 * @param array  $params  Array di parametri da preparare
 * @return array         Array di parametri convertiti nel tipo corretto
 */
function prepareParameters($types, $params) {
    $prepared = [];
    
    for ($i = 0; $i < strlen($types) && $i < count($params); $i++) {
        $type = $types[$i];
        $value = $params[$i] ?? null;
        
        // Applica la conversione di tipo appropriata
        switch ($type) {
            case 'i': // integer
                $prepared[$i] = (int)$value;
                break;
                
            case 'd': // double/float
                $prepared[$i] = (float)$value;
                break;
                
            case 's': // string
                // Converti null/false in stringa vuota
                $prepared[$i] = (string)($value ?: '');
                break;
                
            case 'b': // blob
                // I blob possono essere null o stringhe binarie
                $prepared[$i] = $value === null ? null : (string)$value;
                break;
                
            default:
                // Tipo non riconosciuto, usa il valore così com'è
                $prepared[$i] = $value;
        }
    }
    
    return $prepared;
}
