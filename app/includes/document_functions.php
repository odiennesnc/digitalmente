<?php
/**
 * document_functions.php
 * 
 * DEPRECATO: NON UTILIZZARE DIRETTAMENTE QUESTO FILE
 * Utilizzare document_manager.php che centralizza tutte le funzioni relative ai documenti
 * 
 * Versione rinnovata - 20 maggio 2025
 * Ultima modifica: 20 maggio 2025 - Deprecato in favore di document_manager.php
 */

// Evita l'utilizzo diretto di questo file
if (!defined('DOCUMENT_MANAGER_LOADED')) {
    die('ERRORE: Non utilizzare document_functions.php direttamente. Includere document_manager.php invece.');
}

/**
 * Ottiene il nome della tipologia di documento
 * 
 * @param int $type_id ID della tipologia
 * @return string Nome della tipologia
 */
function getDocumentTypeName($type_id) {
    $types = [
        1 => 'Libro',
        2 => 'Rivista',
        3 => 'Video'
    ];
    
    return isset($types[$type_id]) ? $types[$type_id] : 'Sconosciuto';
}

/**
 * Ottiene tutte le tipologie di documento
 * 
 * @return array Array associativo con le tipologie
 */
function getDocumentTypes() {
    return [
        1 => 'Libro',
        2 => 'Rivista',
        3 => 'Video'
    ];
}

/**
 * Carica un documento dal database
 * 
 * @param mysqli $conn Connessione al database
 * @param int $id ID del documento
 * @return array|null Dati del documento o null se non trovato
 * 
 * Nota: Questa funzione è stata spostata nel file document_utils.php
 * per evitare duplicazioni
 */
// function getDocumentById() - spostata in document_utils.php

/**
 * Nota: La funzione deleteDocument è stata spostata in document_utils.php
 * per evitare duplicazioni e mantenere la gestione delle immagini uniforme
 */

/**
 * Carica un'immagine per un documento
 * 
 * @param array $file Array $_FILES per l'immagine
 * @param string|null $old_image Nome del file immagine precedente
 * @return string|false Nome del file caricato o false in caso di errore
 */
function uploadDocumentImage($file, $old_image = null) {
    $upload_dir = __DIR__ . '/../uploads/documents/';
    
    // Verifica se esiste la directory, altrimenti creala
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            error_log("Impossibile creare la directory di upload");
            return false;
        }
    }
    
    // Se non c'è un nuovo file e c'è un'immagine precedente, mantieni quella vecchia
    if ($file['error'] === UPLOAD_ERR_NO_FILE && $old_image) {
        return $old_image;
    }
    
    // Verifica errori di upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("Errore nell'upload del file: " . $file['error']);
        return false;
    }
    
    // Controlla il tipo di file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        error_log("Tipo di file non supportato: " . $file['type']);
        return false;
    }
    
    // Genera un nome file univoco
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'doc_' . uniqid() . '.' . $file_extension;
    $destination = $upload_dir . $new_filename;
    
    // Sposta il file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Elimina il vecchio file se esiste
        if ($old_image && file_exists($upload_dir . $old_image)) {
            @unlink($upload_dir . $old_image);
        }
        return $new_filename;
    }
    
    error_log("Errore nello spostamento del file caricato");
    return false;
}

/**
 * Ottiene i campi richiesti per una tipologia di documento specifica
 * 
 * @param int $type_id ID della tipologia
 * @return array Array con i nomi dei campi
 */
function getRequiredFieldsByType($type_id) {
    $common_fields = ['titolo', 'anno_pubblicazione'];
    
    switch ((int)$type_id) {
        case 1: // Libro
            return array_merge($common_fields, ['autore', 'editore']);
        case 2: // Rivista
            return array_merge($common_fields, ['editore']);
        case 3: // Video
            return array_merge($common_fields, ['autore']);
        default:
            return $common_fields;
    }
}

/**
 * Valida i dati di un documento
 * 
 * @param array $data Array con i dati del documento
 * @return array Array con indice 'valid' (booleano) e 'errors' (array di errori)
 */
