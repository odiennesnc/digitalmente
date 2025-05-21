<?php
/**
 * Utility per la gestione avanzata degli errori del database
 */

/**
 * Registra un errore dettagliato di database nel log
 *
 * @param mysqli|mysqli_stmt $source L'oggetto mysqli o mysqli_stmt che ha generato l'errore
 * @param Exception|null $exception L'eccezione catturata, se disponibile
 * @param string $context Contesto dell'errore (es. "binding", "query", ecc.)
 * @param array $params Parametri aggiuntivi per il debugging
 * @return void
 */
function logDatabaseError($source, $exception = null, $context = '', $params = []) {
    $now = date('Y-m-d H:i:s');
    $error_log = "[$now] ERRORE DATABASE";
    
    if (!empty($context)) {
        $error_log .= " ($context)";
    }
    
    $error_log .= ":\n";
    
    // Informazioni sull'eccezione
    if ($exception instanceof Exception) {
        $error_log .= "- Messaggio: " . $exception->getMessage() . "\n";
        $error_log .= "- File: " . $exception->getFile() . " (linea " . $exception->getLine() . ")\n";
    }
    
    // Informazioni sull'errore di mysqli
    if ($source instanceof mysqli) {
        $error_log .= "- MySQLi error: " . $source->error . "\n";
        $error_log .= "- Codice errore: " . $source->errno . "\n";
        $error_log .= "- SQLState: " . $source->sqlstate . "\n";
    } elseif ($source instanceof mysqli_stmt) {
        $error_log .= "- MySQLi stmt error: " . $source->error . "\n";
        $error_log .= "- Codice errore: " . $source->errno . "\n";
    }
    
    // Parametri aggiuntivi
    if (!empty($params)) {
        $error_log .= "- Parametri aggiuntivi:\n";
        foreach ($params as $key => $value) {
            $display_value = is_array($value) ? json_encode($value) : (string)$value;
            $error_log .= "  - $key: $display_value\n";
        }
    }
    
    // Traccia dello stack
    if ($exception instanceof Exception) {
        $error_log .= "- Stack trace:\n" . $exception->getTraceAsString() . "\n";
    }
    
    $error_log .= "-------------------------------\n";
    
    // Scriviamo nel log
    error_log($error_log);
}

/**
 * Genera un messaggio di errore user-friendly dal database
 *
 * @param mysqli|mysqli_stmt $source L'oggetto mysqli o mysqli_stmt che ha generato l'errore
 * @param Exception|null $exception L'eccezione catturata, se disponibile
 * @param bool $includeDetails Se includere dettagli tecnici (solo per admin)
 * @return string Il messaggio di errore
 */
function getUserFriendlyError($source, $exception = null, $includeDetails = false) {
    // Messaggi generici
    $genericMessage = "Si è verificato un errore durante l'operazione con il database. ";
    
    if (!$includeDetails) {
        return $genericMessage . "Si prega di riprovare più tardi.";
    }
    
    // Ottiene l'errore specifico
    $errorDetail = "";
    
    if ($source instanceof mysqli) {
        $errorDetail = $source->error;
    } elseif ($source instanceof mysqli_stmt) {
        $errorDetail = $source->error;
    } elseif ($exception instanceof Exception) {
        $errorDetail = $exception->getMessage();
    }
    
    // Messaggi più specifici per errori comuni
    if (stripos($errorDetail, 'duplicate') !== false) {
        return $genericMessage . "Esiste già un record con questi dati.";
    } elseif (stripos($errorDetail, 'constraint') !== false) {
        return $genericMessage . "Impossibile completare l'operazione per vincoli di integrità dei dati.";
    } elseif (stripos($errorDetail, 'parameters') !== false || stripos($errorDetail, 'binding') !== false) {
        return $genericMessage . "Errore di formattazione nei parametri. Verificare i dati inseriti.";
    }
    
    return $genericMessage . "Dettagli tecnici: " . $errorDetail;
}
