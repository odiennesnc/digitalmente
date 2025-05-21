<?php
/**
 * document_utils.php
 * 
 * DEPRECATO: NON UTILIZZARE DIRETTAMENTE QUESTO FILE
 * Utilizzare document_manager.php che centralizza tutte le funzioni relative ai documenti
 * 
 * Utility e funzioni ausiliarie per la gestione dei documenti
 * Ultima modifica: 20 maggio 2025 - Deprecato in favore di document_manager.php
 */

// Evita l'utilizzo diretto di questo file
if (!defined('DOCUMENT_MANAGER_LOADED')) {
    die('ERRORE: Non utilizzare document_utils.php direttamente. Includere document_manager.php invece.');
}

/**
 * Carica un documento dal database in base all'ID
 * @param mysqli $conn Connessione al database
 * @param int $id ID del documento
 * @return array|false Array associativo con i dati del documento o false in caso di errore
 */
function getDocumentById($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT d.*, a.argomento 
                               FROM documenti d 
                               LEFT JOIN argomenti a ON d.argomenti_id = a.id 
                               WHERE d.id = ?");
        if (!$stmt) {
            error_log("Errore nella preparazione query: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Errore nel caricamento del documento: " . $e->getMessage());
        return false;
    }
}

/**
 * Gestisce l'upload di un'immagine per un documento
 * @param array $file Array $_FILES['nome_campo']
 * @param string $old_file Nome del file precedente (per la sostituzione)
 * @return string|false Nome del file caricato o false in caso di errore
 */