function validateDocumentData($data) {
    $errors = [];
    
    // Verifica i campi obbligatori comuni
    if (empty($data['titolo'])) {
        $errors[] = "Il titolo è obbligatorio";
    }
    
    if (empty($data['tipologia_doc']) || !in_array((int)$data['tipologia_doc'], [1, 2, 3])) {
        $errors[] = "La tipologia documento non è valida";
    }
    
    // Verifica i campi in base alla tipologia
    if (!empty($data['tipologia_doc'])) {
        $required_fields = getRequiredFieldsByType((int)$data['tipologia_doc']);
        
        foreach ($required_fields as $field) {
            if ($field !== 'titolo' && empty($data[$field])) {
                $errors[] = "Il campo " . ucfirst($field) . " è obbligatorio per questo tipo di documento";
            }
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Salva un documento nel database
 * 
 * @param mysqli $conn Connessione al database
 * @param array $data Dati del documento
 * @param int|null $id ID del documento esistente (null per nuovo documento)
 * @return int|false ID del documento inserito/aggiornato o false in caso di errore
 */
function saveDocument($conn, $data, $id = null) {
    // Validazione
    $validation = validateDocumentData($data);
    if (!$validation['valid']) {
        error_log("Errori di validazione documento: " . implode(", ", $validation['errors']));
        return false;
    }
    
    // Prepara i dati per il database
    $fields = [
        'argomenti_id' => isset($data['argomenti_id']) && is_numeric($data['argomenti_id']) ? (int)$data['argomenti_id'] : null,
        'tipologia_doc' => (int)$data['tipologia_doc'],
        'titolo' => $data['titolo'],
        'anno_pubblicazione' => $data['anno_pubblicazione'] ?? '',
        'autore' => $data['autore'] ?? '',
        'editore' => $data['editore'] ?? '',
        'collana' => $data['collana'] ?? '',
        'traduzione' => $data['traduzione'] ?? '',
        'pagine' => $data['pagine'] ?? '',
        'indice' => $data['indice'] ?? '',
        'bibliografia' => $data['bibliografia'] ?? '',
        'mese' => $data['mese'] ?? '',
        'numero' => $data['numero'] ?? '',
        'sommario' => $data['sommario'] ?? '',
        'regia' => $data['regia'] ?? '',
        'montaggio' => $data['montaggio'] ?? '',
        'argomento_trattato' => $data['argomento_trattato'] ?? ''
    ];
    
    // Se c'è un'immagine, gestiscila
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] != UPLOAD_ERR_NO_FILE) {
        $old_image = null;
        
        // Se stiamo aggiornando un documento, recupera il nome dell'immagine precedente
        if ($id) {
            $doc = getDocumentById($conn, $id);
            if ($doc && !empty($doc['foto'])) {
                $old_image = $doc['foto'];
            }
        }
        
        $image_name = uploadDocumentImage($_FILES['foto'], $old_image);
        
        if ($image_name !== false) {
            $fields['foto'] = $image_name;
        }
    }
    
    try {
        if ($id) {
            // Aggiornamento documento esistente
            $sql_parts = [];
            foreach ($fields as $field => $value) {
                $sql_parts[] = "`{$field}` = ?";
            }
            
            $sql = "UPDATE documenti SET " . implode(", ", $sql_parts) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                error_log("Errore nella preparazione query di aggiornamento: " . $conn->error);
                return false;
            }
            
            // Aggiungi tutti i valori all'array dei parametri
            $params = array_values($fields);
            $params[] = $id; // Aggiungi l'ID come ultimo parametro
            
            // Prepara i tipi per bind_param
            $types = str_repeat('s', count($fields)) . 'i'; // Tutti stringhe + un intero per l'ID
            
            // Esegui il binding dei parametri con l'operatore splat
            $stmt->bind_param($types, ...$params);
            
        } else {
            // Inserimento nuovo documento
            $fields_keys = array_keys($fields);
            $placeholders = array_fill(0, count($fields), '?');
            
            $sql = "INSERT INTO documenti (" . implode(", ", array_map(function($field) {
                return "`{$field}`";
            }, $fields_keys)) . ") VALUES (" . implode(", ", $placeholders) . ")";
            
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                error_log("Errore nella preparazione query di inserimento: " . $conn->error);
                return false;
            }
            
            // Prepara i tipi per bind_param
            $types = str_repeat('s', count($fields)); // Tutti i campi come stringhe
            
            // Esegui il binding dei parametri con l'operatore splat
            $stmt->bind_param($types, ...array_values($fields));
        }
        
        // Esegui la query
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("Errore nell'esecuzione della query: " . $stmt->error);
            return false;
        }
        
        // Restituisci l'ID del documento
        if ($id) {
            return $id; // Documento aggiornato
        } else {
            return $conn->insert_id; // Nuovo documento
        }
        
    } catch (Exception $e) {
        error_log("Errore nel salvataggio del documento: " . $e->getMessage());
        return false;
    }
}
?>