function handleDocumentImageUpload($file, $old_file = null) {
    try {
        // Verifica che ci sia un file caricato
        if (!isset($file) || $file['error'] != UPLOAD_ERR_OK) {
            // Se non c'è un nuovo file, mantieni il vecchio
            if ($file['error'] == UPLOAD_ERR_NO_FILE && $old_file) {
                return $old_file;
            }
            return false;
        }
        
        $upload_dir = '../uploads/documents/';
        
        // Verifica directory di upload
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception("Impossibile creare la directory di upload");
            }
        }
        
        if (!is_writable($upload_dir)) {
            throw new Exception("La directory di upload non è scrivibile");
        }
        
        // Validazione del file
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $file_type = $file['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception("Formato file non supportato. Sono ammessi solo JPG, JPEG, PNG e GIF.");
        }
        
        // Generazione nome file sicuro
        $file_name = basename($file['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = uniqid('doc_') . '.' . $file_ext;
        $destination = $upload_dir . $new_file_name;
        
        // Upload del file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Se c'era un vecchio file, eliminalo
            if ($old_file && file_exists($upload_dir . $old_file)) {
                @unlink($upload_dir . $old_file);
            }
            return $new_file_name;
        } else {
            throw new Exception("Errore durante il caricamento del file");
        }
    } catch (Exception $e) {
        error_log("Errore nell'upload dell'immagine: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina un documento e la relativa immagine
 * @param mysqli $conn Connessione al database
 * @param int $id ID del documento da eliminare
 * @return bool
 */
function deleteDocument($conn, $id) {
    try {
        // Recupera il nome dell'immagine prima di eliminare il record
        $stmt = $conn->prepare("SELECT foto FROM documenti WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Errore nella preparazione query: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $doc = $result->fetch_assoc();
        
        // Elimina il record nel database
        $stmt = $conn->prepare("DELETE FROM documenti WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Errore nella preparazione query di eliminazione: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception("Errore nell'eliminazione del documento: " . $stmt->error);
        }
        
        // Se c'era un'immagine, eliminala dal filesystem
        if (!empty($doc['foto'])) {
            $image_path = '../uploads/documents/' . $doc['foto'];
            if (file_exists($image_path)) {
                @unlink($image_path);
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Errore nell'eliminazione del documento: " . $e->getMessage());
        return false;
    }
}

/**
 * Valida i campi per un documento
 * @param array $data Array associativo con i dati del documento
 * @return array Array con chiave 'valid' (bool) e 'message' (string)
 */
function validateDocumentData($data) {
    $errors = [];
    
    // Campi obbligatori comuni
    if (empty($data['titolo'])) {
        $errors[] = "Il titolo è obbligatorio";
    }
    
    if (!isset($data['tipologia_doc']) || !in_array((int)$data['tipologia_doc'], [1, 2, 3])) {
        $errors[] = "La tipologia documento non è valida";
    }
    
    // Validazione in base alla tipologia
    switch ((int)$data['tipologia_doc']) {
        case 1: // Libro
            // Log per debug della validazione libro
            error_log("Validazione libro: autore=" . (isset($data['autore']) ? $data['autore'] : 'vuoto') . 
                      ", editore=" . (isset($data['editore']) ? $data['editore'] : 'vuoto') . 
                      ", anno_pubblicazione=" . (isset($data['anno_pubblicazione']) ? $data['anno_pubblicazione'] : 'vuoto'));
            
            // Verifica che i campi non siano null prima di passarli al binding
            if (!isset($data['autore'])) $data['autore'] = '';
            if (!isset($data['editore'])) $data['editore'] = '';
            if (!isset($data['anno_pubblicazione'])) $data['anno_pubblicazione'] = '';
            break;
            
        case 2: // Rivista
            // Log per debug della validazione rivista
            error_log("Validazione rivista: editore=" . (isset($data['editore']) ? $data['editore'] : 'vuoto') . 
                      ", anno_pubblicazione=" . (isset($data['anno_pubblicazione']) ? $data['anno_pubblicazione'] : 'vuoto'));
            
            // Verifica che i campi non siano null prima di passarli al binding
            if (!isset($data['editore'])) $data['editore'] = '';
            if (!isset($data['anno_pubblicazione'])) $data['anno_pubblicazione'] = '';
            break;
            
        case 3: // Video
            // Log per debug della validazione video
            error_log("Validazione video: autore=" . (isset($data['autore']) ? $data['autore'] : 'vuoto') . 
                      ", anno_pubblicazione=" . (isset($data['anno_pubblicazione']) ? $data['anno_pubblicazione'] : 'vuoto'));
            
            // Verifica che i campi non siano null prima di passarli al binding
            if (!isset($data['autore'])) $data['autore'] = '';
            if (!isset($data['anno_pubblicazione'])) $data['anno_pubblicazione'] = '';
            break;
    }
    
    if (!empty($errors)) {
        return [
            'valid' => false,
            'message' => "Errori di validazione:<br>" . implode("<br>", $errors)
        ];
    }
    
    return [
        'valid' => true,
        'message' => ''
    ];
}

/**
 * Inserisce un nuovo documento nel database
 * @param mysqli $conn Connessione al database
 * @param array $data Array associativo con i dati del documento
 * @return bool
 */
function insertDocument($conn, $data) {
    try {
        // Validazione dei dati
        $validation = validateDocumentData($data);
        if (!$validation['valid']) {
            error_log("Validazione fallita: " . $validation['message']);
            return false;
        }
        
        // Prepara la query in base alla tipologia di documento
        $query = "INSERT INTO documenti (titolo, tipologia_doc, argomenti_id, anno_pubblicazione, foto";
        $values = "VALUES (?, ?, ?, ?, ?";
        $types = "ssiis"; // string, string, int, int, string
        $params = [
            $data['titolo'],
            $data['tipologia_doc'],
            $data['argomenti_id'],
            $data['anno_pubblicazione'],
            $data['foto']
        ];
        
        // Aggiungi campi specifici in base alla tipologia
        switch ((int)$data['tipologia_doc']) {
            case 1: // Libro
                $query .= ", autore, editore, collana, traduzione, pagine, indice, bibliografia";
                $values .= ", ?, ?, ?, ?, ?, ?, ?";
                $types .= "sssssss"; // 7 stringhe
                $params[] = $data['autore'] ?? '';
                $params[] = $data['editore'] ?? '';
                $params[] = $data['collana'] ?? '';
                $params[] = $data['traduzione'] ?? '';
                $params[] = $data['pagine'] ?? '';
                $params[] = $data['indice'] ?? '';
                $params[] = $data['bibliografia'] ?? '';
                break;
                
            case 2: // Rivista
                $query .= ", editore, mese, numero, sommario";
                $values .= ", ?, ?, ?, ?";
                $types .= "ssss"; // 4 stringhe
                $params[] = $data['editore'] ?? '';
                $params[] = $data['mese'] ?? '';
                $params[] = $data['numero'] ?? '';
                $params[] = $data['sommario'] ?? '';
                break;
                
            case 3: // Video
                $query .= ", autore, regia, montaggio, argomento_trattato";
                $values .= ", ?, ?, ?, ?";
                $types .= "ssss"; // 4 stringhe
                $params[] = $data['autore'] ?? '';
                $params[] = $data['regia'] ?? '';
                $params[] = $data['montaggio'] ?? '';
                $params[] = $data['argomento_trattato'] ?? '';
                break;
        }
        
        $query .= ") " . $values . ")";
        
        // Esegui la query
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Errore nella preparazione query: " . $conn->error);
        }
        
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Errore nell'inserimento del documento: " . $stmt->error);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Errore nell'inserimento del documento: " . $e->getMessage());
        return false;
    }
}

/**
 * Aggiorna un documento esistente nel database
 * @param mysqli $conn Connessione al database
 * @param array $data Array associativo con i dati del documento (deve contenere 'id')
 * @return bool
 */
function updateDocument($conn, $data) {
    try {
        if (!isset($data['id']) || empty($data['id'])) {
            throw new Exception("ID documento non specificato");
        }
        
        // Validazione dei dati
        $validation = validateDocumentData($data);
        if (!$validation['valid']) {
            error_log("Validazione fallita: " . $validation['message']);
            return false;
        }
        
        // Prepara la query in base alla tipologia di documento
        $query = "UPDATE documenti SET titolo = ?, tipologia_doc = ?, argomenti_id = ?, anno_pubblicazione = ?";
        $types = "ssis"; // string, string, int, string
        $params = [
            $data['titolo'],
            $data['tipologia_doc'],
            $data['argomenti_id'],
            $data['anno_pubblicazione']
        ];
        
        // Aggiorna il campo foto solo se è stato fornito
        if (isset($data['foto']) && !empty($data['foto'])) {
            $query .= ", foto = ?";
            $types .= "s";
            $params[] = $data['foto'];
        }
        
        // Aggiungi campi specifici in base alla tipologia
        switch ((int)$data['tipologia_doc']) {
            case 1: // Libro
                $query .= ", autore = ?, editore = ?, collana = ?, traduzione = ?, pagine = ?, indice = ?, bibliografia = ?";
                $types .= "sssssss"; // 7 stringhe
                $params[] = $data['autore'] ?? '';
                $params[] = $data['editore'] ?? '';
                $params[] = $data['collana'] ?? '';
                $params[] = $data['traduzione'] ?? '';
                $params[] = $data['pagine'] ?? '';
                $params[] = $data['indice'] ?? '';
                $params[] = $data['bibliografia'] ?? '';
                break;
                
            case 2: // Rivista
                $query .= ", editore = ?, mese = ?, numero = ?, sommario = ?";
                $types .= "ssss"; // 4 stringhe
                $params[] = $data['editore'] ?? '';
                $params[] = $data['mese'] ?? '';
                $params[] = $data['numero'] ?? '';
                $params[] = $data['sommario'] ?? '';
                break;
                
            case 3: // Video
                $query .= ", autore = ?, regia = ?, montaggio = ?, argomento_trattato = ?";
                $types .= "ssss"; // 4 stringhe
                $params[] = $data['autore'] ?? '';
                $params[] = $data['regia'] ?? '';
                $params[] = $data['montaggio'] ?? '';
                $params[] = $data['argomento_trattato'] ?? '';
                break;
        }
        
        $query .= " WHERE id = ?";
        $types .= "i"; // int per ID
        $params[] = $data['id'];
        
        // Esegui la query
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Errore nella preparazione query: " . $conn->error);
        }
        
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Errore nell'aggiornamento del documento: " . $stmt->error);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Errore nell'aggiornamento del documento: " . $e->getMessage());
        return false;
    }
}
?>
